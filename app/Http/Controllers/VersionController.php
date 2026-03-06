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

        $release = Cache::remember('github_latest_release', 300, function () use ($repo) {
            $response = Http::get("https://api.github.com/repos/{$repo}/releases/latest");

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            return [
                'version' => $data['tag_name'] ?? null,
                'name' => $data['name'] ?? null,
                'body' => $data['body'] ?? null,
                'published_at' => $data['published_at'] ?? null,
                'html_url' => $data['html_url'] ?? null,
            ];
        });

        if (! $release) {
            return response()->json(['error' => 'Unable to fetch release info'], 503);
        }

        return response()->json($release);
    }
}
