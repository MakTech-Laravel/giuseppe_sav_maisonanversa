<?php

namespace App\Http\Controllers;

use App\Support\Seo\MaisonSitemapBuilder;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(MaisonSitemapBuilder $builder): Response
    {
        return response($builder->render(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
