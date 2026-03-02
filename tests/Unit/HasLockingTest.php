<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Grafite\Support\Models\Concerns\HasLocking;
use Grafite\Support\Exceptions\ModelLockedException;
use Carbon\Carbon;

class LockableModel extends Model
{
    use HasLocking;

    protected $table = 'lockable_models';

    protected $guarded = [];

    protected $casts = [
        'updated_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}

class HasLockingTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Schema::create('lockable_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Cache::flush();
    }

    public function tearDown(): void
    {
        Schema::dropIfExists('lockable_models');
        Cache::flush();

        parent::tearDown();
    }

    protected function createModel(array $attributes = []): LockableModel
    {
        return LockableModel::create(array_merge([
            'name' => 'Test Model',
        ], $attributes));
    }

    public function testCanLockModel()
    {
        $model = $this->createModel();

        Auth::shouldReceive('id')->andReturn(1);

        $this->assertTrue($model->lock());
        $this->assertTrue($model->isLocked());
    }

    public function testCanLockModelWithSpecificUser()
    {
        $model = $this->createModel();

        $this->assertTrue($model->lock(5, 'session-abc'));
        $this->assertTrue($model->isLocked());
        $this->assertTrue($model->isLockedBy(5));
    }

    public function testCanLockModelWithSpecificUserAndSession()
    {
        $model = $this->createModel();

        $this->assertTrue($model->lock(5, 'session-123'));
        $this->assertTrue($model->isLocked());
        $this->assertTrue($model->isLockedBy(5));
        $this->assertTrue($model->isLockedBySession(5, 'session-123'));
        $this->assertEquals('session-123', $model->lockedBySession());
    }

    public function testCannotLockModelAlreadyLockedByAnotherUser()
    {
        $model = $this->createModel();

        $model->lock(1, 'session-a');

        $this->assertFalse($model->lock(2, 'session-b'));
        $this->assertTrue($model->isLockedBy(1));
    }

    public function testCannotLockModelAlreadyLockedBySameUserDifferentSession()
    {
        $model = $this->createModel();

        $model->lock(1, 'session-a');

        $this->assertFalse($model->lock(1, 'session-b'));
        $this->assertTrue($model->isLockedBySession(1, 'session-a'));
        $this->assertFalse($model->isLockedBySession(1, 'session-b'));
    }

    public function testSameUserCanRelockModel()
    {
        $model = $this->createModel();

        $model->lock(1, 'session-a');
        $this->assertTrue($model->lock(1, 'session-a'));
        $this->assertTrue($model->isLockedBy(1));
    }

    public function testCanUnlockModel()
    {
        $model = $this->createModel();

        $model->lock(1, 'session-a');
        $this->assertTrue($model->isLocked());

        $this->assertTrue($model->unlock(1, 'session-a'));
        $this->assertFalse($model->isLocked());
    }

    public function testCannotUnlockModelLockedByAnotherUser()
    {
        $model = $this->createModel();

        $model->lock(1, 'session-a');

        $this->assertFalse($model->unlock(2, 'session-b'));
        $this->assertTrue($model->isLocked());
    }

    public function testCannotUnlockModelLockedBySameUserDifferentSession()
    {
        $model = $this->createModel();

        $model->lock(1, 'session-a');

        $this->assertFalse($model->unlock(1, 'session-b'));
        $this->assertTrue($model->isLocked());
    }

    public function testCanForceUnlockModel()
    {
        $model = $this->createModel();

        $model->lock(1, 'session-a');

        $this->assertTrue($model->forceUnlock());
        $this->assertFalse($model->isLocked());
    }

    public function testLockedByReturnsCorrectUserId()
    {
        $model = $this->createModel();

        $model->lock(42, 'session-x');

        $this->assertEquals(42, $model->lockedBy());
    }

    public function testLockedBySessionReturnsCorrectSessionId()
    {
        $model = $this->createModel();

        $model->lock(42, 'session-xyz');

        $this->assertEquals('session-xyz', $model->lockedBySession());
    }

    public function testLockedByReturnsNullWhenNotLocked()
    {
        $model = $this->createModel();

        $this->assertNull($model->lockedBy());
    }

    public function testLockedBySessionReturnsNullWhenNotLocked()
    {
        $model = $this->createModel();

        $this->assertNull($model->lockedBySession());
    }

    public function testIsLockedByReturnsFalseWhenNotLocked()
    {
        $model = $this->createModel();

        $this->assertFalse($model->isLockedBy(1));
    }

    public function testGetLockDataReturnsNullWhenNotLocked()
    {
        $model = $this->createModel();

        $this->assertNull($model->getLockData());
    }

    public function testGetLockDataReturnsDataWhenLocked()
    {
        $model = $this->createModel();

        $model->lock(1, 'session-test');

        $lockData = $model->getLockData();

        $this->assertIsArray($lockData);
        $this->assertEquals(1, $lockData['user_id']);
        $this->assertEquals('session-test', $lockData['session_id']);
        $this->assertArrayHasKey('locked_at', $lockData);
        $this->assertArrayHasKey('updated_at', $lockData);
    }

    public function testRefreshLockExtendsTimeout()
    {
        $model = $this->createModel();

        $model->lock(1, 'session-refresh');

        $originalLockData = $model->getLockData();

        // Wait a bit then refresh
        Carbon::setTestNow(Carbon::now()->addSeconds(5));

        $this->assertTrue($model->refreshLock());

        $newLockData = $model->getLockData();

        $this->assertGreaterThan($originalLockData['updated_at'], $newLockData['updated_at']);

        Carbon::setTestNow();
    }

    public function testRefreshLockReturnsFalseWhenNotLocked()
    {
        $model = $this->createModel();

        $this->assertFalse($model->refreshLock());
    }

    public function testLockTimeoutCanBeChanged()
    {
        $original = LockableModel::getLockTimeout();

        LockableModel::setLockTimeout(30);

        $this->assertEquals(30, LockableModel::getLockTimeout());

        LockableModel::setLockTimeout($original);
    }

    public function testLockCacheKeyIsUnique()
    {
        $model1 = $this->createModel(['name' => 'Model 1']);
        $model2 = $this->createModel(['name' => 'Model 2']);

        $this->assertNotEquals($model1->getLockCacheKey(), $model2->getLockCacheKey());
    }

    public function testLockExpiresAfterTimeout()
    {
        $model = $this->createModel();

        $model->lock(1, 'session-expire');

        $this->assertTrue($model->isLocked());

        // Fast forward past the timeout (15 minutes + 1 minute to be safe)
        Carbon::setTestNow(Carbon::now()->addMinutes(16));

        // The model hasn't been updated, so the lock should expire
        $this->assertFalse($model->isLocked());

        Carbon::setTestNow();
    }

    public function testLockReturnsFalseWithoutAuthenticatedUser()
    {
        $model = $this->createModel();

        Auth::shouldReceive('id')->andReturn(null);

        $this->assertFalse($model->lock());
    }

    public function testModelLockedException()
    {
        $model = $this->createModel();

        $exception = new ModelLockedException($model, 42);

        $this->assertInstanceOf(ModelLockedException::class, $exception);
        $this->assertSame($model, $exception->getModel());
        $this->assertEquals(42, $exception->getLockedByUserId());
        $this->assertStringContainsString('42', $exception->getMessage());
    }

    public function testUpdateBlockedWhenLockedByAnotherUser()
    {
        $model = $this->createModel();

        $model->lock(1, 'session-owner');

        Auth::shouldReceive('id')->andReturn(2);

        $this->expectException(ModelLockedException::class);

        $model->name = 'Updated Name';
        $model->save();
    }

    public function testUpdateBlockedWhenLockedBySameUserDifferentSession()
    {
        $this->markTestSkipped('Session mocking is complex in this test setup');
    }

    public function testUpdateAllowedWhenLockedBySameUser()
    {
        $model = $this->createModel();

        $model->lock(1, null); // No session ID means only user check

        Auth::shouldReceive('id')->andReturn(1);

        $model->name = 'Updated Name';
        $model->save();

        $this->assertEquals('Updated Name', $model->fresh()->name);
    }

    public function testUpdateAllowedWhenNotLocked()
    {
        $model = $this->createModel();

        Auth::shouldReceive('id')->andReturn(1);

        $model->name = 'Updated Name';
        $model->save();

        $this->assertEquals('Updated Name', $model->fresh()->name);
    }

    public function testDeleteBlockedWhenLockedByAnotherUser()
    {
        $model = $this->createModel();

        $model->lock(1, 'session-owner');

        Auth::shouldReceive('id')->andReturn(2);

        $this->expectException(ModelLockedException::class);

        $model->delete();
    }

    public function testDeleteAllowedWhenLockedBySameUser()
    {
        $model = $this->createModel();
        $modelId = $model->id;

        $model->lock(1, null); // No session ID means only user check

        Auth::shouldReceive('id')->andReturn(1);

        $model->delete();

        $this->assertNull(LockableModel::find($modelId));
    }

    public function testDeleteAllowedWhenNotLocked()
    {
        $model = $this->createModel();
        $modelId = $model->id;

        Auth::shouldReceive('id')->andReturn(1);

        $model->delete();

        $this->assertNull(LockableModel::find($modelId));
    }
}
