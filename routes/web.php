<?php

use Illuminate\Support\Facades\Route;

Route::get('/up', function () {
    return view('support::up');
});

if (! app()->environment(['staging', 'production'])) {
    Route::get('/docs', function () {
        $files = scandir(base_path('docs'));

        foreach ($files as $file) {
            if (! is_dir(base_path('docs/'.$file))) {
                $sections[$file] = file_get_contents(base_path('docs/'.$file));
            }
        }

        if (base_path('CHANGELOG.md')) {
            $sections['change_log'] = file_get_contents(base_path('CHANGELOG.md'));
        }

        if (base_path('changelog.md')) {
            $sections['change_log'] = file_get_contents(base_path('changelog.md'));
        }

        return view('support::docs', [
            'sections' => $sections,
        ]);
    });
}
