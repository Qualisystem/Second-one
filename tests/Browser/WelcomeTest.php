<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class WelcomeTest extends DuskTestCase
{
    /**
     * A basic browser test example.
     */
    public function test_load_welcome_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSee('OpenGRC')
                ->assertSee('Login');
        });
    }

    public function test_click_login_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->clickLink('Login')
                ->assertPathIs('/app/login');
        });
    }
}
