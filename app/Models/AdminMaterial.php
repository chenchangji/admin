<?php

namespace App\Models;

class AdminMaterial extends Model
{
    protected $fillable = [
        'title',
        'type',
        'tag',
        'class',
        'sub_class',
        'url',
        'video_cover_url',
        'status',
        'size',
        'user_id',
        'product_id',
        'product_format',
        'screen_type',
        'duration',
        'actor_ids',
        'desc',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'actor_ids' => 'array', // 读取时自动 json_decode，写入时自动 json_encode
    ];

}
