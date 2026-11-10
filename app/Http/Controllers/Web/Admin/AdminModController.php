<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Enums\ModApprovalStatus;
use App\Enums\ModerationReportStatus;
use App\Enums\ModVersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\ReviewGroupModApprovalRequest;
use App\Http\Requests\Web\Admin\ReviewModerationReportRequest;
use App\Http\Requests\Web\Admin\UpdateModVersionStatusRequest;
use App\Models\GroupModApproval;
use App\Models\Mod;
use App\Models\ModerationReport;
use App\Models\ModVersion;
use App\Models\User;
use App\Services\Admin\PlatformModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminModController extends Controller
{
    public function __construct(
        private readonly PlatformModerationService $moderation,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('access-admin-dashboard');

        $search = trim((string) $request->query('q', ''));
        $filter = trim((string) $request->query('filter', ''));

        $mods = Mod::withTrashed()
            ->with(['group', 'game', 'creator'])
            ->withCount('versions')
            ->when($search !== '', function ($q) use ($search): void {
                $term = '%'.addcslashes($search, '\\%_').'%';
                $q->where(fn ($sub) => $sub
                    ->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('author_name', 'like', $term));
            })
            ->when($filter === 's3', fn ($q) => $q->where('source_type', 's3_upload'))
            ->when($filter === 'steam', fn ($q) => $q->where('source_type', 'steam_workshop'))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $pendingApprovals = GroupModApproval::query()
            ->where('status', ModApprovalStatus::Pending->value)
            ->with(['group', 'modVersion.mod'])
            ->latest()
            ->limit(15)
            ->get();

        $duplicateHashes = DB::table('mod_versions')
            ->whereNull('deleted_at')
            ->whereNotNull('sha256_hash')
            ->selectRaw('sha256_hash, COUNT(*) as occurrences')
            ->groupBy('sha256_hash')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->get();

        $reports = ModerationReport::query()
            ->where('status', ModerationReportStatus::Pending->value)
            ->with(['reporter', 'reportable'])
            ->latest()
            ->limit(15)
            ->get();

        return view('admin.mods.index', [
            'mods' => $mods,
            'pendingApprovals' => $pendingApprovals,
            'duplicateHashes' => $duplicateHashes,
            'reports' => $reports,
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public function show(int $modId): View
    {
        Gate::authorize('access-admin-dashboard');

        $mod = Mod::withTrashed()
            ->with([
                'group',
                'game',
                'creator',
                'versions.source',
                'versions.requiredDlcs',
                'versions.dependencies.relatedMod',
                'versions.groupApprovals.group',
            ])
            ->findOrFail($modId);

        return view('admin.mods.show', [
            'mod' => $mod,
        ]);
    }

    public function updateVersionStatus(UpdateModVersionStatusRequest $request, ModVersion $modVersion): RedirectResponse
    {
        Gate::authorize('access-admin-dashboard');

        /** @var User $actor */
        $actor = $request->user()->fresh();

        $status = ModVersionStatus::from((string) $request->validated('status'));
        $this->moderation->updateModVersionStatus($actor, $modVersion, $status, $request->validated('reason'));

        return redirect()
            ->route('admin.mods.show', $modVersion->mod_id)
            ->with('status', 'Mod version status updated.');
    }

    public function reviewApproval(ReviewGroupModApprovalRequest $request, GroupModApproval $approval): RedirectResponse
    {
        Gate::authorize('access-admin-dashboard');

        /** @var User $actor */
        $actor = $request->user()->fresh();

        $status = ModApprovalStatus::from((string) $request->validated('status'));
        $this->moderation->reviewGroupModApproval($actor, $approval, $status, $request->validated('review_notes'));

        return back()->with('status', 'Mod approval reviewed.');
    }

    public function reviewReport(ReviewModerationReportRequest $request, ModerationReport $report): RedirectResponse
    {
        Gate::authorize('access-admin-dashboard');

        /** @var User $actor */
        $actor = $request->user()->fresh();

        $status = ModerationReportStatus::from((string) $request->validated('status'));
        $this->moderation->reviewModerationReport($actor, $report, $status, $request->validated('review_notes'));

        return back()->with('status', 'Moderation report updated.');
    }
}
