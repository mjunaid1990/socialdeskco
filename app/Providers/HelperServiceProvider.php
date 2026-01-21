<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Helpers\SettingsHelper;

class HelperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register SettingsHelper as a singleton
        $this->app->singleton(SettingsHelper::class, function ($app) {
            return new SettingsHelper();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the settings helper as a blade directive
        Blade::directive('setting', function ($expression) {
            return "<?php echo \\App\\Helpers\\SettingsHelper::get($expression); ?>";
        });
    }
}
