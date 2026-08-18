<?php

namespace Tests\Unit;

use Grafite\Support\Services\Text\Tokenizer;
use Tests\TestCase;

class TokenizerTest extends TestCase
{
    public function testWordsAreLowercasedAndStrippedOfPunctuation()
    {
        $words = (new Tokenizer('Hello, World! This is a test.'))->words();

        $this->assertEquals(['hello', 'world', 'this', 'is', 'a', 'test'], $words);
    }

    public function testWordsKeepContractionsAndHyphens()
    {
        $words = (new Tokenizer("It's a well-known fact, isn't it?"))->words();

        $this->assertEquals(["it's", 'a', 'well-known', 'fact', "isn't", 'it'], $words);
    }

    public function testWordsWithCasePreservesOriginalCasing()
    {
        $words = (new Tokenizer('The NASA Rover Landed'))->wordsWithCase();

        $this->assertEquals(['The', 'NASA', 'Rover', 'Landed'], $words);
    }

    public function testWordsOnEmptyString()
    {
        $this->assertEquals([], (new Tokenizer(''))->words());
        $this->assertEquals([], (new Tokenizer(null))->words());
    }

    public function testSentencesSplitOnTerminalPunctuation()
    {
        $sentences = (new Tokenizer('One thing happened. Another thing happened! Did it? Yes.'))->sentences();

        $this->assertCount(4, $sentences);
        $this->assertEquals('One thing happened.', $sentences[0]);
        $this->assertEquals('Did it?', $sentences[2]);
    }

    public function testSentencesKeepTrailingFragmentWithoutPunctuation()
    {
        $sentences = (new Tokenizer('First sentence. Second one has no terminator'))->sentences();

        $this->assertCount(2, $sentences);
        $this->assertEquals('Second one has no terminator', $sentences[1]);
    }

    public function testSentencesTreatUnpunctuatedTextAsOneSentence()
    {
        $sentences = (new Tokenizer('Rover lands on Mars after seven month journey'))->sentences();

        $this->assertCount(1, $sentences);
    }

    public function testSentencesIgnoreAbbreviationsAndDecimals()
    {
        $sentences = (new Tokenizer('Dr. Smith arrived at 3.5 p.m. on Tuesday. He left early.'))->sentences();

        $this->assertCount(2, $sentences);
        $this->assertEquals('Dr. Smith arrived at 3.5 p.m. on Tuesday.', $sentences[0]);
    }

    public function testSentencesOnEmptyString()
    {
        $this->assertEquals([], (new Tokenizer('   '))->sentences());
    }

    public function testParagraphs()
    {
        $paragraphs = (new Tokenizer("First para.\n\nSecond para.\n\n\nThird para."))->paragraphs();

        $this->assertCount(3, $paragraphs);
        $this->assertEquals('Second para.', $paragraphs[1]);
    }

    public function testWithoutStopwords()
    {
        $words = (new Tokenizer('The quick brown fox jumps over the lazy dog'))->withoutStopwords();

        $this->assertNotContains('the', $words);
        $this->assertNotContains('over', $words);
        $this->assertContains('quick', $words);
        $this->assertContains('fox', $words);
    }

    public function testNGrams()
    {
        $bigrams = (new Tokenizer('the quick brown fox'))->nGrams(2);

        $this->assertEquals(['the quick', 'quick brown', 'brown fox'], $bigrams);

        $trigrams = (new Tokenizer('the quick brown fox'))->nGrams(3);

        $this->assertEquals(['the quick brown', 'quick brown fox'], $trigrams);
    }

    public function testNGramsReturnEmptyWhenTextIsShorterThanWindow()
    {
        $this->assertEquals([], (new Tokenizer('single'))->nGrams(3));
        $this->assertEquals([], (new Tokenizer('anything'))->nGrams(0));
    }

    public function testNGramsCanSkipStopwords()
    {
        $bigrams = (new Tokenizer('the quick brown fox'))->nGrams(2, true);

        $this->assertEquals(['quick brown', 'brown fox'], $bigrams);
    }

    public function testFrequencyIsOrderedByCount()
    {
        $frequency = (new Tokenizer('dog cat dog bird dog cat'))->frequency();

        $this->assertEquals(3, $frequency['dog']);
        $this->assertEquals(2, $frequency['cat']);
        $this->assertEquals('dog', array_key_first($frequency));
    }

    public function testUniqueWords()
    {
        $unique = (new Tokenizer('dog cat dog bird'))->uniqueWords();

        $this->assertEquals(['dog', 'cat', 'bird'], $unique);
    }

    public function testSyllableCounting()
    {
        $this->assertEquals(1, Tokenizer::syllables('cat'));
        $this->assertEquals(1, Tokenizer::syllables('the'));
        $this->assertEquals(2, Tokenizer::syllables('hello'));
        $this->assertEquals(2, Tokenizer::syllables('table'));
        $this->assertEquals(2, Tokenizer::syllables('people'));
        $this->assertEquals(4, Tokenizer::syllables('beautiful'));
        $this->assertEquals(5, Tokenizer::syllables('readability'));
        $this->assertEquals(0, Tokenizer::syllables(''));
        $this->assertEquals(0, Tokenizer::syllables('123'));
    }

    public function testSyllableCountForWholeString()
    {
        // the(1) cat(1) sat(1) on(1) hello(2)
        $this->assertEquals(6, (new Tokenizer('the cat sat on hello'))->syllableCount());
    }

    public function testComplexWords()
    {
        $complex = (new Tokenizer('The beautiful readability of simple cats'))->complexWords();

        $this->assertContains('beautiful', $complex);
        $this->assertContains('readability', $complex);
        $this->assertNotContains('cats', $complex);
    }

    public function testStopwordsAreLoadedAndCached()
    {
        $stopwords = Tokenizer::stopwords();

        $this->assertArrayHasKey('the', $stopwords);
        $this->assertArrayHasKey('and', $stopwords);
        $this->assertArrayNotHasKey('diophantine', $stopwords);
        $this->assertSame($stopwords, Tokenizer::stopwords());
    }
}
