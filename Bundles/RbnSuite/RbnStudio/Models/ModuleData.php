<?php

declare(strict_types=1);

namespace Rbn\Framework\Bundles\RbnSuite\RbnStudio\Models;

use Rbn\Framework\Core\Base\Data\BaseConfig;
use Rbn\Framework\Core\Base\Attributes\Bundle;
use Rbn\Framework\Core\Routes\Route;

/**
 * ModuleData - RbnStudio Bundle Identity and Registration Center 🎨🚀🏛️⚓
 * RBN 3.5 Masterpiece Standard.
 */
#[Bundle(
    name: 'studio',
    context: 'panel',
    map: StudioMap::MAP
)]
class ModuleData extends BaseConfig
{
    /**
     * CENTRALIZED REGISTRATION MAP 🏛️
     */
    public function registerMap(): array
    {
        return [
            'services' => [],
            'providers' => [],
        ];
    }

    /**
     * SOVEREIGN CUSTOM ROUTES 🎯
     */
    public function registerRoutes(): void
    {
        // 📂 Categories Sovereign Routes
        Route::prefix('studio/categories')->controller('CategoriesController')->group(function () {
            Route::get('/', 'index')->name('admin.studio.categories.index');
            Route::post('save', 'save')->name('admin.studio.categories.save');
            Route::post('status', 'status')->name('admin.studio.categories.status');
            Route::get('delete/{id:[0-9]+}', 'delete')->name('admin.studio.categories.delete');
            Route::post('delete/{id:[0-9]+}', 'delete')->name('admin.studio.categories.delete');
            Route::get('modal/{id?}', 'modal')->name('admin.studio.categories.modal');
            Route::post('reorder', 'bulkOrder')->name('admin.studio.categories.reorder');
        });

        // 📝 Drafts Sovereign Routes
        Route::prefix('studio/drafts')->controller('DraftsController')->group(function () {
            Route::get('/', 'index')->name('admin.studio.drafts.index');
            Route::post('save', 'save')->name('admin.studio.drafts.save');
            Route::get('delete/{id:[0-9]+}', 'delete')->name('admin.studio.drafts.delete');
            Route::post('delete/{id:[0-9]+}', 'delete')->name('admin.studio.drafts.delete');
            Route::get('generate/{id:[0-9]+}', 'generate')->name('admin.studio.drafts.generate');
            Route::get('modal/{id?}', 'modal')->name('admin.studio.drafts.modal');
            Route::post('reorder', 'bulkOrder')->name('admin.studio.drafts.reorder');
        });

        // 📰 Posts Sovereign Routes
        Route::prefix('studio/posts')->controller('PostsController')->group(function () {
            Route::get('/', 'index')->name('admin.studio.posts.index');
            Route::post('save', 'save')->name('admin.studio.posts.save');
            Route::get('delete/{id:[0-9]+}', 'delete')->name('admin.studio.posts.delete');
            Route::post('delete/{id:[0-9]+}', 'delete')->name('admin.studio.posts.delete');
            Route::get('edit/{id?}', 'edit')->name('admin.studio.posts.edit');
            Route::post('generate-image', 'generateImage')->name('admin.studio.posts.generate-image');
            Route::post('rewrite', 'rewrite')->name('admin.studio.posts.rewrite');
            Route::post('reorder', 'bulkOrder')->name('admin.studio.posts.reorder');
            Route::get('social-modal/{id:[0-9]+}', 'socialModal')->name('admin.studio.posts.social-modal');
            Route::post('social-share', 'socialShare')->name('admin.studio.posts.social-share');
        });

        // 🗞️ News Sovereign Routes
        Route::prefix('studio/news')->controller('NewsController')->group(function () {
            Route::get('/', 'index')->name('admin.studio.news.index');
            Route::post('save', 'save')->name('admin.studio.news.save');
            Route::get('delete/{id:[0-9]+}', 'delete')->name('admin.studio.news.delete');
            Route::post('delete/{id:[0-9]+}', 'delete')->name('admin.studio.news.delete');
            Route::get('edit/{id?}', 'edit')->name('admin.studio.news.edit');
            Route::post('generate-image', 'generateImage')->name('admin.studio.news.generate-image');
            Route::post('rewrite', 'rewrite')->name('admin.studio.news.rewrite');
            Route::get('social-modal/{id:[0-9]+}', 'socialModal')->name('admin.studio.news.social-modal');
            Route::post('social-share', 'socialShare')->name('admin.studio.news.social-share');
        });
    }
}
