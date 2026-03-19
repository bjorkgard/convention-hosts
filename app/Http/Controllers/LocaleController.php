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
     * Recursively convert Laravel :placeholder to i18next {{placeholder}}.
     */
    private function convertPlaceholders(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->convertPlaceholders($v), $value);
        }

        if (is_string($value)) {
            return (string) preg_replace('/:([a-zA-Z_]+)/', '{{$1}}', $value);
        }

        return $value;
    }
}
