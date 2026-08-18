<?php

namespace Tests\Unit;

use Grafite\Support\Helpers\Stringy;
use Tests\TestCase;

class StringyTest extends TestCase
{
    public function testStringyOf()
    {
        $string = (string) Stringy::of('superman');

        $this->assertStringContainsString('superman', $string);
    }

    public function testExtractKeywords()
    {
        $text = 'Criteria of compatibility of a system of linear Diophantine equations, strict inequations, and nonstrict inequations are considered. Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.';

        $keywords = Stringy::of($text)->keywords();

        $this->assertEquals('criteria', $keywords->first());
        $this->assertEquals('compatibility', $keywords[1]);
    }

    public function testExtractPhrases()
    {
        $text = 'Criteria of compatibility of a system of linear Diophantine equations, strict inequations, and nonstrict inequations are considered. Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.';

        $phrases = Stringy::of($text)->phrases();

        $this->assertEquals('linear diophantine equations', $phrases->first());
        $this->assertEquals('minimal generating sets', $phrases[1]);
    }

    public function testWordCount()
    {
        $text = 'Criteria of compatibility of a system of linear Diophantine equations, strict inequations, and nonstrict inequations are considered. Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.';

        $count = Stringy::of($text)->wordCount();

        $this->assertEquals(44, $count);
    }

    public function testCharacterCount()
    {
        $text = 'Criteria of compatibility of a system of linear Diophantine equations, strict inequations, and nonstrict inequations are considered. Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.';

        $count = Stringy::of($text)->characterCount();

        $this->assertEquals(297, $count);
    }

    public function testSummary()
    {
        $text = 'Criteria of compatibility of a system of linear Diophantine equations, strict inequations, and nonstrict inequations are considered. Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.';

        $summary = Stringy::of($text)->summary();

        $this->assertEquals('Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.', $summary);
    }

    public function testGetKeySentence()
    {
        $text = 'Criteria of compatibility of a system of linear Diophantine equations, strict inequations, and nonstrict inequations are considered. Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.';

        $keySentence = Stringy::of($text)->keySentence();

        $this->assertEquals('Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.', $keySentence);
    }

    public function testInsert()
    {
        $text = 'My name is :name and I am :age years old.';

        $sentence = Stringy::of($text)->insert([
            'name' => 'Bob',
            'age' => '65',
        ])->asPlainText();

        $this->assertEquals('My name is Bob and I am 65 years old.', $sentence);
    }

    public function testGetHashtags()
    {
        $text = 'My name is Bob and I am 65 years old. #livingLarge #wickedThoughts https://twt.co/superman';

        $hashtags = Stringy::of($text)->hashtags();

        $this->assertEquals('livingLarge', $hashtags->first());
    }

    public function testUrls()
    {
        $text = 'My name is Bob and I am 65 years old. #livingLarge #wickedThoughts https://twt.co/superman';

        $urls = Stringy::of($text)->urls();

        $this->assertEquals('https://twt.co/superman', $urls->first());
    }

    public function testMentions()
    {
        $text = 'My name is @Bob and I am 65 years old, my friend is @alice. #livingLarge #wickedThoughts https://twt.co/superman';

        $mentions = Stringy::of($text)->mentions();

        $this->assertEquals('Bob', $mentions->first());
        $this->assertEquals('alice', $mentions[1]);
    }

    public function testIpAddressExtraction()
    {
        $text = '2014-06-02 11:53:54.410 -0700   Information 638 NICOLE  Client "123456" opening a connection from "123456.local (207.230.229.204)" using "Go 13.0.4 [fmapp]".
2014-06-02 11:54:52.504 -0700   Information 98  NICOLE  Client "123456 (123456.local) [207.230.229.205]" closing database "FMServer_Sample" as "Admin".
2014-06-02 12:07:33.433 -0700   Information 638 NICOLE  Client "[WebDirect]" opening a connection from "207.230.229.208 (207.230.229.208)" using "Win Chrome 35.0 [fmwebdirect]".
2014-06-02 13:05:00.088 -0700   Information 638 NICOLE  Client "Showare Update" opening a connection from "FileMaker Script" using "Server 13.0v1 [fmapp]".
2014-06-02 13:05:22.366 -0700   Information 98  NICOLE  Client "Showare Update (FileMaker Script)" closing database "cac" as "opus".
2014-06-02 12:08:04.165 -0700   Information 98  NICOLE  Client "[WebDirect] (207.230.229.209) [207.230.229.209]" closing database "FMServer_Sample" as "Admin".';

        $ips = Stringy::of($text)->ipAddresses();

        $this->assertEquals('207.230.229.204', $ips->first());
        $this->assertEquals('207.230.229.205', $ips[1]);
    }

    public function testCalculation()
    {
        $text = '8 * 8 / 16 / 4';

        $result = Stringy::of($text)->calculate();

        $this->assertEquals(1, $result);
    }

    public function testMask()
    {
        $text = 'what is 72 89 0';

        $result = Stringy::of($text)->mask('11111');

        $this->assertEquals('72890', $result);
    }

    public function testAsPostalCode()
    {
        $text = 'K8f3G4';

        $result = Stringy::of($text)->asPostalCode();

        $this->assertEquals('K8f 3G4', $result);
    }

