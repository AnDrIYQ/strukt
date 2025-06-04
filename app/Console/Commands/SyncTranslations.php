<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Translation;

class SyncTranslations extends Command
{
    protected $signature = 'sync:translations';

    public function handle()
    {
        $inserted = 0;
        $found = collect();

        $files = File::allFiles(base_path('app/Http/Controllers/Api'));

        foreach ($files as $file) {
            $content = file_get_contents($file->getRealPath());

            preg_match_all("/translate\(\s*[\"']([^\"']+)[\"']\s*\)/", $content, $matches);
            $found = $found->merge($matches[1]);
        }

        $langFiles = File::files(base_path('lang/en'));

        foreach ($langFiles as $file) {
            $messages = include $file->getPathname();
            $flattened = $this->flatten($messages);
            $found = $found->merge($flattened);
        }

        $found = $found->filter()->unique();

        foreach ($found as $key) {
            if (!Translation::where('key', $key)->exists()) {
                Translation::create(['key' => $key]);
                $this->info("Додано ключ: $key");
                $inserted++;
            }
        }

        $this->info("✅ Синхронізація завершена. Нових ключів: $inserted");
    }

    protected function flatten(array $messages): array
    {
        $results = [];

        foreach ($messages as $value) {
            if (is_array($value)) {
                $results = array_merge($results, $this->flatten($value));
            } else {
                $results[] = $value;
            }
        }

        return $results;
    }
}
