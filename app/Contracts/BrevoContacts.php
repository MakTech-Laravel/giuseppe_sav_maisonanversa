<?php

namespace App\Contracts;

use App\Models\NewsletterSubscriber;

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
}
