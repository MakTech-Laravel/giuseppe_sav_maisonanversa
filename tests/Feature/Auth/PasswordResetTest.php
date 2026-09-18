<?php

test('reset password link screen redirects to the auth modal', function () {
    $this->get(route('password.request'))
        ->assertRedirect(localized('maison.home', absolute: false))
        ->assertSessionHas('open_auth_modal', 'forgot');
});
