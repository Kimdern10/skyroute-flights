<?php

test('every page links the SkyRoute icons and web app manifest', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('href="/favicon.ico"', false)
        ->assertSee('href="/apple-touch-icon.png"', false)
        ->assertSee('href="/site.webmanifest"', false);
})->with(['home', 'login']);

test('the web app manifest lists icons that exist', function () {
    $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true);

    expect($manifest['name'])->toBe('SkyRoute');

    foreach ($manifest['icons'] as $icon) {
        expect(public_path(ltrim($icon['src'], '/')))->toBeFile();
    }
});
