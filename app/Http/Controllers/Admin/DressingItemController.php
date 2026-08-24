<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDressingItemRequest;
use App\Http\Requests\Admin\UpdateDressingItemRequest;
use App\Models\DressingItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
                'image_url' => $item->resolvedImageUrl(),
            ]),
        ]);
    }

    public function create(Request $request, string $locale): Response
    {
        return Inertia::render('admin/dressing-items/create');
    }

    public function store(StoreDressingItemRequest $request, string $locale): RedirectResponse
    {
        $data = $request->safe()->except(['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($request->file('image'));
        }

        $item = DressingItem::query()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Dressing item aangemaakt.')]);

        return redirect()->route('admin.dressing-items.show', [
            'locale' => $locale,
            'dressingItem' => $item->id,
        ]);
    }

    public function show(string $locale, DressingItem $dressingItem): Response
    {
        return Inertia::render('admin/dressing-items/show', [
            'item' => $this->itemDetails($dressingItem),
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
                'description' => $dressingItem->description ?? '',
                'image_key' => $dressingItem->image_key,
                'image_url' => $dressingItem->resolvedImageUrl(),
                'status' => $dressingItem->status,
                'sort_order' => $dressingItem->sort_order,
                'is_published' => $dressingItem->is_published,
            ],
        ]);
    }

    public function update(UpdateDressingItemRequest $request, string $locale, DressingItem $dressingItem): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image')) {
            $this->deleteImage($dressingItem);
            $data['image_path'] = $this->storeImage($request->file('image'));
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($dressingItem);
            $data['image_path'] = null;
        }

        $dressingItem->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Dressing item bijgewerkt.')]);

        return redirect()->route('admin.dressing-items.show', [
            'locale' => $locale,
            'dressingItem' => $dressingItem->id,
        ]);
    }

    public function destroy(Request $request, string $locale, DressingItem $dressingItem): RedirectResponse
    {
        $this->deleteImage($dressingItem);
        $dressingItem->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Dressing item verwijderd.')]);

        return redirect()->route('admin.dressing-items.index', ['locale' => $locale]);
    }

    /**
     * @return array<string, mixed>
     */
    private function itemDetails(DressingItem $dressingItem): array
    {
        return [
            'id' => (string) $dressingItem->id,
            'name' => $dressingItem->translated('name'),
            'slug' => $dressingItem->slug,
            'category' => $dressingItem->translated('category'),
            'description' => $dressingItem->translated('description'),
            'image_url' => $dressingItem->resolvedImageUrl(),
            'status' => $dressingItem->status,
            'sort_order' => $dressingItem->sort_order,
            'is_published' => $dressingItem->is_published,
        ];
    }

    private function storeImage(UploadedFile $file): string
    {
        return $file->store('dressing-items', 'public');
    }

    private function deleteImage(DressingItem $dressingItem): void
    {
        if ($dressingItem->image_path === null || $dressingItem->image_path === '') {
            return;
        }

        Storage::disk('public')->delete($dressingItem->image_path);
    }
}
