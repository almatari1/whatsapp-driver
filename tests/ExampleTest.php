<?php

namespace Botman\DriverWhatsapp\Tests;

use Orchestra\Testbench\TestCase;
use Botman\DriverWhatsapp\DriverWhatsappServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [DriverWhatsappServiceProvider::class];
    }
    
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
