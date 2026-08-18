<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sentiment Lexicon
    |--------------------------------------------------------------------------
    |
    | `lexicon` may point at a PHP file returning an array of word => valence
    | pairs on the -4 to +4 scale, replacing the bundled list entirely. This is
    | where you would drop in the full 7,500 entry VADER lexicon.
    |
    | `additions` is merged over whichever list is in use, so it is the place
    | for domain specific terms without giving up the defaults.
    |
    */

    'sentiment' => [
        'lexicon' => null,

        'additions' => [
            // 'downtime' => -2.4,
            // 'shipped' => 1.6,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Degree Modifiers
    |--------------------------------------------------------------------------
    |
    | Words which intensify or dampen the sentiment of the word that follows
    | them. Positive values intensify, negative values dampen.
    |
    */

    'boosters' => [
        'lexicon' => null,

        'additions' => [
            // 'wildly' => 0.293,
        ],
    ],

];
