<?php
namespace Malmatari\Drivers\Whatsapp\Providers;

use Illuminate\Support\ServiceProvider;
use BotMan\BotMan\Drivers\DriverManager;
use Malmatari\Drivers\Whatsapp\WhatsappDriver;
use Malmatari\Drivers\Whatsapp\WhatsappLocationDriver;
use Malmatari\Drivers\Whatsapp\WhatsappImageDriver;

class WhatsappServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {


        if (! $this->isRunningInBotManStudio()) {
            $this->loadDrivers();

            $this->publishes([
                __DIR__.'/../../stubs/whatsapp.php' => config_path('botman/whatsapp.php'),
            ]);

            $this->mergeConfigFrom(__DIR__.'/../../stubs/whatsapp.php', 'botman.whatsapp');


        }

    }



     /**
     * Load BotMan drivers.
     */
    protected function loadDrivers()
    {
        DriverManager::loadDriver(WhatsappDriver::class);
        DriverManager::loadDriver(WhatsappImageDriver::class);
        DriverManager::loadDriver(WhatsappLocationDriver::class);

    }

     /**
     * @return bool
     */
    protected function isRunningInBotManStudio()
    {
        return class_exists(StudioServiceProvider::class);
    }


}
