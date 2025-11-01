<?php

namespace App\Providers;

use App\Services\DatabaseTranslationLoader;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;

/**
 * Translation Service Provider
 *
 * Database-driven translation tizimini sozlaydi
 */
class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // DatabaseTranslationLoader ni ro'yxatdan o'tkazish
        $this->app->singleton('translation.loader', function ($app) {
            return new DatabaseTranslationLoader($app['files'], $app['path.lang']);
        });

        // Translator ni qayta bog'lash
        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            // Default til (uz)
            $locale = $app['config']['app.locale'];

            $trans = new Translator($loader, $locale);
            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Trans helper uchun missing translation handler
        $this->app['translator']->addMissingCallback(function ($key, $replacements, $locale, $fallback) {
            // Missing translation ni database ga qo'shish
            if ($this->app->bound('translation.loader')) {
                $loader = $this->app->make('translation.loader');
                if ($loader instanceof DatabaseTranslationLoader) {
                    $loader->addMissingTranslation($key, $locale);
                }
            }
        });
    }
}
