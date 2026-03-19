<?php

use Illuminate\Support\Facades\File;

it('auto-discovers new locale directories without code changes', function () {
    $newLocalePath = lang_path('de');

    // Ensure the test locale doesn't already exist
    expect(File::isDirectory($newLocalePath))->toBeFalse();

    // Create a new locale directory with a minimal translation file
    File::makeDirectory($newLocalePath, 0755, true);
    File::put($newLocalePath.'/common.php', "<?php\n\nreturn ['app_name' => 'Konventionsverwaltung'];\n");

    try {
        $response = $this->getJson('/api/locales');

        $response->assertOk()
            ->assertJsonFragment(['de']);

        // Verify translations are also served for the new locale
        $translationsResponse = $this->getJson('/api/translations/de');

        $translationsResponse->assertOk();
        expect($translationsResponse->json('common.app_name'))->toBe('Konventionsverwaltung');
    } finally {
        // Clean up: remove the temporary locale directory
        File::deleteDirectory($newLocalePath);
    }
});
