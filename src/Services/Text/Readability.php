<?php

namespace Grafite\Support\Services\Text;

class Readability
{
    private Tokenizer $tokenizer;

    private ?array $cache = null;

    public function __construct(Tokenizer $tokenizer)
    {
        $this->tokenizer = $tokenizer;
    }

    public static function of(?string $content): self
    {
        return new self(new Tokenizer($content));
    }

    /**
     * Flesch Reading Ease. Higher is easier; 0-100 in practice, though the
     * formula is unbounded at both ends.
     */
    public function fleschReadingEase(): float
    {
        if ($this->isEmpty()) {
            return 0.0;
        }

        $score = 206.835
            - (1.015 * $this->averageSentenceLength())
            - (84.6 * $this->averageSyllablesPerWord());

        return $this->round($score);
    }

    /**
     * Flesch-Kincaid Grade Level, expressed as a US school grade.
     */
    public function fleschKincaidGrade(): float
    {
        if ($this->isEmpty()) {
            return 0.0;
        }

        $score = (0.39 * $this->averageSentenceLength())
            + (11.8 * $this->averageSyllablesPerWord())
            - 15.59;

        return $this->round($score);
    }

    /**
     * Gunning Fog index, the years of formal education a reader needs.
     */
    public function gunningFog(): float
    {
        if ($this->isEmpty()) {
            return 0.0;
        }

        $complexRatio = $this->complexWordCount() / $this->wordCount();

        $score = 0.4 * ($this->averageSentenceLength() + (100 * $complexRatio));

        return $this->round($score);
    }

    /**
     * SMOG grade. Designed for texts of 30+ sentences; shorter samples are
     * still scored but the result is less reliable.
     */
    public function smog(): float
    {
        if ($this->isEmpty()) {
            return 0.0;
        }

        $score = (1.0430 * sqrt($this->complexWordCount() * (30 / $this->sentenceCount()))) + 3.1291;

        return $this->round($score);
    }

    /**
     * Coleman-Liau index, which uses characters rather than syllables.
     */
    public function colemanLiau(): float
    {
        if ($this->isEmpty()) {
            return 0.0;
        }

        $lettersPerHundred = ($this->letterCount() / $this->wordCount()) * 100;
        $sentencesPerHundred = ($this->sentenceCount() / $this->wordCount()) * 100;

        $score = (0.0588 * $lettersPerHundred) - (0.296 * $sentencesPerHundred) - 15.8;

        return $this->round($score);
    }

    /**
     * Automated Readability Index, also character based.
     */
    public function automatedReadabilityIndex(): float
    {
        if ($this->isEmpty()) {
            return 0.0;
        }

        $score = (4.71 * ($this->letterCount() / $this->wordCount()))
            + (0.5 * $this->averageSentenceLength())
            - 21.43;

        return $this->round($score);
    }

    /**
     * Consensus grade level: the mean of the grade-based formulas.
     */
    public function gradeLevel(): float
    {
        if ($this->isEmpty()) {
            return 0.0;
        }

        $grades = [
            $this->fleschKincaidGrade(),
            $this->gunningFog(),
            $this->smog(),
            $this->colemanLiau(),
            $this->automatedReadabilityIndex(),
        ];

        return $this->round(array_sum($grades) / count($grades));
    }

    /**
     * Plain language reading of the Flesch Reading Ease score.
     */
    public function interpretation(): string
    {
        $score = $this->fleschReadingEase();

        return match (true) {
            $score >= 90 => 'Very Easy',
            $score >= 80 => 'Easy',
            $score >= 70 => 'Fairly Easy',
            $score >= 60 => 'Standard',
            $score >= 50 => 'Fairly Difficult',
            $score >= 30 => 'Difficult',
            default => 'Very Confusing',
        };
    }

    public function wordCount(): int
    {
        return $this->metrics()['words'];
    }

    public function sentenceCount(): int
    {
        return $this->metrics()['sentences'];
    }

    public function syllableCount(): int
    {
        return $this->metrics()['syllables'];
    }

    public function complexWordCount(): int
    {
        return $this->metrics()['complexWords'];
    }

    public function letterCount(): int
    {
        return $this->metrics()['letters'];
    }

    public function averageSentenceLength(): float
    {
        if ($this->isEmpty()) {
            return 0.0;
        }

        return $this->wordCount() / $this->sentenceCount();
    }

    public function averageSyllablesPerWord(): float
    {
        if ($this->isEmpty()) {
            return 0.0;
        }

        return $this->syllableCount() / $this->wordCount();
    }

    /**
     * Share of words carrying meaning rather than grammatical structure.
     * Higher values read as denser, more information packed prose.
     */
    public function lexicalDensity(): float
    {
        if ($this->isEmpty()) {
            return 0.0;
        }

        return $this->round($this->metrics()['contentWords'] / $this->wordCount(), 4);
    }

    /**
     * Vocabulary richness: unique words over total words. Falls naturally as
     * a text gets longer, so only compare samples of similar length.
     */
    public function typeTokenRatio(): float
    {
        if ($this->isEmpty()) {
            return 0.0;
        }

        return $this->round($this->metrics()['uniqueWords'] / $this->wordCount(), 4);
    }

    public function all(): array
    {
        return [
            'fleschReadingEase' => $this->fleschReadingEase(),
            'fleschKincaidGrade' => $this->fleschKincaidGrade(),
            'gunningFog' => $this->gunningFog(),
            'smog' => $this->smog(),
            'colemanLiau' => $this->colemanLiau(),
            'automatedReadabilityIndex' => $this->automatedReadabilityIndex(),
            'gradeLevel' => $this->gradeLevel(),
            'interpretation' => $this->interpretation(),
            'words' => $this->wordCount(),
            'sentences' => $this->sentenceCount(),
            'syllables' => $this->syllableCount(),
            'complexWords' => $this->complexWordCount(),
            'averageSentenceLength' => $this->round($this->averageSentenceLength()),
            'averageSyllablesPerWord' => $this->round($this->averageSyllablesPerWord()),
            'lexicalDensity' => $this->lexicalDensity(),
            'typeTokenRatio' => $this->typeTokenRatio(),
        ];
    }

    /**
     * Every formula walks the same tokens, so tokenize once and reuse.
     */
    private function metrics(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $words = $this->tokenizer->words();
        $stopwords = Tokenizer::stopwords();

        $syllables = 0;
        $complexWords = 0;
        $letters = 0;
        $contentWords = 0;

        foreach ($words as $word) {
            $wordSyllables = Tokenizer::syllables($word);

            $syllables += $wordSyllables;
            $letters += mb_strlen($word);

            if ($wordSyllables >= 3) {
                $complexWords++;
            }

            if (! isset($stopwords[$word])) {
                $contentWords++;
            }
        }

        return $this->cache = [
            'words' => count($words),
            'sentences' => count($this->tokenizer->sentences()),
            'syllables' => $syllables,
            'complexWords' => $complexWords,
            'letters' => $letters,
            'contentWords' => $contentWords,
            'uniqueWords' => count(array_unique($words)),
        ];
    }

    private function isEmpty(): bool
    {
        $metrics = $this->metrics();

        return $metrics['words'] === 0 || $metrics['sentences'] === 0;
    }

    private function round(float $value, int $precision = 2): float
    {
        return round($value, $precision);
    }
}
