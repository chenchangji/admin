<?php

namespace App\Models;

class ComposeVideo extends Model
{
   protected $fillable = [
        'template_id',
        'job_id',
        'url',
        'video_cover_url',
        'status',
        'product_id',
        'product_format',
        'screen_type',
        'actor_ids',
        'material_ids',
        'desc',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'actor_ids' => 'array', // 读取时自动 json_decode，写入时自动 json_encode
        'material_ids' => 'array', // 读取时自动 json_decode，写入时自动 json_encode
    ];
}
