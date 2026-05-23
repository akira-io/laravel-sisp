<?php

declare(strict_types=1);

it('boots package routes and serves countries data', function (): void {
    $response = $this->get(route('sisp.countries'));

    $response->assertOk()
        ->assertJsonPath('cv.numeric', '132')
        ->assertJsonPath('cv.name', 'Cabo Verde');
});
