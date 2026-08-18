<?php

namespace Tests\Unit;

use Grafite\Support\Services\Text\Lexicon;
use Grafite\Support\Services\Text\Sentiment;
use Tests\TestCase;

class SentimentTest extends TestCase
{
    protected function tearDown(): void
    {
        Lexicon::flush();

        parent::tearDown();
    }

    private function compound(string $text): float
    {
        return (new Sentiment($text))->compound();
    }

    /**
     * These figures are the reference VADER outputs for the same inputs, so
     * they pin the rules rather than just the current behaviour.
     */
    public function testMatchesReferenceVaderScores()
    {
        $this->assertEquals(0.4404, $this->compound('The food is good.'));
        $this->assertEquals(0.5622, $this->compound('The food is GOOD.'));
        $this->assertEquals(0.4927, $this->compound('The food is very good.'));
        $this->assertEquals(0.4926, $this->compound('The food is good!'));
        $this->assertEquals(-0.3412, $this->compound('The food is not good.'));
        $this->assertEquals(-0.5859, $this->compound('The food is good, but the service is horrible.'));
        $this->assertEquals(-0.8211, $this->compound('A really bad, horrible book.'));
        $this->assertEquals(0.3832, $this->compound('The book was kind of good.'));
        $this->assertEquals(-0.5461, $this->compound('Today SUX!'));
    }

    public function testNegationFlipsPolarity()
    {
        $this->assertGreaterThan(0, $this->compound('The service was good.'));
        $this->assertLessThan(0, $this->compound('The service was not good.'));
        $this->assertLessThan(0, $this->compound("The service wasn't good."));
    }

    public function testNegationReachesBackSeveralWords()
    {
        $this->assertLessThan(0, $this->compound('It was not very good.'));
        $this->assertLessThan(0, $this->compound('This is not at all good.'));
    }

    /**
     * Negation only looks back three words, so a negation further away than
     * that is missed. This is the documented VADER window, not an oversight.
     */
    public function testNegationDoesNotReachBeyondThreeWords()
    {
        $this->assertGreaterThan(0, $this->compound('I do not think it was good.'));
    }

    public function testBoostersIntensifyAndDampen()
    {
        $plain = $this->compound('The film was good.');
        $boosted = $this->compound('The film was extremely good.');
        $dampened = $this->compound('The film was slightly good.');

        $this->assertGreaterThan($plain, $boosted);
        $this->assertLessThan($plain, $dampened);
    }

    public function testCapitalisationAmplifies()
    {
        $this->assertGreaterThan(
            $this->compound('The food is good.'),
            $this->compound('The food is GOOD.')
        );

        // All caps carries no emphasis, since nothing stands out against it.
        $this->assertEquals(
            $this->compound('the food is good.'),
            $this->compound('THE FOOD IS GOOD.')
        );
    }

    public function testExclamationMarksAmplify()
    {
        $none = $this->compound('The food is good');
        $one = $this->compound('The food is good!');
        $three = $this->compound('The food is good!!!');

        $this->assertGreaterThan($none, $one);
        $this->assertGreaterThan($one, $three);
    }

    public function testExclamationAmplificationIsCapped()
    {
        $this->assertEquals(
            $this->compound('The food is good!!!!'),
            $this->compound('The food is good!!!!!!!!')
        );
    }

    public function testButShiftsEmphasisToTheSecondClause()
    {
        // Both clauses are present, but the one after "but" should dominate.
        $this->assertLessThan(0, $this->compound('The food is good, but the service is horrible.'));
        $this->assertGreaterThan(0, $this->compound('The food is horrible, but the service is good.'));
    }

    public function testIdiomsAreScoredAsPhrases()
    {
        $this->assertGreaterThan(0, $this->compound('That movie was the shit'));
    }

    public function testEmoticonsAreScored()
    {
        $this->assertGreaterThan(0, $this->compound('the report is here :)'));
        $this->assertLessThan(0, $this->compound('the report is here :('));

        // Without the emoticon the same words are neutral.
        $this->assertEquals(0.0, $this->compound('the report is here'));
    }

    public function testEmoticonsShiftTheScoreOfSurroundingWords()
    {
        $this->assertLessThan(
            $this->compound('Nice work'),
            $this->compound('Nice work :(')
        );
    }

    public function testEmoticonsAreNotDoubleCounted()
    {
        // ":-)" must not also register as ":)".
        $this->assertEquals($this->compound('well done :)'), $this->compound('well done :-)'));
    }

    public function testScoresReturnProportionsThatSumToOne()
    {
        $scores = (new Sentiment('The food is good, but the service is horrible.'))->scores();

        $this->assertEqualsWithDelta(
            1.0,
            $scores['positive'] + $scores['negative'] + $scores['neutral'],
            0.01
        );
    }

    public function testCompoundIsBounded()
    {
        $extreme = str_repeat('excellent wonderful fantastic amazing ', 50);

        $this->assertLessThanOrEqual(1.0, $this->compound($extreme));
        $this->assertGreaterThanOrEqual(-1.0, $this->compound(str_repeat('horrible awful terrible ', 50)));
    }

    public function testLabels()
    {
        $this->assertEquals('positive', (new Sentiment('This is wonderful.'))->label());
        $this->assertEquals('negative', (new Sentiment('This is horrible.'))->label());
        $this->assertEquals('neutral', (new Sentiment('The meeting is at noon.'))->label());
    }

    public function testEmptyAndNeutralText()
    {
        $scores = (new Sentiment(''))->scores();

        $this->assertEquals(0.0, $scores['compound']);
        $this->assertEquals(0.0, $scores['positive']);
        $this->assertEquals(0.0, $scores['neutral']);

        $this->assertEquals(0.0, (new Sentiment(null))->compound());
    }

    public function testBySentence()
    {
        $results = (new Sentiment('The food was great. The service was terrible.'))->bySentence();

        $this->assertCount(2, $results);
        $this->assertEquals('The food was great.', $results[0]['sentence']);
        $this->assertGreaterThan(0, $results[0]['compound']);
        $this->assertLessThan(0, $results[1]['compound']);
    }

    public function testLexiconAdditionsAreMerged()
    {
        $this->assertEquals(0.0, $this->compound('The deployment had downtime.'));

        Lexicon::flush();
        config()->set('stringy.sentiment.additions', ['downtime' => -2.4]);

        $this->assertLessThan(0, $this->compound('The deployment had downtime.'));
    }

    public function testLexiconCanBeReplacedWholesale()
    {
        Lexicon::flush();
        config()->set('stringy.sentiment.lexicon', Lexicon::path('boosters'));

        // The booster list carries no sentiment words, so nothing scores.
        $this->assertEquals(0.0, $this->compound('The food is good.'));
    }

    public function testBundledLexiconsLoad()
    {
        $this->assertArrayHasKey('good', Lexicon::sentiment());
        $this->assertArrayHasKey('very', Lexicon::boosters());
        $this->assertArrayHasKey(':)', Lexicon::emoticons());
    }
}
