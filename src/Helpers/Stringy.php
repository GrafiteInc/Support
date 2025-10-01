<?php

namespace Grafite\Support\Helpers;

use DivineOmega\PHPSummary\SummaryTool;
use DonatelloZa\RakePlus\RakePlus;
use Manny\Manny;
use NXP\MathExecutor;
use Twitter\Text\Extractor;

class Stringy
{
    public static $string;

    public static function of($string)
    {
        self::$string = $string;

        return new static;
    }

    public function keywords()
    {
        return collect(RakePlus::create(self::$string, 'en_US', 8)
            ->keywords());
    }

    public function phrases()
    {
        return collect(RakePlus::create(self::$string)
            ->sortByScore('desc')->get());
    }

    public function wordCount()
    {
        return count(explode(' ', self::$string));
    }

    public function characterCount()
    {
        return strlen(self::$string);
    }

    public function keySentence()
    {
        $string = str_replace("\r\n", "\n\n", strip_tags(self::$string));

        return (new SummaryTool($string))->getSummarySentences()[0];
    }

    public function summary()
    {
        $string = str_replace("\r\n", "\n\n", strip_tags(self::$string));

        return (new SummaryTool($string))->getSummary();
    }

    public function calculate()
    {
        $executor = new MathExecutor();

        return $executor->execute(self::$string);
    }

    public function hashtags()
    {
        $hashtags = [];

        preg_match_all("/#(\w+)/u", self::$string, $matches);

        if (!empty($matches[1])) {
            $hashtags = $matches[1];
        }

        return collect($hashtags);
    }

    public function urls()
    {
        preg_match_all('#\bhttps?://[^,\s()<>]+(?:(\([\w\d]+\)|([^,[:punct:]\s]|/)))#', self::$string, $matches);

        return collect($matches[0]);
    }

    public function mentions()
    {
        preg_match_all('/@([a-zA-Z0-9_]+)/', self::$string, $matches);

        return collect($matches[1]);
    }

    public function ipAddresses()
    {
        preg_match_all('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', self::$string, $ip_matches);

        return collect($ip_matches[0]);
    }

    public function insert($data)
    {
        $string = self::$string;

        foreach ($data as $key => $value) {
            $string = str_replace(":{$key}", $value, $string);
        }

        self::$string = $string;

        return new static;
    }

    public function mask($pattern)
    {
        return Manny::mask(self::$string, $pattern);
    }

    public function asPostalCode()
    {
        return Manny::mask(self::$string, 'A1A 1A1');
    }

    public function asPhone()
    {
        return Manny::phone(self::$string);
    }

    public function asPlainText()
    {
        return self::$string;
    }

    public function __toString()
    {
        return self::$string;
    }
}
