<?php

namespace Grafite\Support\Services\Text;

/**
 * Lexicon and rule based sentiment scoring, following the VADER model.
 *
 * Beyond looking words up in a lexicon this accounts for negation ("not
 * good"), degree modifiers ("very good"), capitalisation ("GOOD"), trailing
 * punctuation ("good!!!") and contrastive conjunctions ("good, but slow"),
 * which is what makes it usable on prose rather than just word lists.
 *
 * @see https://ojs.aaai.org/index.php/ICWSM/article/view/14550
 */
class Sentiment
{
    /**
     * Amount a degree modifier shifts the word it qualifies.
     */
    private const BOOSTER_DAMPING = [1 => 0.95, 2 => 0.9];

    /**
     * Boost applied to a word written in capitals.
     */
    private const CAPS_INCREMENT = 0.733;

    /**
     * Multiplier applied to a negated word.
     */
    private const NEGATION_SCALAR = -0.74;

    /**
     * Normalisation constant; controls how quickly the compound score
     * approaches -1 and +1.
     */
    private const ALPHA = 15;

    private const EXCLAMATION_INCREMENT = 0.292;

    private const MAX_EXCLAMATIONS = 4;

    private const QUESTION_INCREMENT = 0.18;

    private const MAX_QUESTIONS = 3;

    /**
     * Two word degree modifiers, which single token lookups would miss.
     */
    private const PHRASE_MODIFIERS = [
        'kind of' => -0.293,
        'sort of' => -0.293,
        'a bit' => -0.293,
    ];

    private const NEGATIONS = [
        'aint', "ain't", 'arent', "aren't", 'cannot', 'cant', "can't",
        'couldnt', "couldn't", 'darent', "daren't", 'despite', 'didnt',
        "didn't", 'doesnt', "doesn't", 'dont', "don't", 'hadnt', "hadn't",
        'hasnt', "hasn't", 'havent', "haven't", 'isnt', "isn't", 'mightnt',
        "mightn't", 'mustnt', "mustn't", 'neednt', "needn't", 'neither',
        'never', 'none', 'nope', 'nor', 'not', 'nothing', 'nowhere',
        'oughtnt', "oughtn't", 'rarely', 'seldom', 'shant', "shan't",
        'shouldnt', "shouldn't", 'uh-uh', 'uhuh', 'wasnt', "wasn't",
        'werent', "weren't", 'without', 'wont', "won't", 'wouldnt',
        "wouldn't",
    ];

    /**
     * Phrases whose sentiment is not the sum of their parts.
     */
    private const IDIOMS = [
        'the shit' => 3.0,
        'the bomb' => 3.0,
        'bad ass' => 1.5,
        'badass' => 1.5,
        'yeah right' => -2.0,
        'cut the mustard' => 2.0,
        'kiss of death' => -1.5,
        'hand to mouth' => -2.0,
        'upper hand' => 1.0,
        'break a leg' => 2.0,
    ];

    private string $content;

    private array $tokens;

    private array $lexicon;

    private array $boosters;

    public function __construct(?string $content)
    {
        $this->content = (string) $content;
        $this->tokens = (new Tokenizer($this->content))->wordsWithCase();
        $this->lexicon = Lexicon::sentiment();
        $this->boosters = Lexicon::boosters();
    }

    public static function of(?string $content): self
    {
        return new self($content);
    }

    /**
     * Proportions of positive, negative and neutral content, plus a single
     * normalised `compound` score between -1 and 1.
     */
    public function scores(): array
    {
        $valences = $this->valences();

        // Contrast weighting is positional, so emoticons are folded in only
        // once the token aligned work is done.
        $valences = array_merge($this->applyContrast($valences), $this->emoticonValences());

        if ($valences === []) {
            return [
                'positive' => 0.0,
                'negative' => 0.0,
                'neutral' => 0.0,
                'compound' => 0.0,
            ];
        }

        $sum = array_sum($valences);
        $emphasis = $this->punctuationEmphasis();

        if ($sum > 0) {
            $sum += $emphasis;
        } elseif ($sum < 0) {
            $sum -= $emphasis;
        }

        [$positive, $negative, $neutral] = $this->sift($valences);

        if ($positive > abs($negative)) {
            $positive += $emphasis;
        } elseif ($positive < abs($negative)) {
            $negative -= $emphasis;
        }

        $total = $positive + abs($negative) + $neutral;

        return [
            'positive' => $total > 0 ? round(abs($positive / $total), 3) : 0.0,
            'negative' => $total > 0 ? round(abs($negative / $total), 3) : 0.0,
            'neutral' => $total > 0 ? round(abs($neutral / $total), 3) : 0.0,
            'compound' => round($this->normalize($sum), 4),
        ];
    }

