<?php

namespace Bigfork\SilverStripeOAuth\Client\Authenticator;

use Bigfork\SilverStripeOAuth\Client\Form\LoginForm;
use Config;
use Controller;
use Injector;

class Authenticator extends \MemberAuthenticator
{
    /**
     * @var array
     */
    private static $providers = [];

    /**
     * @return LoginForm
     */
    public static function get_login_form(Controller $controller)
    {
        return Injector::inst()->create('Bigfork\SilverStripeOAuth\Client\Form\LoginForm', $controller, 'LoginForm');
    }

    /**
     * @return string
     */
    public static function get_name()
    {
        return _t('Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator.TITLE', 'Social sign-on');
    }
}
