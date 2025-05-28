<?php

namespace App\Http\Filters;

class AdminActorFilter extends Filter
{
    protected $simpleFilters = [
        'id',
    ];

    protected $filters = [
        'name',
    ];

    protected function fieldName($val)
    {
        $this->builder->where('name', $val);
    }
}
