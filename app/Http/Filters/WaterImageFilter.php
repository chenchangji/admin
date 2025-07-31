<?php

namespace App\Http\Filters;

class WaterImageFilter extends Filter
{
    protected $simpleFilters = [
        'id',
    ];

    protected $filters = [
        'title',
    ];

    protected function Title($val)
    {
        $this->builder->where('title', $val);
    }
}
