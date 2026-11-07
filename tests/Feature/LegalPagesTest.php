<?php

use App\Models\Setting;

test('the privacy policy and terms show starter text with the business details', function (string $route, string $text) {
    $this->get(route($route))->assertOk()->assertSee($text)->assertSee(Setting::get('support_email'));
})->with([
    ['privacy', 'What we collect'],
    ['terms', 'Your wallet'],
]);

test('the owner\'s own text replaces the starter text, with HTML escaped', function () {
    Setting::put(['terms' => "## Our rules\n\nBe kind. <script>alert(1)</script>"]);

    $this->get(route('terms'))
        ->assertOk()
        ->assertSee('Our rules')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('Your wallet');
});

test('sign-up links to the terms and privacy policy', function () {
    $this->get(route('register'))->assertOk()->assertSee(route('terms'))->assertSee(route('privacy'));
});
