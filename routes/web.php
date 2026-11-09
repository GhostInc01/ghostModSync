<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\ConvoyProfileController;
use App\Http\Controllers\Web\DeviceController;
use App\Http\Controllers\Web\GroupController;
use App\Http\Controllers\Web\ModController;
use App\Http\Controllers\Web\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
Route::post('/pricing-notify', [WelcomeController::class, 'notifyPricing'])
    ->middleware('throttle:10,1')
    ->name('pricing.notify');
Route::get('/privacy-policy', [WelcomeController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms-and-conditions', [WelcomeController::class, 'terms'])->name('legal.terms');
Route::get('/contact', [WelcomeController::class, 'contact'])->name('legal.contact');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::patch('/devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
    Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');

    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/create', [GroupController::class, 'create'])->name('groups.create');
    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/invitations/{token}/accept', [GroupController::class, 'acceptInvitation'])
        ->name('groups.invitations.accept');

    Route::scopeBindings()->group(function () {
        Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
        Route::get('/groups/{group}/members', [GroupController::class, 'members'])->name('groups.members');
        Route::get('/groups/{group}/join-requests', [GroupController::class, 'joinRequests'])->name('groups.join-requests');
        Route::get('/groups/{group}/invitations', [GroupController::class, 'invitations'])->name('groups.invitations');
        Route::get('/groups/{group}/settings', [GroupController::class, 'settings'])->name('groups.settings');

        Route::patch('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
        Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
        Route::post('/groups/{group}/transfer-ownership', [GroupController::class, 'transferOwnership'])
            ->name('groups.transfer-ownership');

        Route::post('/groups/{group}/join', [GroupController::class, 'join'])->name('groups.join');
        Route::patch('/groups/{group}/join-requests/{joinRequest}', [GroupController::class, 'reviewJoinRequest'])
            ->name('groups.join-requests.review');
        Route::patch('/groups/{group}/memberships/{membership}', [GroupController::class, 'updateMembership'])
            ->name('groups.memberships.update');
        Route::delete('/groups/{group}/memberships/{membership}', [GroupController::class, 'destroyMembership'])
            ->name('groups.memberships.destroy');
        Route::post('/groups/{group}/invitations', [GroupController::class, 'storeInvitation'])
            ->name('groups.invitations.store');
        Route::delete('/groups/{group}/invitations/{invitation}', [GroupController::class, 'destroyInvitation'])
            ->name('groups.invitations.destroy');

        Route::post('/groups/{group}/mods', [ModController::class, 'store'])
            ->name('groups.mods.store');
        Route::post('/groups/{group}/mods/{mod}/versions', [ModController::class, 'storeVersion'])
            ->name('groups.mods.versions.store');
        Route::post('/groups/{group}/mods/{mod}/versions/{modVersion:id}/dependencies', [ModController::class, 'storeDependency'])
            ->name('groups.mods.versions.dependencies.store');

        Route::post('/groups/{group}/profiles', [ConvoyProfileController::class, 'store'])
            ->name('groups.profiles.store');
        Route::put('/groups/{group}/profiles/{convoyProfile}/mods', [ConvoyProfileController::class, 'syncMods'])
            ->name('groups.profiles.mods.sync');
        Route::patch('/groups/{group}/profiles/{convoyProfile}/approvals/{syncApproval:id}', [ConvoyProfileController::class, 'reviewSyncApproval'])
            ->name('groups.profiles.approvals.review');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
