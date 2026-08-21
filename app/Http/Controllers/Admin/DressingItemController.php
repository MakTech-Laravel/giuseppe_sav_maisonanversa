<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DressingItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DressingItemController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $items = DressingItem::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return Inertia::render('admin/dressing-items/index', [
            'items' => $items->map(fn (DressingItem $item): array => [
                'id' => (string) $item->id,
                'name' => $item->translated('name'),
                'slug' => $item->slug,
                'category' => $item->translated('category'),
                'status' => $item->status,
                'sort_order' => $item->sort_order,
                'is_published' => $item->is_published,
            ]),
        ]);
    }

    public function create(Request $request, string $locale): Response
    {
        return Inertia::render('admin/dressing-items/create');
    }

    public function store(Request $request, string $locale): RedirectResponse
    {
        $item = DressingItem::query()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Dressing item aangemaakt.')]);

        return redirect()->route('admin.dressing-items.edit', [
            'locale' => $locale,
            'dressingItem' => $item->id,
        ]);
    }

    public function edit(Request $request, string $locale, DressingItem $dressingItem): Response
    {
        return Inertia::render('admin/dressing-items/edit', [
            'item' => [
                'id' => (string) $dressingItem->id,
                'name' => $dressingItem->name,
                'slug' => $dressingItem->slug,
                'category' => $dressingItem->category,
                'image_key' => $dressingItem->image_key,
                'status' => $dressingItem->status,
                'sort_order' => $dressingItem->sort_order,
                'is_published' => $dressingItem->is_published,
            ],
        ]);
    }

    public function update(Request $request, string $locale, DressingItem $dressingItem): RedirectResponse
    {
        $dressingItem->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Dressing item bijgewerkt.')]);

        return redirect()->route('admin.dressing-items.edit', [
            'locale' => $locale,
            'dressingItem' => $dressingItem->id,
        ]);
    }

    public function destroy(Request $request, string $locale, DressingItem $dressingItem): RedirectResponse
    {
        $dressingItem->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Dressing item verwijderd.')]);

        return redirect()->route('admin.dressing-items.index', ['locale' => $locale]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'image_key' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:coming_soon,available'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['required', 'boolean'],
        ]);
        $validated['slug'] = Str::slug((string) ($validated['slug'] ?: $validated['name']));

        return $validated;
    }
}
