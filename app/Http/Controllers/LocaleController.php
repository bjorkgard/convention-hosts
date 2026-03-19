<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;

class LocaleController extends Controller
{
    /**
     * Return available locale codes from lang/ subdirectories.
     */
    public function index(): JsonResponse
    {
        $locales = collect(File::directories(lang_path()))
            ->map(fn ($dir) => basename($dir))
            ->values();

        return response()->json($locales);
    }

    /**
     * Return merged translations from all domain files for the given locale.
     * Converts Laravel :placeholder syntax to i18next {{placeholder}} syntax.
     */
    public function show(string $locale): JsonResponse
    {
        $path = lang_path($locale);

        if (! File::isDirectory($path)) {
            abort(404);
        }

        $translations = [];
        foreach (File::files($path) as $file) {
            if ($file->getExtension() === 'php') {
                $domain = $file->getFilenameWithoutExtension();
                $translations[$domain] = $this->convertPlaceholders(require $file->getPathname());
            }
        }

        return response()->json($translations);
    }

    /**
     * Recursively convert Laravel syntax to i18next syntax.
     *
     * Handles:
     * - :placeholder → {{placeholder}}
     * - Laravel plural "{1} one|[2,*] other" → ['_one' => 'one', '_other' => 'other']
     */
    private function convertPlaceholders(mixed $value): mixed
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $v) {
                $converted = $this->convertPlaceholders($v);
                if (is_array($converted) && isset($converted['_one'])) {
                    // Expand plural keys: key → key_one + key_other
                    $result[$key.'_one'] = $converted['_one'];
                    $result[$key.'_other'] = $converted['_other'];
                } else {
                    $result[$key] = $converted;
                }
            }

            return $result;
        }

        if (is_string($value)) {
            // Detect Laravel plural syntax: "{1} ...|[2,*] ..."
            if (preg_match('/\{1\}\s*(.+?)\|.*\[2,\*\]\s*(.+)/', $value, $matches)) {
                return [
                    '_one' => (string) preg_replace('/:([a-zA-Z_]+)/', '{{$1}}', trim($matches[1])),
                    '_other' => (string) preg_replace('/:([a-zA-Z_]+)/', '{{$1}}', trim($matches[2])),
                ];
            }

            return (string) preg_replace('/:([a-zA-Z_]+)/', '{{$1}}', $value);
        }

        return $value;
    }
}
