<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardOverviewService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardOverviewService $overview,
    ) {}

    public function index(Request $request): View
    {
        // Re-read from the database so every column (e.g. last_login_at) is loaded.
        $user = $request->user()->fresh();

        try {
            $data = $this->overview->forUser($user);
            $data['loadFailed'] = false;
        } catch (Throwable $e) {
            report($e);
            $data = ['loadFailed' => true, 'user' => $user];
        }

        return view('dashboard', $data);
    }
}
