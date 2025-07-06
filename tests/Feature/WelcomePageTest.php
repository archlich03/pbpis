<?php

it('loads the home page', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('PBPIS');
});