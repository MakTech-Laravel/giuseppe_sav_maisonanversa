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
            'items' => AdminDemo::communityQueue(),
            'communityConnected' => false,
        ]);
    }

    public function letter(Request $request, string $locale): Response
    {
        return Inertia::render('admin/letter/index', [
            'subscribers' => AdminDemo::letterSubscribers(),
            'letterConnected' => false,
        ]);
    }

    public function events(Request $request, string $locale): Response
    {
        return Inertia::render('admin/events/index', [
            'events' => AdminDemo::events(),
            'eventsConnected' => false,
        ]);
    }

    public function eventShow(Request $request, string $locale, string $event): Response
    {
        $detail = AdminDemo::event($event);

        abort_if($detail === null, 404);

        return Inertia::render('admin/events/show', [
            'event' => $detail,
            'eventsConnected' => false,
        ]);
    }

    public function circle(Request $request, string $locale): Response
    {
        return Inertia::render('admin/circle/index', [
            'members' => AdminDemo::circleMembers(),
            'circleConnected' => false,
        ]);
    }

    public function circleShow(Request $request, string $locale, string $member): Response
    {
        $detail = AdminDemo::circleMember($member);

        abort_if($detail === null, 404);

        return Inertia::render('admin/circle/show', [
            'member' => $detail,
            'circleConnected' => false,
        ]);
    }

    public function heritage(Request $request, string $locale): Response
    {
        return Inertia::render('admin/heritage/index', [
            'inventory' => AdminDemo::heritageInventory(),
            'heritageConnected' => false,
        ]);
    }
}
