<?php

namespace Tests\Unit;

use Grafite\Support\Services\Text\Readability;
use Grafite\Support\Services\Text\Tokenizer;
use Tests\TestCase;

class ReadabilityTest extends TestCase
{
    /**
     * 10 words, 2 sentences, 10 syllables. Chosen so every formula can be
     * worked out by hand.
     */
    private const SIMPLE = 'The cat sat on the mat. The dog ran fast.';

    private function readability(string $text): Readability
    {
        return new Readability(new Tokenizer($text));
    }

    public function testCounts()
    {
        $readability = $this->readability(self::SIMPLE);

        $this->assertEquals(10, $readability->wordCount());
        $this->assertEquals(2, $readability->sentenceCount());
        $this->assertEquals(10, $readability->syllableCount());
        $this->assertEquals(0, $readability->complexWordCount());
        $this->assertEquals(5.0, $readability->averageSentenceLength());
        $this->assertEquals(1.0, $readability->averageSyllablesPerWord());
    }

    public function testFleschReadingEase()
    {
        // 206.835 - (1.015 * 5) - (84.6 * 1) = 117.16
        $this->assertEquals(117.16, $this->readability(self::SIMPLE)->fleschReadingEase());
    }

    public function testFleschKincaidGrade()
    {
        // (0.39 * 5) + (11.8 * 1) - 15.59 = -1.84
        $this->assertEquals(-1.84, $this->readability(self::SIMPLE)->fleschKincaidGrade());
    }

    public function testGunningFog()
    {
        // 0.4 * (5 + (100 * 0)) = 2.0
        $this->assertEquals(2.0, $this->readability(self::SIMPLE)->gunningFog());
    }

    public function testAutomatedReadabilityIndex()
    {
        // letters = 30, words = 10 => (4.71 * 3) + (0.5 * 5) - 21.43 = -4.8
        $this->assertEquals(-4.8, $this->readability(self::SIMPLE)->automatedReadabilityIndex());
    }

    public function testDenseProseScoresHarderThanSimpleProse()
    {
        $dense = 'Criteria of compatibility of a system of linear Diophantine equations, strict inequations, and nonstrict inequations are considered.';

        $this->assertLessThan(
            $this->readability(self::SIMPLE)->fleschReadingEase(),
            $this->readability($dense)->fleschReadingEase()
        );

        $this->assertGreaterThan(
            $this->readability(self::SIMPLE)->gradeLevel(),
            $this->readability($dense)->gradeLevel()
        );
    }

    public function testInterpretation()
    {
        $this->assertEquals('Very Easy', $this->readability(self::SIMPLE)->interpretation());

        $dense = 'Criteria of compatibility of a system of linear Diophantine equations, strict inequations, and nonstrict inequations are considered.';

        $this->assertEquals('Very Confusing', $this->readability($dense)->interpretation());
    }

    public function testLexicalDensity()
    {
        // "cat", "sat", "mat", "dog", "ran", "fast" carry meaning; "the" and "on" do not.
        $this->assertEquals(0.6, $this->readability(self::SIMPLE)->lexicalDensity());
    }

    public function testTypeTokenRatio()
    {
        // 8 unique words across 10 tokens ("the" repeats three times).
        $this->assertEquals(0.8, $this->readability(self::SIMPLE)->typeTokenRatio());
    }

    public function testEmptyStringReturnsZeroesRatherThanDividingByZero()
    {
        $readability = $this->readability('');

        $this->assertEquals(0.0, $readability->fleschReadingEase());
        $this->assertEquals(0.0, $readability->fleschKincaidGrade());
        $this->assertEquals(0.0, $readability->gunningFog());
        $this->assertEquals(0.0, $readability->smog());
        $this->assertEquals(0.0, $readability->colemanLiau());
        $this->assertEquals(0.0, $readability->automatedReadabilityIndex());
        $this->assertEquals(0.0, $readability->gradeLevel());
        $this->assertEquals(0.0, $readability->lexicalDensity());
        $this->assertEquals(0.0, $readability->typeTokenRatio());
        $this->assertEquals(0.0, $readability->averageSentenceLength());
        $this->assertEquals(0.0, $readability->averageSyllablesPerWord());
    }

    public function testHeadlineWithoutPunctuationIsStillScored()
    {
        $readability = $this->readability('Rover lands on Mars after seven month journey');

        $this->assertEquals(1, $readability->sentenceCount());
        $this->assertGreaterThan(0, $readability->fleschReadingEase());
    }

    public function testAllReturnsEveryMetric()
    {
        $all = $this->readability(self::SIMPLE)->all();

        $this->assertArrayHasKey('fleschReadingEase', $all);
        $this->assertArrayHasKey('gunningFog', $all);
        $this->assertArrayHasKey('smog', $all);
        $this->assertArrayHasKey('colemanLiau', $all);
        $this->assertArrayHasKey('gradeLevel', $all);
        $this->assertArrayHasKey('interpretation', $all);
        $this->assertArrayHasKey('lexicalDensity', $all);
        $this->assertEquals(10, $all['words']);
    }

    public function testOfHelper()
    {
        $this->assertEquals(117.16, Readability::of(self::SIMPLE)->fleschReadingEase());
    }
}
