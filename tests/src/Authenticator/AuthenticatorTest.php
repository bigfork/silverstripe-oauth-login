<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Authenticator;

use Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator;
use Bigfork\SilverStripeOAuth\Client\Authenticator\LoginHandler;
use Bigfork\SilverStripeOAuth\Client\Test\LoginTestCase;

class AuthenticatorTest extends LoginTestCase
{
    public function testGetLoginHandler()
    {
        $authenticator = new Authenticator;
        $this->assertInstanceOf(LoginHandler::class, $authenticator->getLoginHandler('test'));
    }
}
