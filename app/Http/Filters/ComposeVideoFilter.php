<?php

namespace App\Http\Filters;

class ComposeVideoFilter extends Filter
{
    protected $simpleFilters = [
        'id',
    ];

    protected $filters = [
        'title',
    ];

    protected function title($val)
    {
        $this->builder->where('title', 'like', '%'.$val.'%');
    }
}
