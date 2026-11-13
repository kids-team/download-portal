<?php

use App\Http\Controllers\Api\V1\Admin\AudienceController as AdminAudienceController;
use App\Http\Controllers\Api\V1\Admin\BibleCatalogController;
use App\Http\Controllers\Api\V1\Admin\BibleReferenceController;
use App\Http\Controllers\Api\V1\Admin\BibleVerseController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\CurrentUserController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\DashboardLayoutController;
use App\Http\Controllers\Api\V1\Admin\DocumentController as AdminDocumentController;
use App\Http\Controllers\Api\V1\Admin\DocumentTreeController;
use App\Http\Controllers\Api\V1\Admin\DownloadController;
use App\Http\Controllers\Api\V1\Admin\MediaAssetController;
use App\Http\Controllers\Api\V1\Admin\ProfileController;
use App\Http\Controllers\Api\V1\Admin\TagController as AdminTagController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
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
        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('dashboard', [DashboardController::class, 'show'])->name('dashboard');
        Route::get('dashboard/layout', [DashboardLayoutController::class, 'show'])->name('dashboard.layout.show');
        Route::put('dashboard/layout', [DashboardLayoutController::class, 'update'])->name('dashboard.layout.update');
        Route::get('bible/books', [BibleCatalogController::class, 'index'])->name('bible.books');
        Route::get('bible/schema', [BibleCatalogController::class, 'schema'])->name('bible.schema');
        Route::get('bible/verses', [BibleVerseController::class, 'show'])->name('bible.verses');
        Route::get('documents/tree', [DocumentTreeController::class, 'index'])->name('documents.tree');
        Route::patch('documents/{document}/tree-position', [DocumentTreeController::class, 'move'])
            ->name('documents.tree-position');
        Route::apiResource('documents', AdminDocumentController::class)
            ->middlewareFor('store', 'can:create,'.Document::class)
            ->middlewareFor('update', 'can:update,document')
            ->middlewareFor('destroy', 'can:delete,document');
        Route::apiResource('documents.downloads', DownloadController::class)
            ->scoped()
            ->middlewareFor('store', 'can:update,document');
        Route::apiResource('documents.bible-references', BibleReferenceController::class)
            ->only(['index', 'store', 'destroy']);
        Route::apiResource('media-assets', MediaAssetController::class)
            ->middlewareFor('store', 'can:create,'.MediaAsset::class)
            ->middlewareFor('update', 'can:update,media_asset')
            ->middlewareFor('destroy', 'can:delete,media_asset');
        Route::apiResources([
            'categories' => AdminCategoryController::class,
            'tags' => AdminTagController::class,
            'audiences' => AdminAudienceController::class,
        ]);
        Route::middleware('role:admin')->group(function (): void {
            Route::apiResource('users', AdminUserController::class)->only(['index', 'store', 'update']);
        });
    });
});
