<?php

namespace Bigfork\SilverStripeOAuth\Client\Authenticator;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Authenticator as SilverStripeAuthenticator;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\LogoutHandler;

class Authenticator implements SilverStripeAuthenticator
{
    /**
     * @var array
     * @config
     */
    private static $providers = [];

    public function supportedServices()
    {
        return SilverStripeAuthenticator::LOGIN | SilverStripeAuthenticator::LOGOUT;
    }

    public function getLoginHandler($link)
    {
        return LoginHandler::create($link, $this);
    }

    public function authenticate(array $data, HTTPRequest $request, ValidationResult &$result = null)
    {
        // No-op
    }

    public function checkPassword(Member $member, $password, ValidationResult &$result = null)
    {
        // No-op
    }

    public function getLogoutHandler($link)
    {
        // return the default handler
        return LogoutHandler::create($link, $this);
    }

    public function getLostPasswordHandler($link)
    {
        // No-op
    }

    public function getChangePasswordHandler($link)
    {
        // No-op
    }
}
