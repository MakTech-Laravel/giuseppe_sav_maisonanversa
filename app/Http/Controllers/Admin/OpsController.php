<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminDemo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OpsController extends Controller
{
    public function orders(Request $request, string $locale): Response
    {
        return Inertia::render('admin/orders/index', [
            'orders' => AdminDemo::orders(),
            'commerceConnected' => false,
        ]);
    }

    public function orderShow(Request $request, string $locale, string $order): Response
    {
        $detail = AdminDemo::order($order);

        abort_if($detail === null, 404);

        return Inertia::render('admin/orders/show', [
            'order' => $detail,
            'commerceConnected' => false,
        ]);
    }

    public function community(Request $request, string $locale): Response
    {
        return Inertia::render('admin/community/index', [
            'items' => [],
        ]);
    }

    public function letter(Request $request, string $locale): Response
    {
        return Inertia::render('admin/letter/index', [
            'subscribers' => AdminDemo::letterSubscribers(),
            'letterConnected' => false,
        ]);
    }
}
