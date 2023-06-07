<?php

namespace Tests\Unit;

use Tests\TestCase;
use Grafite\Support\Helpers\Stringy;

class StringyTest extends TestCase
{
    public function testStringyOf()
    {
        $string = (string) Stringy::of('superman');

        $this->assertStringContainsString('superman', $string);
    }

    public function testExtractKeywords()
    {
        $text = "Criteria of compatibility of a system of linear Diophantine equations, strict inequations, and nonstrict inequations are considered. Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.";

        $keywords = Stringy::of($text)->keywords();

        $this->assertEquals('criteria', $keywords->first());
        $this->assertEquals('compatibility', $keywords[1]);
    }

    public function testExtractPhrases()
    {
        $text = "Criteria of compatibility of a system of linear Diophantine equations, strict inequations, and nonstrict inequations are considered. Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.";

        $phrases = Stringy::of($text)->phrases();

        $this->assertEquals('linear diophantine equations', $phrases->first());
        $this->assertEquals('minimal generating sets', $phrases[1]);
    }

    public function testWordCount()
    {
        $text = "Criteria of compatibility of a system of linear Diophantine equations, strict inequations, and nonstrict inequations are considered. Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.";

        $count = Stringy::of($text)->wordCount();

        $this->assertEquals(44, $count);
    }

    public function testCharacterCount()
    {
        $text = "Criteria of compatibility of a system of linear Diophantine equations, strict inequations, and nonstrict inequations are considered. Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.";

        $count = Stringy::of($text)->characterCount();

        $this->assertEquals(297, $count);
    }

    public function testSummary()
    {
        $text = "Criteria of compatibility of a system of linear Diophantine equations, strict inequations, and nonstrict inequations are considered. Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.";

        $summary = Stringy::of($text)->summary();

        $this->assertEquals('Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.', $summary);
    }

    public function testGetKeySentence()
    {
        $text = "Criteria of compatibility of a system of linear Diophantine equations, strict inequations, and nonstrict inequations are considered. Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.";

        $keySentence = Stringy::of($text)->keySentence();

        $this->assertEquals('Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given.', $keySentence);
    }

    public function testInsert()
    {
        $text = 'My name is :name and I am :age years old.';

        $sentence = Stringy::of($text)->insert([
            'name' => 'Bob',
            'age' => '65'
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
}
