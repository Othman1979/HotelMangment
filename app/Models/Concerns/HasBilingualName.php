<?php

namespace App\Models\Concerns;

trait HasBilingualName
{
    public function name(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }
}