    /**
     * The compound score on its own: -1 is maximally negative, +1 maximally
     * positive.
     */
    public function compound(): float
    {
        return $this->scores()['compound'];
    }

    /**
     * A plain label for the compound score, using VADER's usual thresholds.
     */
    public function label(): string
    {
        $compound = $this->compound();

        return match (true) {
            $compound >= 0.05 => 'positive',
            $compound <= -0.05 => 'negative',
            default => 'neutral',
        };
    }

    /**
     * Score each sentence separately, which is often more useful than a single
     * figure for a long passage.
     */
    public function bySentence(): array
    {
        $results = [];

        foreach ((new Tokenizer($this->content))->sentences() as $sentence) {
            $results[] = ['sentence' => $sentence] + (new self($sentence))->scores();
        }

        return $results;
    }

    /**
     * Per token valences, before contrast and punctuation adjustments.
     */
    private function valences(): array
    {
        $valences = [];
        $isCapDifferential = $this->isCapDifferential();

        foreach ($this->tokens as $i => $token) {
            $lower = mb_strtolower($token);

            // Degree modifiers carry no sentiment of their own. "kind" is a
            // sentiment word in its own right, so it only counts as a modifier
            // in the phrase "kind of".
            $isKindOf = $lower === 'kind'
                && isset($this->tokens[$i + 1])
                && mb_strtolower($this->tokens[$i + 1]) === 'of';

            if ($isKindOf || isset($this->boosters[$lower])) {
                $valences[] = 0.0;

                continue;
            }

            if (! isset($this->lexicon[$lower])) {
                $valences[] = 0.0;

                continue;
            }

            $valence = (float) $this->lexicon[$lower];

            if ($this->isUpper($token) && $isCapDifferential) {
                $valence += $valence > 0 ? self::CAPS_INCREMENT : -self::CAPS_INCREMENT;
            }

            // Look back up to three words for modifiers and negations.
            for ($distance = 0; $distance < 3; $distance++) {
                $precedingIndex = $i - $distance - 1;

                if ($precedingIndex < 0) {
                    break;
                }

                if (isset($this->lexicon[mb_strtolower($this->tokens[$precedingIndex])])) {
                    continue;
                }

                $modifier = $this->modifierFor($precedingIndex, $valence, $isCapDifferential);

                if ($modifier !== 0.0 && isset(self::BOOSTER_DAMPING[$distance])) {
                    $modifier *= self::BOOSTER_DAMPING[$distance];
                }

                $valence += $modifier;
                $valence = $this->applyNegation($valence, $i, $distance);

                if ($distance === 2) {
                    $valence = $this->applyIdioms($valence, $i);
                }
            }

            $valences[] = $this->applyLeast($valence, $i);
        }

        return $valences;
    }

    /**
     * "least" negates ("least helpful") unless it follows "at" or "very".
     */
    private function applyLeast(float $valence, int $index): float
    {
        if ($index < 1 || mb_strtolower($this->tokens[$index - 1]) !== 'least') {
            return $valence;
        }

        if ($index >= 2) {
            $twoBack = mb_strtolower($this->tokens[$index - 2]);

            if ($twoBack === 'at' || $twoBack === 'very') {
                return $valence;
            }
        }

        return $valence * self::NEGATION_SCALAR;
    }

    /**
     * Words before "but" matter less, words after it matter more.
     */
    private function applyContrast(array $valences): array
    {
        $lowered = array_map('mb_strtolower', $this->tokens);
        $position = array_search('but', $lowered, true);

        if ($position === false) {
            return $valences;
        }

        foreach ($valences as $i => $valence) {
            if ($i < $position) {
                $valences[$i] = $valence * 0.5;
            } elseif ($i > $position) {
                $valences[$i] = $valence * 1.5;
            }
        }

        return $valences;
    }

    private function applyNegation(float $valence, int $index, int $distance): float
    {
        $wordAt = function (int $offset) use ($index): ?string {
            $position = $index - $offset;

            return $position >= 0 ? mb_strtolower($this->tokens[$position]) : null;
        };

        if ($distance === 0) {
            return $this->isNegation($wordAt(1)) ? $valence * self::NEGATION_SCALAR : $valence;
        }

        if ($distance === 1) {
            if ($wordAt(2) === 'never' && in_array($wordAt(1), ['so', 'this'], true)) {
                return $valence * 1.25;
            }

            if ($wordAt(2) === 'without' && $wordAt(1) === 'doubt') {
                return $valence;
            }

            return $this->isNegation($wordAt(2)) ? $valence * self::NEGATION_SCALAR : $valence;
        }

        if ($wordAt(3) === 'never' && (in_array($wordAt(2), ['so', 'this'], true) || in_array($wordAt(1), ['so', 'this'], true))) {
            return $valence * 1.25;
        }

        if ($wordAt(3) === 'without' && ($wordAt(2) === 'doubt' || $wordAt(1) === 'doubt')) {
            return $valence;
        }

        return $this->isNegation($wordAt(3)) ? $valence * self::NEGATION_SCALAR : $valence;
    }

