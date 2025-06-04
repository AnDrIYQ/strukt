<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EndPoint extends Model
{
    //
    protected $fillable = [
        'path', 'type', 'role', 'fields', 'trigger_event', 'own_only', 'collection_id',
    ];

    protected $casts = [
        'own_only' => 'boolean',
        'trigger_event' => 'boolean',
        'fields' => 'array',
        'role' => 'array',
    ];
}
