<?php

use Illuminate\Support\Facades\File;

/*
 * The modals replace the prototype's onclick-driven overlays. These tests read
 * the source back rather than driving a browser, matching how the other Maison
 * feature tests pin behaviour that would otherwise regress silently.
 */

function modalSource(string $name): string
{
    return File::get(resource_path("js/components/maison/modals/{$name}"));
}

function modalSources(): string
{
    return collect(File::files(resource_path('js/components/maison/modals')))
        ->filter(fn ($file) => in_array($file->getExtension(), ['ts', 'tsx'], true))
        ->map(fn ($file) => $file->getContents())
        ->join("\n");
}

test('the scroll lock utility counts holders so nested locks do not unlock early', function () {
    expect(File::get(resource_path('js/hooks/use-scroll-lock.ts')))
        ->toContain('let holders = 0')
        ->toContain('export function lockScroll')
        ->toContain('export function unlockScroll')
        ->toContain('export function scrollLockHolders');
});

test('the order modal renders a hundred-cell number grid', function () {
    expect(modalSource('order-modal.tsx'))
        ->toContain('Array.from({ length: 100 }')
        ->toContain('grid-cols-10');
});

test('modal components do not use inline onclick handlers', function () {
    expect(modalSources())->not->toMatch('/onclick=/');
});

test('every modal uses the shared scroll lock', function () {
    expect(modalSource('maison-modal.tsx'))
        ->toContain('useScrollLock(true)');
});

test('the modal shell is accessible and dismissible with Escape', function () {
    expect(modalSource('maison-modal.tsx'))
        ->toContain('aria-modal="true"')
        ->toContain("event.key === 'Escape'");
});

test('the layout wires real modals instead of placeholders', function () {
    expect(File::get(resource_path('js/layouts/frontend-layout.tsx')))
        ->toContain('MaisonModals')
        ->toContain("'auth'")
        ->not->toContain('ModalPlaceholder');
});

test('the auth modal uses the shared maison modal shell', function () {
    expect(modalSource('auth-modal.tsx'))
        ->toContain('MaisonModal')
        ->toContain('loginStore.form()')
        ->toContain('registerStore.form()');
});
