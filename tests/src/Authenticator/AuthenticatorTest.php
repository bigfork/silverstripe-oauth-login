<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Authenticator;

use Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator;
use Bigfork\SilverStripeOAuth\Client\Test\TestCase;
use Controller;

class AuthenticatorTest extends TestCase
{
    public function testGetLoginForm()
    {
        $controller = new Controller;
        $form = Authenticator::get_login_form($controller);

        $this->assertInstanceOf('Bigfork\SilverStripeOAuth\Client\Form\LoginForm', $form);
        $this->assertEquals('LoginForm', $form->getName());
    }
}
