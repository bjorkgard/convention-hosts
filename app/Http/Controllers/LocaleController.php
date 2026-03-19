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
                $translations[$domain] = require $file->getPathname();
            }
        }

        return response()->json($translations);
    }
}