    public function testAsPhone()
    {
        $text = '8887776543';

        $result = Stringy::of($text)->asPhone();

        $this->assertEquals('888-777-6543', $result);
    }

    public function testEmailDomains()
    {
        $text = 'Contact us at support@example.com or sales@company.org for more info. You can also reach admin@test.io';

        $domains = Stringy::of($text)->emailDomains();

        $this->assertCount(3, $domains);
        $this->assertEquals('example.com', $domains->first());
        $this->assertEquals('company.org', $domains[1]);
        $this->assertEquals('test.io', $domains[2]);
    }

    public function testWords()
    {
        $words = Stringy::of('Hello, World! This is a test.')->words();

        $this->assertCount(6, $words);
        $this->assertEquals('hello', $words->first());
    }

    public function testSentences()
    {
        $sentences = Stringy::of('One thing happened. Another thing happened!')->sentences();

        $this->assertCount(2, $sentences);
        $this->assertEquals('One thing happened.', $sentences->first());
    }

    public function testParagraphs()
    {
        $paragraphs = Stringy::of("First para.\n\nSecond para.")->paragraphs();

        $this->assertCount(2, $paragraphs);
    }

    public function testUniqueWords()
    {
        $unique = Stringy::of('dog cat dog bird')->uniqueWords();

        $this->assertCount(3, $unique);
    }

    public function testWithoutStopwords()
    {
        $words = Stringy::of('The quick brown fox')->withoutStopwords();

        $this->assertFalse($words->contains('the'));
        $this->assertTrue($words->contains('quick'));
    }

    public function testNGrams()
    {
        $bigrams = Stringy::of('the quick brown fox')->nGrams(2);

        $this->assertEquals('the quick', $bigrams->first());
        $this->assertCount(3, $bigrams);
    }

    public function testWordFrequency()
    {
        $frequency = Stringy::of('dog cat dog bird dog')->wordFrequency();

        $this->assertEquals(3, $frequency['dog']);
        $this->assertEquals('dog', $frequency->keys()->first());
    }

    public function testSyllableCount()
    {
        $this->assertEquals(6, Stringy::of('the cat sat on hello')->syllableCount());
    }

    public function testReadability()
    {
        $readability = Stringy::of('The cat sat on the mat. The dog ran fast.')->readability();

        $this->assertEquals(117.16, $readability['fleschReadingEase']);
        $this->assertEquals('Very Easy', $readability['interpretation']);
        $this->assertEquals(10, $readability['words']);
    }

    public function testReadingEaseAndGradeLevel()
    {
        $text = 'The cat sat on the mat. The dog ran fast.';

        $this->assertEquals(117.16, Stringy::of($text)->readingEase());
        $this->assertLessThan(1, Stringy::of($text)->gradeLevel());
    }

    public function testLexicalDensityAndTypeTokenRatio()
    {
        $text = 'The cat sat on the mat. The dog ran fast.';

        $this->assertEquals(0.6, Stringy::of($text)->lexicalDensity());
        $this->assertEquals(0.8, Stringy::of($text)->typeTokenRatio());
    }

    public function testReadabilityIgnoresMarkup()
    {
        $withTags = Stringy::of('<p>The cat sat on the mat.</p> <p>The dog ran fast.</p>')->readability();

        $this->assertEquals(10, $withTags['words']);
        $this->assertEquals(2, $withTags['sentences']);
    }

    public function testSentiment()
    {
        $sentiment = Stringy::of('The food is good.')->sentiment();

        $this->assertEquals(0.4404, $sentiment['compound']);
        $this->assertGreaterThan(0, $sentiment['positive']);
        $this->assertEquals(0, $sentiment['negative']);
    }

    public function testSentimentScoreAndLabel()
    {
        $this->assertEquals(0.4404, Stringy::of('The food is good.')->sentimentScore());
        $this->assertEquals('positive', Stringy::of('The food is good.')->sentimentLabel());
        $this->assertEquals('negative', Stringy::of('The food is horrible.')->sentimentLabel());
        $this->assertEquals('neutral', Stringy::of('The meeting is at noon.')->sentimentLabel());
    }

    public function testSentimentBySentence()
    {
        $results = Stringy::of('The food was great. The service was terrible.')->sentimentBySentence();

        $this->assertCount(2, $results);
        $this->assertGreaterThan(0, $results->first()['compound']);
        $this->assertLessThan(0, $results->last()['compound']);
    }

    public function testSentimentIgnoresMarkup()
    {
        $this->assertEquals(
            Stringy::of('The food is good.')->sentimentScore(),
            Stringy::of('<p>The food is <em>good</em>.</p>')->sentimentScore()
        );
    }

    public function testReadTime()
    {
        // 200 words = 1 minute read time
        $text = trim(str_repeat('word ', 200));

        $readTime = Stringy::of($text)->readTime();

        $this->assertEquals(1, $readTime);

        // 201 words should round up to 2 minutes
        $text = trim(str_repeat('word ', 201));

        $readTime = Stringy::of($text)->readTime();

        $this->assertEquals(2, $readTime);

        // 50 words should round up to 1 minute
        $text = trim(str_repeat('word ', 50));

        $readTime = Stringy::of($text)->readTime();

        $this->assertEquals(1, $readTime);
    }
}
