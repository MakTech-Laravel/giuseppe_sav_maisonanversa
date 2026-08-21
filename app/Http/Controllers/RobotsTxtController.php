<?php

namespace App\Http\Controllers;

use App\Support\Seo\RobotsTxtGenerator;
use Illuminate\Http\Response;

class RobotsTxtController extends Controller
{
    public function __invoke(RobotsTxtGenerator $generator): Response
    {
        $result = $generator->generate();

        return response($result['body'], 200, $result['headers']);
    }
}
