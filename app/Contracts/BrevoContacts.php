<?php

namespace App\Contracts;

use App\Models\NewsletterSubscriber;
use App\Models\Order;

interface BrevoContacts
{
    /**
     * Upsert a contact onto the Heritage Letter list and return the provider id.
     */
    public function upsertHeritageLetterContact(NewsletterSubscriber $subscriber): ?string;

    /**
     * Remove a contact from the Heritage Letter list.
     */
    public function unsubscribeHeritageLetterContact(NewsletterSubscriber $subscriber): void;

    /**
     * Upsert a paid-order buyer onto the orders list. Does not touch the Heritage Letter list.
     */
    public function upsertOrderContact(Order $order): void;
}
