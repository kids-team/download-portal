<?php

use App\Http\Controllers\Api\V1\Admin\AudienceController as AdminAudienceController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\CurrentUserController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\DocumentController as AdminDocumentController;
use App\Http\Controllers\Api\V1\Admin\MediaAssetController;
use App\Http\Controllers\Api\V1\Admin\TagController as AdminTagController;
use App\Http\Controllers\Api\V1\Public\AudienceController as PublicAudienceController;
use App\Http\Controllers\Api\V1\Public\CategoryController as PublicCategoryController;
use App\Http\Controllers\Api\V1\Public\DocumentController as PublicDocumentController;
use App\Http\Controllers\Api\V1\Public\DownloadDeliveryController;
use App\Http\Controllers\Api\V1\Public\SearchController;
use App\Http\Controllers\Api\V1\Public\TagController as PublicTagController;
use App\Models\Document;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function (): void {
    Route::apiResource('documents', PublicDocumentController::class)->only(['index', 'show']);
    Route::apiResources([
        'categories' => PublicCategoryController::class,
        'tags' => PublicTagController::class,
        'audiences' => PublicAudienceController::class,
    ], ['only' => ['index', 'show']]);
    Route::get('search', [SearchController::class, 'index'])->name('search');
    Route::get('downloads/{download}/delivery', [DownloadDeliveryController::class, 'show'])->name('downloads.delivery');

    Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum', 'role:admin,editor'])->group(function (): void {
        Route::get('me', [CurrentUserController::class, 'show'])->name('me');
        Route::get('dashboard', [DashboardController::class, 'show'])->name('dashboard');
        Route::apiResource('documents', AdminDocumentController::class)
            ->middlewareFor('store', 'can:create,'.Document::class)
            ->middlewareFor('update', 'can:update,document')
            ->middlewareFor('destroy', 'can:delete,document');
        Route::apiResource('media-assets', MediaAssetController::class)
            ->middlewareFor('store', 'can:create,'.MediaAsset::class)
            ->middlewareFor('update', 'can:update,media_asset')
            ->middlewareFor('destroy', 'can:delete,media_asset');
        Route::apiResources([
            'categories' => AdminCategoryController::class,
            'tags' => AdminTagController::class,
            'audiences' => AdminAudienceController::class,
        ]);
    });
});
