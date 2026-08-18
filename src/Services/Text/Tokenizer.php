<?php

namespace Grafite\Support\Services\Text;

use DonatelloZa\RakePlus\StopwordsPHP;

class Tokenizer
{
    /**
     * Placeholder used to shield periods which do not end a sentence.
     */
    private const PERIOD_PLACEHOLDER = "\x00";

    /**
     * Abbreviations whose trailing period does not end a sentence.
     */
    private const ABBREVIATIONS = [
        'mr', 'mrs', 'ms', 'dr', 'prof', 'rev', 'hon', 'sr', 'jr', 'st',
        'vs', 'etc', 'inc', 'ltd', 'co', 'corp', 'dept', 'est', 'approx',
        'jan', 'feb', 'mar', 'apr', 'jun', 'jul', 'aug', 'sep', 'sept', 'oct', 'nov', 'dec',
        'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun',
        'a.m', 'p.m', 'i.e', 'e.g', 'u.s', 'u.k',
    ];

    /**
     * Words the syllable heuristic gets wrong.
     */
    private const SYLLABLE_EXCEPTIONS = [
        'business' => 2,
        'colonel' => 2,
        'every' => 2,
        'everyone' => 3,
        'everything' => 3,
        'forever' => 3,
        'people' => 2,
        'queue' => 1,
        'shoreline' => 2,
        'simile' => 3,
        'wednesday' => 2,
    ];

    private static ?array $stopwords = null;

    private string $content;

    public function __construct(?string $content)
    {
        $this->content = (string) $content;
    }

    public static function of(?string $content): self
    {
        return new self($content);
    }

    /**
     * Lowercased word tokens with punctuation stripped. Contractions and
     * hyphenated words are kept intact.
     */
    public function words(): array
    {
        $words = preg_split('/[^\p{L}\p{N}\'’-]+/u', mb_strtolower($this->content), -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false) {
            return [];
        }

        $words = array_map(function ($word) {
            return trim($word, "-'’");
        }, $words);

        return array_values(array_filter($words, function ($word) {
            return $word !== '';
        }));
    }

    /**
     * Word tokens with their original casing preserved.
     */
    public function wordsWithCase(): array
    {
        $words = preg_split('/[^\p{L}\p{N}\'’-]+/u', $this->content, -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false) {
            return [];
        }

        $words = array_map(function ($word) {
            return trim($word, "-'’");
        }, $words);

        return array_values(array_filter($words, function ($word) {
            return $word !== '';
        }));
    }

    /**
     * Sentences, including any trailing fragment which lacks terminal
     * punctuation. Abbreviations and decimals are not treated as sentence ends.
     */
    public function sentences(): array
    {
        $content = trim(preg_replace('/\s+/u', ' ', $this->content));

        if ($content === '') {
            return [];
        }

        $shielded = $this->shieldNonTerminalPeriods($content);

        $parts = preg_split('/(?<=[.!?])["\'’”\)\]]*\s+/u', $shielded, -1, PREG_SPLIT_NO_EMPTY);

        if ($parts === false) {
            return [$content];
        }

        $sentences = array_map(function ($part) {
            return trim(str_replace(self::PERIOD_PLACEHOLDER, '.', $part));
        }, $parts);

        return array_values(array_filter($sentences, function ($sentence) {
            return $sentence !== '';
        }));
    }

    public function paragraphs(): array
    {
        $paragraphs = preg_split('/(\r\n|\r|\n)\s*(\r\n|\r|\n)/u', $this->content, -1, PREG_SPLIT_NO_EMPTY);

        if ($paragraphs === false) {
            return [];
        }

        $paragraphs = array_map('trim', $paragraphs);

        return array_values(array_filter($paragraphs, function ($paragraph) {
            return $paragraph !== '';
        }));
    }

    public function withoutStopwords(): array
    {
        $stopwords = self::stopwords();

        return array_values(array_filter($this->words(), function ($word) use ($stopwords) {
            return ! isset($stopwords[$word]);
        }));
    }

    /**
     * Contiguous word sequences of length $n.
     */
    public function nGrams(int $n = 2, bool $withoutStopwords = false): array
    {
        if ($n < 1) {
            return [];
        }

        $words = $withoutStopwords ? $this->withoutStopwords() : $this->words();
        $total = count($words);

        if ($total < $n) {
            return [];
        }

        $nGrams = [];

        for ($i = 0; $i <= $total - $n; $i++) {
            $nGrams[] = implode(' ', array_slice($words, $i, $n));
        }

        return $nGrams;
    }

    /**
     * Word counts keyed by word, ordered most frequent first.
     */
    public function frequency(bool $withoutStopwords = false): array
    {
        $words = $withoutStopwords ? $this->withoutStopwords() : $this->words();

        $counts = array_count_values($words);

        arsort($counts);

        return $counts;
    }

    public function uniqueWords(): array
    {
        return array_values(array_unique($this->words()));
    }

    public function syllableCount(): int
    {
        $count = 0;

        foreach ($this->words() as $word) {
            $count += self::syllables($word);
        }

        return $count;
    }

    /**
     * Words of three or more syllables, the usual proxy for "complex" in
     * readability formulas.
     */
    public function complexWords(): array
    {
        return array_values(array_filter($this->words(), function ($word) {
            return self::syllables($word) >= 3;
        }));
    }

    /**
     * Vowel-group syllable heuristic with silent-e handling.
     */
    public static function syllables(string $word): int
    {
        $word = preg_replace('/[^a-z]/', '', mb_strtolower($word));

        if ($word === '') {
            return 0;
        }

        if (isset(self::SYLLABLE_EXCEPTIONS[$word])) {
            return self::SYLLABLE_EXCEPTIONS[$word];
        }

        if (strlen($word) <= 3) {
            return 1;
        }

        // Drop silent endings, but keep those which carry their own vowel sound.
        $trimmed = preg_replace('/(?:[^laeiouy]es|[^laeiouy]e)$/', '', $word);
        $trimmed = preg_replace('/^y/', '', $trimmed);

        preg_match_all('/[aeiouy]{1,2}/', $trimmed, $matches);

        return max(1, count($matches[0]));
    }

    /**
     * The en_US stopword list, keyed by word for O(1) lookups.
     */
    public static function stopwords(): array
    {
        if (self::$stopwords === null) {
            self::$stopwords = array_fill_keys(
                array_map('mb_strtolower', StopwordsPHP::createFromLanguage('en_US')->stopwords()),
                true
            );
        }

        return self::$stopwords;
    }

    private function shieldNonTerminalPeriods(string $content): string
    {
        $abbreviations = implode('|', array_map('preg_quote', self::ABBREVIATIONS));

        $content = preg_replace_callback(
            '/\b('.$abbreviations.')\./i',
            function ($matches) {
                return $matches[1].self::PERIOD_PLACEHOLDER;
            },
            $content
        );

        // Decimals and dotted initials such as "J. R. R."
        $content = preg_replace('/(\d)\.(\d)/', '$1'.self::PERIOD_PLACEHOLDER.'$2', $content);
        $content = preg_replace('/\b([A-Z])\.(?=\s*[A-Z]\.)/', '$1'.self::PERIOD_PLACEHOLDER, $content);

        return $content;
    }
}
