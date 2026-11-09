<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PricingNotificationRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class WelcomeController extends Controller
{
    public function index(): View
    {
        return view('welcome');
    }

    /**
     * Placeholder handler for the Pricing "Notify Me" form.
     *
     * TODO: Connect the form to the backend mailing-list or notification service.
     */
    public function notifyPricing(PricingNotificationRequest $request): JsonResponse|RedirectResponse
    {
        $request->validated();

        $message = (string) config('landing.content.pricing.notify_form.success_message');

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'placeholder_acknowledged',
                'message' => $message,
            ]);
        }

        return redirect()->to(route('welcome').'#pricing')
            ->with('pricing_notify_status', $message);
    }

    public function privacy(): View
    {
        return view('legal.placeholder', [
            'pageTitle' => 'Privacy Policy',
            'pageSummary' => 'Official GhostModsSync Privacy Policy placeholder. Final legal documentation by Ghost Inc Gaming (a JNK Designs company) will be published here prior to commercial launch.',
        ]);
    }

    public function terms(): View
    {
        return view('legal.placeholder', [
            'pageTitle' => 'Terms and Conditions',
            'pageSummary' => 'Official GhostModsSync Terms and Conditions placeholder. Final service terms for VTC leaders and members will be published here prior to commercial launch.',
        ]);
    }

    public function contact(): View
    {
        return view('legal.placeholder', [
            'pageTitle' => 'Contact GhostModsSync',
            'pageSummary' => 'Official GhostModsSync support and community contact portal placeholder. Direct support channels and Discord links will be connected prior to launch.',
        ]);
    }
}
