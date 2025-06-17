<?php

namespace App\Http\Filters;

class ComposeVideoFilter extends Filter
{
    protected $simpleFilters = [
        'id',
    ];

    protected $filters = [
        'title',
        'actor'
    ];

    protected function title($val)
    {
        $this->builder->where('title', 'like', '%'.$val.'%');
    }

    protected function actor($val)
    {
        $this->builder->whereJsonContains('actor_ids', (int) $val);
    }
}
