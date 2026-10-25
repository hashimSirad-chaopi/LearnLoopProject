<?php

test('guests visiting the home page are redirected to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});