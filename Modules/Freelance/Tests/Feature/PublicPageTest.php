<?php

uses(\Tests\Feature\Freelance\FreelanceTestCase::class);

it('renders the how it works page', function () {
    $response = $this->get('/freelance/how-it-works');
    $response->assertStatus(200);
});

it('renders the about us page', function () {
    $response = $this->get('/freelance/about-us');
    $response->assertStatus(200);
});
