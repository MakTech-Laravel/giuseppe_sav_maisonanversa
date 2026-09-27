<?php

namespace App\Http\Controllers\Maison;

use App\Http\Controllers\Controller;
use App\Support\FoundingCircleRegisterPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FoundingCircleRegisterController extends Controller
{
    public function __invoke(Request $request, string $locale, FoundingCircleRegisterPresenter $presenter): Response
    {
        return Inertia::render('maison/founding-circle/register', [
            'places' => $presenter->ledger($request->user()),
        ]);
    }
}
