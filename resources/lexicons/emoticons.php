<?php

/**
 * Emoticon valences on the same -4 to +4 scale as the sentiment lexicon.
 *
 * These are matched against the raw string before tokenizing, since word
 * tokenizing would otherwise discard them as punctuation.
 */

return [
    '<3' => 2.4,
    '>:(' => -2.2,
    ">:'(" => -2.6,
    ":'(" => -2.0,
    ":'-(" => -2.0,
    ':-)' => 1.4,
    ':-D' => 2.5,
    ':-(' => -1.6,
    ':-/' => -0.9,
    ':-P' => 1.0,
    ':-*' => 1.6,
    ';-)' => 1.2,
    '=)' => 1.4,
    '=D' => 2.5,
    '=(' => -1.6,
    '8)' => 1.0,
    ':)' => 1.4,
    ':D' => 2.5,
    ':(' => -1.6,
    ':/' => -0.8,
    ':|' => -0.2,
    ':P' => 1.0,
    ':*' => 1.6,
    ':@' => -1.8,
    ':o' => 0.2,
    ';)' => 1.2,
    'xD' => 2.2,
    'XD' => 2.2,
];
