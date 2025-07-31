<?php

namespace App\Models;

class AdminTemplate extends Model
{
    protected $fillable = [
        'title',
        'product_id',
        'product_format',
        'product_tag',
        'product_type',
        'screen_type',
        'class_rules',
        'range',
        'exclude_sub_class',
        'exclude_actor_ids',
        'user_id',
        'is_water_mark',
        'water_image_id',
        'status',
    ];
    protected $casts = [
        'exclude_actor_ids' => 'array', // 读取时自动 json_decode，写入时自动 json_encode
        'exclude_sub_class' => 'array', // 读取时自动 json_decode，写入时自动 json_encode
    ];
}
