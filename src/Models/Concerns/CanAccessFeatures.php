<?php

namespace Grafite\Support\Models\Concerns;

trait CanAccessFeatures
{
    public function hasFeatureAccess($key)
    {
        if (! is_array($key)) {
            $feature = config('features.'.$key, false);

            if (is_bool($feature)) {
                return $feature;
            }

            if (is_array($feature)) {
                return $feature['state'] && collect($feature['users'])->contains($this->id);
            }
        }
    }
}
