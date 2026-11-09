<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\DependencyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreModDependencyRequest;
use App\Http\Requests\Web\StoreModRequest;
use App\Http\Requests\Web\StoreModVersionRequest;
use App\Models\Group;
use App\Models\Mod;
use App\Models\ModVersion;
use App\Models\User;
use App\Services\Mods\DependencyResolverService;
use App\Services\Mods\ModCatalogueService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class ModController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ModCatalogueService $modService,
        private readonly DependencyResolverService $dependencyResolver,
    ) {}

    public function store(StoreModRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('manageMods', $group);

        /** @var User $user */
        $user = $request->user();

        $this->modService->createMod($group, $user, $request->validated());

        return redirect()
            ->route('groups.show', $group)
            ->with('status', 'mod-created');
    }

    public function storeVersion(StoreModVersionRequest $request, Group $group, Mod $mod): RedirectResponse
    {
        $this->authorize('manageMods', $group);

        /** @var User $user */
        $user = $request->user();

        $result = $this->modService->createModVersion($mod, $user, $request->validated());

        return redirect()
            ->route('groups.show', $group)
            ->with('status', 'mod-version-created')
            ->with('upload_instructions', $result['upload'] ?? null);
    }

    public function storeDependency(
        StoreModDependencyRequest $request,
        Group $group,
        Mod $mod,
        ModVersion $modVersion,
    ): RedirectResponse {
        $this->authorize('manageMods', $group);

        $validated = $request->validated();

        $this->dependencyResolver->addDependency(
            modVersion: $modVersion,
            relatedModId: (int) $validated['related_mod_id'],
            type: DependencyType::from((string) $validated['dependency_type']),
            relatedModVersionId: isset($validated['related_mod_version_id']) ? (int) $validated['related_mod_version_id'] : null,
        );

        return redirect()
            ->route('groups.show', $group)
            ->with('status', 'mod-dependency-added');
    }
}
