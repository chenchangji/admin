<?php

namespace App\Http\Filters;

class AdminMaterialFilter extends Filter
{
    protected $simpleFilters = [
        'id',
    ];

    protected $filters = [
        'title',
        'user_id',
        'class',
        'sub_class',
    ];

    protected function title($val)
    {
        $this->builder->where('title', 'like', '%'.$val.'%');
    }

    protected function userId($val)
    {
        $this->builder->where('user_id', $val);
    }

    protected function class($val)
    {
        $this->builder->where('class', $val);
    }

    protected function subClass($val)
    {
        $this->builder->where('sub_class', $val);
    }
}
