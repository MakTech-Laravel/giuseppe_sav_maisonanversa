<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * The public Maison Anversa site. Every page is presentational in this phase,
 * so each action only names its Inertia component; the edition figures are the
 * one piece of shared state and come from config/maison.php.
 */
class MaisonController extends Controller
{
    public function home(): Response
    {
        return $this->page('home');
    }

    public function house(): Response
    {
        return $this->page('house');
    }

    public function product(): Response
    {
        return $this->page('product');
    }

    public function story(): Response
    {
        return $this->page('story');
    }

    public function circle(): Response
    {
        return $this->page('circle');
    }

    public function dressing(): Response
    {
        return $this->page('dressing');
    }

    public function journal(): Response
    {
        return $this->page('journal');
    }

    public function community(): Response
    {
        return $this->page('community');
    }

    public function corner(): Response
    {
        return $this->page('corner');
    }

    public function contact(): Response
    {
        return $this->page('contact');
    }

    public function privacy(): Response
    {
        return $this->page('legal/privacy');
    }

    public function terms(): Response
    {
        return $this->page('legal/terms');
    }

    public function shipping(): Response
    {
        return $this->page('legal/shipping');
    }

    public function care(): Response
    {
        return $this->page('legal/care');
    }

    /**
     * Render a page under resources/js/pages/maison, with the edition state
     * every page may quote attached.
     *
     * @param  array<string, mixed>  $props
     */
    private function page(string $component, array $props = []): Response
    {
        return Inertia::render("maison/{$component}", [
            'edition' => $this->edition(),
            ...$props,
        ]);
    }

    /**
     * @return array{reserved: int, total: int, available: int}
     */
    private function edition(): array
    {
        $reserved = (int) config('maison.edition.reserved');
        $total = (int) config('maison.edition.total');

        return [
            'reserved' => $reserved,
            'total' => $total,
            'available' => max(0, $total - $reserved),
        ];
    }
}
