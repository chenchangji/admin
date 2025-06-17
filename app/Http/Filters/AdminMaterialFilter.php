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
        'product_id',
        'screen_type',
        'actor',
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

    protected function productId($val)
    {
        $this->builder->where('product_id', $val);
    }

    protected function screenType($val)
    {
        $this->builder->where('screen_type', $val);
    }

    protected function actor($val)
    {
        $this->builder->whereJsonContains('actor_ids', (int) $val);
    }
}