    private function applyIdioms(float $valence, int $index): float
    {
        $lowered = array_map('mb_strtolower', $this->tokens);

        for ($length = 2; $length <= 4; $length++) {
            $start = $index - $length + 1;

            if ($start < 0) {
                continue;
            }

            $phrase = implode(' ', array_slice($lowered, $start, $length));

            if (isset(self::IDIOMS[$phrase])) {
                return (float) self::IDIOMS[$phrase];
            }
        }

        return $valence;
    }

    /**
     * How much the degree modifier at $index shifts the current word. Two word
     * modifiers such as "kind of" take precedence over single tokens.
     */
    private function modifierFor(int $index, float $valence, bool $isCapDifferential): float
    {
        $word = $this->tokens[$index];
        $lower = mb_strtolower($word);

        $scalar = null;

        if ($index >= 1) {
            $phrase = mb_strtolower($this->tokens[$index - 1]).' '.$lower;

            if (isset(self::PHRASE_MODIFIERS[$phrase])) {
                $scalar = self::PHRASE_MODIFIERS[$phrase];
            }
        }

        if ($scalar === null && isset($this->boosters[$lower])) {
            $scalar = $this->boosters[$lower];
        }

        if ($scalar === null) {
            return 0.0;
        }

        $scalar = (float) $scalar;

        if ($valence < 0) {
            $scalar *= -1;
        }

        if ($this->isUpper($word) && $isCapDifferential) {
            $scalar += $valence > 0 ? self::CAPS_INCREMENT : -self::CAPS_INCREMENT;
        }

        return $scalar;
    }

    /**
     * Valences for any emoticons in the raw string. Longer patterns are
     * matched first and removed as they are found, so ":-)" is not also
     * counted as ":)".
     */
    private function emoticonValences(): array
    {
        $emoticons = Lexicon::emoticons();

        if ($emoticons === []) {
            return [];
        }

        $patterns = array_keys($emoticons);

        usort($patterns, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        $content = $this->content;
        $valences = [];

        foreach ($patterns as $pattern) {
            $occurrences = substr_count($content, $pattern);

            if ($occurrences === 0) {
                continue;
            }

            $content = str_replace($pattern, ' ', $content);

            $valences = array_merge(
                $valences,
                array_fill(0, $occurrences, (float) $emoticons[$pattern])
            );
        }

        return $valences;
    }

    /**
     * Trailing "!" and "?" add emphasis, but only up to a point.
     */
    private function punctuationEmphasis(): float
    {
        $exclamations = min(substr_count($this->content, '!'), self::MAX_EXCLAMATIONS);
        $emphasis = $exclamations * self::EXCLAMATION_INCREMENT;

        $questions = substr_count($this->content, '?');

        if ($questions > 1) {
            $emphasis += $questions <= self::MAX_QUESTIONS
                ? $questions * self::QUESTION_INCREMENT
                : 0.96;
        }

        return $emphasis;
    }

    /**
     * Split valences into positive, negative and neutral pools. The +/- 1
     * offsets keep neutral words from swamping the proportions.
     */
    private function sift(array $valences): array
    {
        $positive = 0.0;
        $negative = 0.0;
        $neutral = 0;

        foreach ($valences as $valence) {
            if ($valence > 0) {
                $positive += $valence + 1;
            } elseif ($valence < 0) {
                $negative += $valence - 1;
            } else {
                $neutral++;
            }
        }

        return [$positive, $negative, $neutral];
    }

    /**
     * Squash an unbounded sum into the -1 to 1 range.
     */
    private function normalize(float $score): float
    {
        $normalized = $score / sqrt(($score * $score) + self::ALPHA);

        return max(-1.0, min(1.0, $normalized));
    }

    /**
     * Capitals only signal emphasis when the text is not entirely uppercase.
     */
    private function isCapDifferential(): bool
    {
        $upper = 0;

        foreach ($this->tokens as $token) {
            if ($this->isUpper($token)) {
                $upper++;
            }
        }

        return $upper > 0 && $upper < count($this->tokens);
    }

    private function isUpper(string $word): bool
    {
        return preg_match('/[a-z]/u', $word) !== 1 && preg_match('/[A-Z]/u', $word) === 1;
    }

    private function isNegation(?string $word): bool
    {
        return $word !== null && in_array($word, self::NEGATIONS, true);
    }
}
