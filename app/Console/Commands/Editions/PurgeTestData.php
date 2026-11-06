<?php

namespace App\Console\Commands\Editions;

use App\Models\EditionPiece;
use App\Models\Order;
use App\Models\User;
use App\Services\Checkout\OrderFulfillment;
use App\Services\FoundingCircle\FoundingCircleRegistrar;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

#[Signature('maison:purge-test-data {--edition=* : Edition numbers of test orders, e.g. 019} {--order=* : Order ids} {--user=* : Emails of test accounts} {--execute : Apply the changes (default is a dry run)}')]
#[Description('Remove test orders and test accounts so the register shows real data only')]
class PurgeTestData extends Command
{
    public function handle(OrderFulfillment $fulfillment, FoundingCircleRegistrar $registrar): int
    {
        $orders = $this->orders();
        $users = $this->users();

        if ($orders->isEmpty() && $users->isEmpty()) {
            $this->warn('Nothing matched. Pass --edition=, --order= and/or --user=.');

            return self::FAILURE;
        }

        $this->table(
            ['Order', 'Edition', 'Status', 'User'],
            $orders->map(fn (Order $order): array => [
                $order->id,
                $order->editionPiece?->edition_number ?? '-',
                (string) ($order->status->value ?? $order->status),
                $order->user?->email ?? '-',
            ])->all(),
        );
        $this->table(['User', 'Email'], $users->map(fn (User $user): array => [$user->id, $user->email])->all());

        if (! $this->option('execute')) {
            $this->info('Dry run: nothing changed. Add --execute to apply.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($orders, $users, $fulfillment, $registrar): void {
            foreach ($orders as $order) {
                // Frees the register place and the edition number first, then the order goes.
                $fulfillment->markRefunded($order);
                $order->refresh()->delete();
            }

            foreach ($users as $user) {
                $registrar->releaseMember($user);
                $user->delete();
            }
        });

        $this->info("Removed {$orders->count()} order(s) and {$users->count()} account(s).");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Order>
     */
    private function orders(): Collection
    {
        $ids = collect((array) $this->option('order'))->map(fn ($id): int => (int) $id);

        $pieceIds = EditionPiece::query()
            ->whereIn('edition_number', collect((array) $this->option('edition'))->map(fn ($number): string => (string) $number)->all())
            ->pluck('id');

        return Order::query()
            ->with(['editionPiece', 'user'])
            ->where(fn ($query) => $query
                ->whereIn('id', $ids)
                ->orWhereIn('edition_piece_id', $pieceIds))
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function users(): Collection
    {
        $emails = array_filter((array) $this->option('user'));

        return $emails === [] ? new Collection : User::query()->whereIn('email', $emails)->get();
    }
}
