<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Authenticator;

use Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator;
use Bigfork\SilverStripeOAuth\Client\Test\TestCase;
use Config;
use Controller;

class AuthenticatorTest extends TestCase
{
    public function testGetLoginForm()
    {
        Config::inst()->remove('Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator', 'providers');
        $controller = new Controller;
        $form = Authenticator::get_login_form($controller);

        $this->assertNull($form, 'get_login_form should return null if no providers have been set up');

        $providers = [
            'ProviderOne' => [
                'scopes' => ['email']
            ]
        ];
        Config::inst()->update('Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator', 'providers', $providers);

        $form = Authenticator::get_login_form($controller);
        $this->assertInstanceOf('Bigfork\SilverStripeOAuth\Client\Form\LoginForm', $form);
        $this->assertEquals('LoginForm', $form->getName());
    }
}
