<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class VersionController extends Controller
{
    public function latest(): JsonResponse
    {
        $repo = config('app.github_repo');

        $cached = Cache::get('github_latest_release');

        if ($cached !== null) {
            return response()->json($cached);
        }

        try {
            $response = Http::timeout(5)->get("https://api.github.com/repos/{$repo}/releases/latest");
        } catch (\Throwable) {
            return response()->json(['error' => 'Unable to fetch release info'], 503);
        }

        if ($response->status() === 404) {
            $empty = [
                'version' => null,
                'name' => null,
                'body' => null,
                'published_at' => null,
                'html_url' => null,
            ];

            Cache::put('github_latest_release', $empty, 300);

            return response()->json($empty);
        }

        if ($response->failed()) {
            return response()->json(['error' => 'Unable to fetch release info'], 503);
        }

        $data = $response->json();

        $release = [
            'version' => $data['tag_name'] ?? null,
            'name' => $data['name'] ?? null,
            'body' => $data['body'] ?? null,
            'published_at' => $data['published_at'] ?? null,
            'html_url' => $data['html_url'] ?? null,
        ];

        Cache::put('github_latest_release', $release, 300);

        return response()->json($release);
    }
}
