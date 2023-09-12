<?php

namespace Grafite\Support\Models\Concerns;

trait HasJavascriptData
{
    /**
     * Prepare a payload for the JS session data.
     *
     * @return string|false
     */
    public function jsonSessionData()
    {
        $visibleAttributes = [
            'id',
        ];

        return json_encode(collect($this->toArray())
            ->filter(function ($value, $attribute) use ($visibleAttributes) {
                return in_array($attribute, $visibleAttributes);
            }));
    }
}
