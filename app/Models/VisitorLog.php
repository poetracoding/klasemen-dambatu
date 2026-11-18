<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'visit_date',
        'session_id',
        'ip_address',
        'path',
        'page_title',
        'device_type',
        'user_agent',
        'referer',
        'created_at',
    ];

    protected $casts = [
        'visit_date' => 'date:Y-m-d',
        'created_at' => 'datetime',
    ];
}
