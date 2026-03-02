<?php

namespace Grafite\Support\Models\Concerns;

use Illuminate\Support\Facades\Cache;
use Grafite\Support\Exceptions\ModelLockedException;

trait HasLocking
{
    /**
     * The lock timeout in minutes.
     *
     * @var int
     */
    protected static int $lockTimeout = 15;

    /**
     * Boot the HasLocking trait.
     *
     * @return void
     */
    public static function bootHasLocking()
    {
        static::updating(function ($model) {
            if ($model->isLocked() && ! $model->isLockedBySession(auth()->id())) {
                throw new ModelLockedException($model, $model->lockedBy());
            }
        });

        static::deleting(function ($model) {
            if ($model->isLocked() && ! $model->isLockedBySession(auth()->id())) {
                throw new ModelLockedException($model, $model->lockedBy());
            }
        });
    }

    /**
     * Get the current session identifier.
     *
     * @return string|null
     */
    protected function getSessionIdentifier(): ?string
    {
        if (function_exists('session') && session()->isStarted()) {
            return session()->getId();
        }

        return null;
    }

    /**
     * Get the cache key for the model lock.
     *
     * @return string
     */
    public function getLockCacheKey(): string
    {
        $class = str_replace('\\', '-', get_class($this));

        return strtolower("model_lock_{$class}_{$this->getKey()}");
    }

    /**
     * Lock the model for the given user and session.
     *
     * @param int|null $userId
     * @param string|null $sessionId
     * @return bool
     */
    public function lock(?int $userId = null, ?string $sessionId = null): bool
    {
        $userId = $userId ?? auth()->id();
        $sessionId = $sessionId ?? $this->getSessionIdentifier();

        if (! $userId) {
            return false;
        }

        // If already locked by another user/session, don't allow locking
        if ($this->isLocked() && ! $this->isLockedBySession($userId, $sessionId)) {
            return false;
        }

        Cache::put($this->getLockCacheKey(), [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'locked_at' => now()->timestamp,
            'updated_at' => $this->updated_at?->timestamp ?? now()->timestamp,
        ], now()->addMinutes(static::$lockTimeout));

        return true;
    }

    /**
     * Unlock the model.
     *
     * @param int|null $userId
     * @param string|null $sessionId
     * @return bool
     */
    public function unlock(?int $userId = null, ?string $sessionId = null): bool
    {
        $userId = $userId ?? auth()->id();
        $sessionId = $sessionId ?? $this->getSessionIdentifier();

        // Only the user/session who locked it can unlock
        if ($userId && $this->isLocked() && ! $this->isLockedBySession($userId, $sessionId)) {
            return false;
        }

        Cache::forget($this->getLockCacheKey());

        return true;
    }

    /**
     * Force unlock the model regardless of who locked it.
     *
     * @return bool
     */
    public function forceUnlock(): bool
    {
        Cache::forget($this->getLockCacheKey());

        return true;
    }

    /**
     * Check if the model is currently locked.
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        $lockData = Cache::get($this->getLockCacheKey());

        if (! $lockData) {
            return false;
        }

        // Check if the lock has expired based on updated_at not changing for 15 minutes
        $modelUpdatedAt = $this->updated_at?->timestamp ?? 0;
        $lockUpdatedAt = $lockData['updated_at'] ?? 0;

        // If updated_at has changed since the lock was created, refresh the lock timeout
        if ($modelUpdatedAt > $lockUpdatedAt) {
            // Extend the lock with the new updated_at
            Cache::put($this->getLockCacheKey(), [
                'user_id' => $lockData['user_id'],
                'session_id' => $lockData['session_id'] ?? null,
                'locked_at' => $lockData['locked_at'],
                'updated_at' => $modelUpdatedAt,
            ], now()->addMinutes(static::$lockTimeout));

            return true;
        }

        // If the model hasn't been updated for 15 minutes, the lock is expired
        if ($modelUpdatedAt === $lockUpdatedAt && now()->timestamp - $lockUpdatedAt >= static::$lockTimeout * 60) {
            Cache::forget($this->getLockCacheKey());

            return false;
        }

        return true;
    }

    /**
     * Check if the model is locked by a specific user (ignoring session).
     *
     * @param int|null $userId
     * @return bool
     */
    public function isLockedBy(?int $userId): bool
    {
        if (! $this->isLocked()) {
            return false;
        }

        $lockData = Cache::get($this->getLockCacheKey());

        return $lockData && $lockData['user_id'] === $userId;
    }

    /**
     * Check if the model is locked by a specific user and session.
     *
     * @param int|null $userId
     * @param string|null $sessionId
     * @return bool
     */
    public function isLockedBySession(?int $userId, ?string $sessionId = null): bool
    {
        if (! $this->isLocked()) {
            return false;
        }

        $sessionId = $sessionId ?? $this->getSessionIdentifier();
        $lockData = Cache::get($this->getLockCacheKey());

        if (! $lockData || $lockData['user_id'] !== $userId) {
            return false;
        }

        // If no session_id in lock data, only check user_id (backwards compatibility)
        if (! isset($lockData['session_id']) || $lockData['session_id'] === null) {
            return true;
        }

        // If no current session, can't match
        if ($sessionId === null) {
            return false;
        }

        return $lockData['session_id'] === $sessionId;
    }

    /**
     * Get the user ID who has locked the model.
     *
     * @return int|null
     */
    public function lockedBy(): ?int
    {
        if (! $this->isLocked()) {
            return null;
        }

        $lockData = Cache::get($this->getLockCacheKey());

        return $lockData['user_id'] ?? null;
    }

    /**
     * Get the session ID that has locked the model.
     *
     * @return string|null
     */
    public function lockedBySession(): ?string
    {
        if (! $this->isLocked()) {
            return null;
        }

        $lockData = Cache::get($this->getLockCacheKey());

        return $lockData['session_id'] ?? null;
    }

    /**
     * Get the lock data for the model.
     *
     * @return array|null
     */
    public function getLockData(): ?array
    {
        if (! $this->isLocked()) {
            return null;
        }

        return Cache::get($this->getLockCacheKey());
    }

    /**
     * Refresh the lock by updating the updated_at timestamp in the lock data.
     *
     * @return bool
     */
    public function refreshLock(): bool
    {
        if (! $this->isLocked()) {
            return false;
        }

        $lockData = Cache::get($this->getLockCacheKey());

        if (! $lockData || ! $this->isLockedBy($lockData['user_id'])) {
            return false;
        }

        Cache::put($this->getLockCacheKey(), [
            'user_id' => $lockData['user_id'],
            'session_id' => $lockData['session_id'] ?? null,
            'locked_at' => $lockData['locked_at'],
            'updated_at' => now()->timestamp,
        ], now()->addMinutes(static::$lockTimeout));

        return true;
    }

    /**
     * Set the lock timeout in minutes.
     *
     * @param int $minutes
     * @return void
     */
    public static function setLockTimeout(int $minutes): void
    {
        static::$lockTimeout = $minutes;
    }

    /**
     * Get the lock timeout in minutes.
     *
     * @return int
     */
    public static function getLockTimeout(): int
    {
        return static::$lockTimeout;
    }
}
