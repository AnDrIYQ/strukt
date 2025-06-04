<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'icon',
        'singleton',
        'schema',
    ];

    protected $casts = [
        'singleton' => 'boolean',
        'schema' => 'array',
    ];

    public function getRouteKeyName()
    {
        return 'name';
    }

    public function endpoints()
    {
        return $this->hasMany(EndPoint::class);
    }
}
