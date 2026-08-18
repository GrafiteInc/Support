<?php

namespace Grafite\Support\Services\Text;

class Lexicon
{
    /**
     * Loaded lists, keyed by name.
     */
    private static array $cache = [];

    /**
     * Sentiment valences on the -4 to +4 VADER scale.
     */
    public static function sentiment(): array
    {
        return self::load('sentiment');
    }

    /**
     * Degree modifiers which intensify or dampen the word that follows.
     */
    public static function boosters(): array
    {
        return self::load('boosters');
    }

    /**
     * Emoticon valences, matched before tokenizing.
     */
    public static function emoticons(): array
    {
        return self::load('emoticons');
    }

    /**
     * Load a bundled list, applying any configured replacement or additions.
     */
    public static function load(string $name): array
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        $path = self::config("stringy.{$name}.lexicon") ?: self::path($name);

        $lexicon = is_string($path) && is_readable($path) ? require $path : [];

        $additions = self::config("stringy.{$name}.additions") ?: [];

        if (is_array($additions) && $additions !== []) {
            $lexicon = array_merge($lexicon, array_change_key_case($additions, CASE_LOWER));
        }

        return self::$cache[$name] = $lexicon;
    }

    public static function path(string $name): string
    {
        return __DIR__.'/../../../resources/lexicons/'.$name.'.php';
    }

    /**
     * Drop cached lists so configuration changes take effect. Mainly useful
     * in tests.
     */
    public static function flush(): void
    {
        self::$cache = [];
    }

    /**
     * Read config when running inside Laravel, and fall back to the bundled
     * defaults when Stringy is used standalone.
     */
    private static function config(string $key)
    {
        if (! function_exists('config')) {
            return null;
        }

        try {
            return config($key);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
