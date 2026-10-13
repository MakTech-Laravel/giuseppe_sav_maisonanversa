<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Authorized here via POST /broadcasting/auth; the standalone Reverb server
| (giuseppe_sav_maisonanversa_reverb_server) only verifies the signature.
| Public channels (e.g. "reverb-ping") need no entry.
|
*/

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return (int) $user->id === $id;
});
