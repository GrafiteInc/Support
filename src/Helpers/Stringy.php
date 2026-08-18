<?php

namespace Grafite\Support\Helpers;

use Grafite\Support\Services\SummaryTool;
use Grafite\Support\Services\Text\Readability;
use Grafite\Support\Services\Text\Sentiment;
use Grafite\Support\Services\Text\Tokenizer;
use DonatelloZa\RakePlus\RakePlus;
use Manny\Manny;
use NXP\MathExecutor;

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

    public function readTime() : int
    {
        $wordsPerMinute = 200;
        $wordCount = $this->wordCount();

        return (int) ceil($wordCount / $wordsPerMinute);
    }

    public function characterCount()
    {
        return strlen(self::$string);
    }

    public function words()
    {
        return collect($this->tokenizer()->words());
    }

    public function sentences()
    {
        return collect($this->tokenizer()->sentences());
    }

    public function paragraphs()
    {
        return collect($this->tokenizer()->paragraphs());
    }

    public function uniqueWords()
    {
        return collect($this->tokenizer()->uniqueWords());
    }

    public function withoutStopwords()
    {
        return collect($this->tokenizer()->withoutStopwords());
    }

    public function nGrams($size = 2, $withoutStopwords = false)
    {
        return collect($this->tokenizer()->nGrams($size, $withoutStopwords));
    }

    public function wordFrequency($withoutStopwords = false)
    {
        return collect($this->tokenizer()->frequency($withoutStopwords));
    }

    public function syllableCount()
    {
        return $this->tokenizer()->syllableCount();
    }

    public function readability()
    {
        return collect($this->readabilityService()->all());
    }

    public function readingEase()
    {
        return $this->readabilityService()->fleschReadingEase();
    }

    public function gradeLevel()
    {
        return $this->readabilityService()->gradeLevel();
    }

    public function lexicalDensity()
    {
        return $this->readabilityService()->lexicalDensity();
    }

    public function typeTokenRatio()
    {
        return $this->readabilityService()->typeTokenRatio();
    }

    public function sentiment()
    {
        return collect($this->sentimentService()->scores());
    }

    public function sentimentScore()
    {
        return $this->sentimentService()->compound();
    }

    public function sentimentLabel()
    {
        return $this->sentimentService()->label();
    }

    public function sentimentBySentence()
    {
        return collect($this->sentimentService()->bySentence());
    }

    protected function tokenizer()
    {
        return new Tokenizer(strip_tags(self::$string));
    }

    protected function sentimentService()
    {
        return new Sentiment(strip_tags(self::$string));
    }

    protected function readabilityService()
    {
        return new Readability($this->tokenizer());
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

    public function emailDomains()
    {
        preg_match_all('/[a-zA-Z0-9._%+-]+@([a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', self::$string, $matches);

        return collect($matches[1]);
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
