<?php

namespace Grafite\Support\Helpers;

class Version
{
    public static function release()
    {
        $build = self::build();

        try {
            if (base_path('changelog.md')) {
                $version = preg_match("/## (.*?) -/", file_get_contents(base_path('changelog.md')), $matches) ? str_replace('[', '', str_replace(']', '', $matches[1])) : "";
            }

            if (base_path('CHANGELOG.md')) {
                $version = preg_match("/## (.*?) -/", file_get_contents(base_path('CHANGELOG.md')), $matches) ? str_replace('[', '', str_replace(']', '', $matches[1])) : "";
            }
        } catch (\Throwable $th) {
            $version = '1.0.0';
        }

        return $version.'-'.$build ?? '1.0.0';
    }

    public static function build()
    {
        $iteration = '';

        try {
            $files = \scandir(base_path('public/build/assets/'));

            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $iteration .= $file;
                }
            }
        } catch (\Throwable $th) {
            $iteration = '1';
        }

        $build = hash('crc32', $iteration);

        return $build;
    }
}
