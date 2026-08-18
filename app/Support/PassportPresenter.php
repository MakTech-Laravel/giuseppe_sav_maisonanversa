<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class PassportPresenter
{
    /**
     * @return array{editionNumber: string, verificationUrl: string, verificationToken: string, pages: list<array{title: string, body: string}>}|null
     */
    public function forUser(User $user): ?array
    {
        $order = $this->heritageOrder($user);

        if ($order === null || $order->edition_number === null) {
            return null;
        }

        return $this->fromOrder($order);
    }

    /**
     * @return array{editionNumber: string, verificationUrl: string, verificationToken: string, pages: list<array{title: string, body: string}>}
     */
    public function fromOrder(Order $order): array
    {
        $order->loadMissing('product', 'editionPiece');

        $name = $order->product?->translated('name') ?? __('Product');
        $total = (int) ($order->product?->edition_total ?? 0);
        $number = str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT);
        $token = $order->editionPiece?->verification_token ?? '';
        $url = $token !== ''
            ? route('maison.verify', ['locale' => $order->locale ?: app()->getLocale(), 'token' => $token])
            : '';

        return [
            'editionNumber' => $number,
            'verificationUrl' => $url,
            'verificationToken' => $token,
            'pages' => [
                [
                    'title' => __('Omslag'),
                    'body' => __('HERITAGE PASSPORT · :product · No. :number / :total', [
                        'product' => $name,
                        'number' => $number,
                        'total' => $total,
                    ]),
                ],
                [
                    'title' => __('Het Huis'),
                    'body' => __('Maison Anversa is geworteld in Antwerpen. Dit huis bewaart Europese sport- en lifestyle-erfgoed, stuk voor stuk genummerd.'),
                ],
                [
                    'title' => __('Productidentiteit'),
                    'body' => __(':product · No. :number / :total · :owner · Geauthenticeerd · Yusuf Savran / Oprichter · Antwerpen', [
                        'product' => $name,
                        'number' => $number,
                        'total' => $total,
                        'owner' => $order->name,
                    ]),
                ],
                [
                    'title' => __('Zorg & verbinding'),
                    'body' => __('Volg de verzorgingsinstructies van het huis. Unieke verificatie: :url', ['url' => $url]),
                ],
            ],
        ];
    }

    /**
     * @return array{number: string, name: string, since: string}|null
     */
    public function circleCard(User $user): ?array
    {
        $order = $this->heritageOrder($user);

        if ($order === null || $order->edition_number === null) {
            return null;
        }

        return [
            'number' => str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT),
            'name' => $user->name,
            'since' => $order->created_at?->translatedFormat('j F Y') ?? '',
        ];
    }

    public function heritageOrder(User $user): ?Order
    {
        return $user->orders()
            ->with('product')
            ->whereHas('product', function ($query): void {
                $query->where('grants_founding_circle', true);
            })
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered])
            ->whereNotNull('edition_number')
            ->latest()
            ->first();
    }
}
