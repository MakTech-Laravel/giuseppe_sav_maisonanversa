<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunitySession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunitySessionController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $sessions = CommunitySession::query()
            ->with('host')
            ->withCount('participants')
            ->orderBy('starts_at')
            ->get();

        return Inertia::render('admin/community-sessions/index', [
            'sessions' => $sessions->map(fn (CommunitySession $session): array => [
                'id' => (string) $session->id,
                'host' => $session->host->name,
                'location' => $session->translated('location'),
                'starts_at' => $session->starts_at->toIso8601String(),
                'capacity' => $session->capacity,
                'participants_count' => (int) $session->participants_count,
            ]),
            'hosts' => User::query()
                ->orderBy('name')
                ->limit(50)
                ->get(['id', 'name'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                ]),
        ]);
    }

    public function create(Request $request, string $locale): Response
    {
        return Inertia::render('admin/community-sessions/create');
    }

    public function store(Request $request, string $locale): RedirectResponse
    {
        $session = CommunitySession::query()->create($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sessie aangemaakt.')]);

        return redirect()->route('admin.community-sessions.edit', [
            'locale' => $locale,
            'communitySession' => $session->id,
        ]);
    }

    public function edit(Request $request, string $locale, CommunitySession $communitySession): Response
    {
        return Inertia::render('admin/community-sessions/edit', [
            'session' => [
                'id' => (string) $communitySession->id,
                'host_id' => $communitySession->host_id,
                'location' => $communitySession->location,
                'starts_at' => $communitySession->starts_at->format('Y-m-d\TH:i'),
                'capacity' => $communitySession->capacity,
                'level' => $communitySession->level,
                'notes' => $communitySession->notes,
            ],
        ]);
    }

    public function update(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $communitySession->update($this->validated($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sessie bijgewerkt.')]);

        return redirect()->route('admin.community-sessions.edit', [
            'locale' => $locale,
            'communitySession' => $communitySession->id,
        ]);
    }

    public function destroy(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $communitySession->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sessie verwijderd.')]);

        return redirect()->route('admin.community-sessions.index', ['locale' => $locale]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'host_id' => ['required', 'integer', 'exists:users,id'],
            'location' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'capacity' => ['nullable', 'integer', 'min:2'],
            'level' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
