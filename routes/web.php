<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CampaignController;
use App\Models\Campaign;
use App\Http\Controllers\PublicCampaignController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $demoCampaign = Campaign::query()
        ->where('is_active', true)
        ->latest('id')
        ->first(['name', 'public_token']);

    return view('welcome', [
        'demoCampaign' => $demoCampaign,
    ]);
});

Route::get('/play/{token}', [PublicCampaignController::class, 'show'])->name('play.show');
Route::post('/play/{token}/spin', [PublicCampaignController::class, 'spin'])->name('play.spin');

Route::prefix('admin')->group(function () {
    Route::get('/login', [AuthController::class, 'show']);
    Route::post('/login', [AuthController::class, 'store']);
    Route::post('/logout', [AuthController::class, 'destroy']);

    Route::middleware('admin.auth')->group(function () {
        Route::get('/campaigns', [CampaignController::class, 'index'])->name('admin.campaigns.index');
        Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('admin.campaigns.create');
        Route::post('/campaigns', [CampaignController::class, 'store'])->name('admin.campaigns.store');
        Route::get('/campaigns/{campaign}/edit', [CampaignController::class, 'edit'])->name('admin.campaigns.edit');
        Route::put('/campaigns/{campaign}', [CampaignController::class, 'update'])->name('admin.campaigns.update');
    });
});
