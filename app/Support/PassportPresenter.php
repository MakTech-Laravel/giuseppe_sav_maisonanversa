<?php

namespace App\Support;

use App\Enums\FoundingCircleClaimStatus;
use App\Enums\OrderStatus;
use App\Models\FoundingCircleClaim;
use App\Models\Order;
use App\Models\User;

class PassportPresenter
{
    /**
     * @return array{
     *     editionNumber: string,
     *     productName: string,
     *     racketLabel: string,
     *     verificationUrl: string,
     *     verificationToken: string,
     *     pages: list<array{title: string, body: string}>
     * }|null
     */
    public function forUser(User $user): ?array
    {
        $order = $this->heritageOrder($user);

        if ($order !== null && $order->edition_number !== null) {
            return $this->fromOrder($order);
        }

        $claim = $this->approvedClaim($user);

        if ($claim === null) {
            return null;
        }

        return $this->fromClaim($claim);
    }

    /**
     * @return array{
     *     editionNumber: string,
     *     productName: string,
     *     racketLabel: string,
     *     verificationUrl: string,
     *     verificationToken: string,
     *     pages: list<array{title: string, body: string}>
     * }
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
        $racketLabel = $number.'/'.str_pad((string) $total, 3, '0', STR_PAD_LEFT);

        return [
            'editionNumber' => $number,
            'productName' => $name,
            'racketLabel' => $racketLabel,
            'verificationUrl' => $url,
            'verificationToken' => $token,
            'pages' => [
                [
                    'title' => __('Omslag'),
                    'body' => __('FOUNDING CIRCLE · :product · RACKET :racket', [
                        'product' => $name,
                        'racket' => $racketLabel,
                    ]),
                ],
                [
                    'title' => __('Het Huis'),
                    'body' => __('Maison Anversa is geworteld in Antwerpen. Dit huis bewaart Europese sport- en lifestyle-erfgoed, stuk voor stuk genummerd.'),
                ],
                [
                    'title' => __('Productidentiteit'),
                    'body' => __(':product · Racket :racket · :owner · Geauthenticeerd · Yusuf Savran / Oprichter · Antwerpen', [
                        'product' => $name,
                        'racket' => $racketLabel,
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
     * @return array{
     *     editionNumber: string,
     *     productName: string,
     *     racketLabel: string,
     *     verificationUrl: string,
     *     verificationToken: string,
     *     pages: list<array{title: string, body: string}>
     * }
     */
    public function fromClaim(FoundingCircleClaim $claim): array
    {
        $claim->loadMissing('product', 'editionPiece', 'user');

        $name = $claim->product?->translated('name') ?? __('Product');
        $total = (int) ($claim->product?->edition_total ?? 0);
        $number = $claim->paddedEditionNumber();
        $token = $claim->editionPiece?->verification_token ?? '';
        $url = $token !== ''
            ? route('maison.verify', ['locale' => app()->getLocale(), 'token' => $token])
            : '';
        $racketLabel = $number.'/'.str_pad((string) $total, 3, '0', STR_PAD_LEFT);

        return [
            'editionNumber' => $number,
            'productName' => $name,
            'racketLabel' => $racketLabel,
            'verificationUrl' => $url,
            'verificationToken' => $token,
            'pages' => [
                [
                    'title' => __('Omslag'),
                    'body' => __('FOUNDING CIRCLE · :product · RACKET :racket', [
                        'product' => $name,
                        'racket' => $racketLabel,
                    ]),
                ],
                [
                    'title' => __('Het Huis'),
                    'body' => __('Maison Anversa is geworteld in Antwerpen. Dit huis bewaart Europese sport- en lifestyle-erfgoed, stuk voor stuk genummerd.'),
                ],
                [
                    'title' => __('Productidentiteit'),
                    'body' => __(':product · Racket :racket · :owner · Geauthenticeerd · Yusuf Savran / Oprichter · Antwerpen', [
                        'product' => $name,
                        'racket' => $racketLabel,
                        'owner' => $claim->user?->name ?? '',
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
     * @return array{
     *     number: string,
     *     name: string,
     *     since: string,
     *     productName: string,
     *     racketLabel: string
     * }|null
     */
    public function circleCard(User $user): ?array
    {
        $order = $this->heritageOrder($user);

        if ($order !== null && $order->edition_number !== null) {
            $order->loadMissing('product');
            $total = (int) ($order->product?->edition_total ?? 0);
            $number = str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT);

            return [
                'number' => $number,
                'name' => $user->name,
                'since' => $order->created_at?->translatedFormat('j F Y') ?? '',
                'productName' => $order->product?->translated('name') ?? __('Heritage No.001'),
                'racketLabel' => $number.'/'.str_pad((string) $total, 3, '0', STR_PAD_LEFT),
            ];
        }

        $claim = $this->approvedClaim($user);

        if ($claim === null) {
            return null;
        }

        $claim->loadMissing('product');
        $total = (int) ($claim->product?->edition_total ?? 0);
        $number = $claim->paddedEditionNumber();

        return [
            'number' => $number,
            'name' => $user->name,
            'since' => $claim->reviewed_at?->translatedFormat('j F Y')
                ?? $claim->created_at?->translatedFormat('j F Y')
                ?? '',
            'productName' => $claim->product?->translated('name') ?? __('Heritage No.001'),
            'racketLabel' => $number.'/'.str_pad((string) $total, 3, '0', STR_PAD_LEFT),
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

    public function approvedClaim(User $user): ?FoundingCircleClaim
    {
        return FoundingCircleClaim::query()
            ->where('user_id', $user->id)
            ->where('status', FoundingCircleClaimStatus::Approved)
            ->with(['product', 'editionPiece'])
            ->latest('reviewed_at')
            ->first();
    }
}
