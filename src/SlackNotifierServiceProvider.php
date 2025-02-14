<?php

namespace Jmrashed\SlackNotifier;

use Illuminate\Support\ServiceProvider;

class SlackNotifierServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register configuration file for the package
        $this->registerConfig();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Check if the application is running in the console
        if ($this->app->runningInConsole()) {
            // Publish the configuration file for the package
            $this->publishConfigs();
        }
    }

    /**
     * Register the configuration file for the package.
     *
     * @return void
     */
    protected function registerConfig(): void
    {
        // Merge the package's configuration with the application's configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/slack-notifier.php', 
            'slack-notifier'
        );
    }

    /**
     * Publish the configuration file to the application's config directory.
     *
     * @return void
     */
    protected function publishConfigs(): void
    {
        // Publish the package's configuration file for customization
        $this->publishes([
            __DIR__.'/../config/slack-notifier.php' => config_path('slack-notifier.php'),
        ], 'slack-notifier');
    }
}
