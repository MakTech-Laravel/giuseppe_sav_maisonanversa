<?php

use App\Http\Controllers\Admin\ReturnController;
use Illuminate\Http\Request;

try {
    $controller = app(ReturnController::class);
    $request = Request::create('/nl/admin/returns', 'GET');
    $response = $controller->index($request);
    echo 'OK '.$response->getRootView()."\n";
} catch (Throwable $e) {
    echo $e::class."\n";
    echo $e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
    echo $e->getTraceAsString()."\n";
}
