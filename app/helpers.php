<?php

use App\Models\Translation;

if (!function_exists('translate')) {
    function translate(string $key): string
    {
        return Translation::where('key', $key)->value('message') ?? $key;
    }
}
