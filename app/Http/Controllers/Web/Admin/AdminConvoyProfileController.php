<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConvoyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminConvoyProfileController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('access-admin-dashboard');

        $search = trim((string) $request->query('q', ''));
        $gameCode = trim((string) $request->query('game', ''));

        $profiles = ConvoyProfile::withTrashed()
            ->with(['group', 'game', 'creator'])
            ->withCount(['profileMods', 'revisions', 'syncSessions'])
            ->when($search !== '', function ($q) use ($search): void {
                $term = '%'.addcslashes($search, '\\%_').'%';
                $q->where(fn ($sub) => $sub
                    ->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term));
            })
            ->when($gameCode !== '', fn ($q) => $q->whereHas('game', fn ($g) => $g->where('code', $gameCode)))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.convoys.index', [
            'profiles' => $profiles,
            'search' => $search,
            'gameCode' => $gameCode,
        ]);
    }

    public function show(int $profileId): View
    {
        Gate::authorize('access-admin-dashboard');

        $profile = ConvoyProfile::withTrashed()
            ->with([
                'group',
                'game',
                'creator',
                'profileMods.modVersion.mod',
                'revisions.publisher',
            ])
            ->findOrFail($profileId);

        return view('admin.convoys.show', [
            'profile' => $profile,
        ]);
    }
}
