<?php namespace FreedomCore\TrinityCore\Support;

use Illuminate\Support\ServiceProvider;

/**
 * Class SupportServiceProvider
 * @package FreedomCore\TrinityCore\Support
 */
class SupportServiceProvider extends ServiceProvider
{

    /**
     * Register Service Provider
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->registerConsoleCommands();
        }
        if (!$this->app->runningInConsole() || config('app.env') == 'testing') {
            $this->registerAppCommands();
        }
    }

    /**
     * Register the commands accessible from the Console.
     */
    private function registerConsoleCommands()
    {
        $this->commands(DB2Reader\Commands\SyncStructures::class);
    }

    /**
     * Register the commands accessible from the App.
     */
    private function registerAppCommands()
    {
    }
}
