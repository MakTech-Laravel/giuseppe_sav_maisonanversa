<?php

namespace App\Jobs;

use App\Contracts\BrevoContacts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteProductBrevoList implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 60, 120];

    public function __construct(public int $listId) {}

    public function handle(BrevoContacts $brevo): void
    {
        $brevo->deleteProductList($this->listId);
    }
}
