<?php

use App\Models\Collection;
use App\Models\EndPoint;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('collection.{name}', function ($user, $name) {
    $collection = Collection::where('name', $name)->first();

    if (!$collection) {
        return false;
    };

    $endpoints = EndPoint::where('collection_id', $collection->id)
        ->where('trigger_event', true)
        ->get();

    foreach ($endpoints as $endpoint) {
        if (in_array($user->role, $endpoint->role)) {
            return true;
        }
    }

    return false;
});
