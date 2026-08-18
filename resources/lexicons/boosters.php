<?php

/*
 * Degree modifiers. Positive values intensify the sentiment of the word which
 * follows, negative values dampen it. The magnitudes are VADER's B_INCR and
 * B_DECR constants.
 *
 * Only single token modifiers are listed here. The handful of two word forms
 * such as "kind of" are handled by the Sentiment service directly.
 */

$increase = 0.293;
$decrease = -0.293;

return [
    // Intensifiers
    'absolutely' => $increase,
    'amazingly' => $increase,
    'awfully' => $increase,
    'completely' => $increase,
    'considerably' => $increase,
    'decidedly' => $increase,
    'deeply' => $increase,
    'enormously' => $increase,
    'entirely' => $increase,
    'especially' => $increase,
    'exceptionally' => $increase,
    'extremely' => $increase,
    'fabulously' => $increase,
    'flipping' => $increase,
    'frickin' => $increase,
    'fricking' => $increase,
    'frigging' => $increase,
    'fully' => $increase,
    'greatly' => $increase,
    'hella' => $increase,
    'highly' => $increase,
    'hugely' => $increase,
    'incredibly' => $increase,
    'intensely' => $increase,
    'majorly' => $increase,
    'more' => $increase,
    'most' => $increase,
    'particularly' => $increase,
    'purely' => $increase,
    'quite' => $increase,
    'really' => $increase,
    'remarkably' => $increase,
    'so' => $increase,
    'substantially' => $increase,
    'thoroughly' => $increase,
    'totally' => $increase,
    'tremendously' => $increase,
    'uber' => $increase,
    'unbelievably' => $increase,
    'unusually' => $increase,
    'utterly' => $increase,
    'very' => $increase,

    // Dampeners
    'almost' => $decrease,
    'barely' => $decrease,
    'hardly' => $decrease,
    'kinda' => $decrease,
    'less' => $decrease,
    'little' => $decrease,
    'marginally' => $decrease,
    'occasionally' => $decrease,
    'partly' => $decrease,
    'scarcely' => $decrease,
    'slightly' => $decrease,
    'somewhat' => $decrease,
    'sorta' => $decrease,
];
