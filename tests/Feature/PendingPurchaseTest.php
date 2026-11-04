<?php

use Illuminate\Support\Facades\File;

/**
 * A guest who picks a number, presses Reserve and is asked to log in must come back
 * to the same number. There is no JS runner here, so, like the rest of the opening's
 * tests, these read the source; the server half is in Auth/IntendedRedirectTest.
 */
function pendingPurchaseSource(string $path): string
{
    return File::get(resource_path($path));
}

test('the pending purchase is kept for a short while and handed over once', function () {
    $source = pendingPurchaseSource('js/lib/pending-purchase.ts');

    expect($source)
        ->toContain("PENDING_PURCHASE_KEY = 'pendingPurchase'")
        ->toContain('PENDING_PURCHASE_TTL_MS = 30 * 60 * 1000')
        // Stamped when stored, refused when older than the lifetime.
        ->toContain('at: Date.now()')
        ->toContain('Date.now() - stored.at > PENDING_PURCHASE_TTL_MS')
        // Taken once: removed as soon as it is read, even when it turns out to be unusable.
        ->toMatch('/clearPendingPurchase\(\);\s*try \{\s*const stored/')
        // Storage can be denied, so every access is guarded.
        ->and(substr_count($source, 'sessionStorage.'))->toBe(3)
        ->and(substr_count($source, 'try {'))->toBe(4);
});

test('the shell remembers the product and the number before it opens the login window', function () {
    $layout = pendingPurchaseSource('js/layouts/frontend-layout.tsx');

    expect($layout)
        // Both ways in pass what the guest was doing ...
        ->toContain("requireAuthForCheckout({ kind: 'order', product })")
        ->toContain("kind: 'purchase',")
        // ... and it is stored before the guard returns, with the home page remembered as well.
        ->toContain('storePendingPurchase(pending)')
        ->toContain('rememberAuthReturnUrl(pending !== undefined)')
        ->toContain('{ url: returnUrl, resume: true }');

    // The login window opens after the purchase is stored, not before.
    expect(strpos($layout, 'storePendingPurchase(pending)'))
        ->toBeLessThan(strpos($layout, "setUserAuthView('login')"));
});

test('once signed in, the shell carries on with the same product and number', function () {
    $layout = pendingPurchaseSource('js/layouts/frontend-layout.tsx');

    expect($layout)
        ->toContain('takePendingPurchase()')
        ->toContain('actions.openPurchase(pending.product, pending.edition)')
        ->toContain('actions.openOrder(pending.product)')
        ->toContain('}, [isAuthenticated, actions]);')
        // Closing the login window cancels the reservation that was waiting on it.
        ->toMatch('/function closeModal\(\): void \{[\s\S]*?clearPendingPurchase\(\);/');

    // It runs only for a signed-in visitor.
    expect($layout)->toMatch('/if \(!isAuthenticated\) \{\s*return;\s*\}\s*const pending = takePendingPurchase\(\)/');
});

test('the opening hands its chosen number to the same flow', function () {
    $register = pendingPurchaseSource('js/components/maison/opening/opening-register.tsx');

    // It goes through the shell's openPurchase, which now keeps it across the login.
    expect($register)
        ->toContain('openPurchase(')
        ->toContain('editionPieceId: tile.id');

    foreach (['maison/home/home-preorder.tsx', 'maison/product/product-detail.tsx'] as $caller) {
        expect(pendingPurchaseSource("js/components/{$caller}"))->toContain('openPurchase');
    }

    expect(pendingPurchaseSource('js/pages/maison/circle.tsx'))->toContain('openPurchase');
});
