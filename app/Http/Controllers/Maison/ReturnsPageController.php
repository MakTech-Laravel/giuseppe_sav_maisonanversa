<?php

namespace App\Http\Controllers\Maison;

use App\Http\Controllers\Controller;
use App\Support\ReturnAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReturnsPageController extends Controller
{
    public function __invoke(Request $request, string $locale): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user?->isCustomer()) {
            return redirect()->route('member.orders', ['locale' => $locale]);
        }

        return Inertia::render('maison/returns', [
            'returnAddress' => ReturnAddress::resolve(),
            'loginRedirect' => route('member.orders', ['locale' => $locale]),
        ]);
    }
}
