<?php

namespace App\Contracts;

use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Product;

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
     * Upsert a paid-order buyer onto the product's own list only.
     * Does not touch the Heritage Letter list or the orders list.
     */
    public function upsertOrderContact(Order $order): void;

    /**
     * Create the product's Brevo list when it does not have one yet.
     */
    public function ensureProductList(Product $product): ?int;

    /**
     * Delete a product list. Contacts remain in Brevo.
     */
    public function deleteProductList(int $listId): void;
}
