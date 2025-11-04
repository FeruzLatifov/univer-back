<?php

namespace App\Providers;

use App\Contracts\Repositories\MenuRepositoryInterface;
use App\Contracts\Services\MenuServiceInterface;
use App\Repositories\MenuRepository;
use App\Services\Menu\MenuService;
use Illuminate\Support\ServiceProvider;

/**
 * Menu Service Provider
 *
 * Binds interfaces to implementations (Dependency Inversion Principle)
 * Makes code testable and microservice-ready
 *
 * @microservice-ready Easy to swap implementations
 */
class MenuServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Bind Repository Interface
        $this->app->singleton(MenuRepositoryInterface::class, function ($app) {
            return new MenuRepository();
        });

        // Bind Service Interface
        $this->app->singleton(MenuServiceInterface::class, function ($app) {
            return new MenuService(
                $app->make(MenuRepositoryInterface::class)
            );
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Register event listeners if needed
        // Event::listen(RoleUpdated::class, InvalidateMenuCache::class);
    }

    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return [
            MenuRepositoryInterface::class,
            MenuServiceInterface::class,
        ];
    }
}
