<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateDeviceRequest;
use App\Models\Device;
use App\Models\User;
use App\Services\Devices\DeviceRegistrationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DeviceRegistrationService $deviceRegistrationService,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $devices = $user->devices()
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->get();

        return view('devices.index', [
            'devices' => $devices,
        ]);
    }

    public function update(UpdateDeviceRequest $request, Device $device): RedirectResponse
    {
        $this->authorize('update', $device);

        /** @var User $user */
        $user = $request->user();

        $this->deviceRegistrationService->updateDevice($device, $user, $request->validated());

        return redirect()
            ->route('devices.index')
            ->with('status', 'device-updated');
    }

    public function destroy(Request $request, Device $device): RedirectResponse
    {
        $this->authorize('revoke', $device);

        /** @var User $user */
        $user = $request->user();

        if ($request->boolean('remove')) {
            $this->deviceRegistrationService->removeDevice($device, $user);

            return redirect()
                ->route('devices.index')
                ->with('status', 'device-removed');
        }

        $this->deviceRegistrationService->revokeDevice($device, $user);

        return redirect()
            ->route('devices.index')
            ->with('status', 'device-revoked');
    }
}
