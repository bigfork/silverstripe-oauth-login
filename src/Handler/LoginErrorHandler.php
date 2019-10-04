<?php

namespace Bigfork\SilverStripeOAuth\Client\Handler;

use Exception;
use League\OAuth2\Client\Provider\AbstractProvider;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Security;

class LoginErrorHandler implements ErrorHandler
{
    public function handleError(AbstractProvider $provider, HTTPRequest $request, Exception $exception)
    {
        $message = '';
        foreach (['error_description', 'error_message', 'error', 'message'] as $var) {
            if ($message = $request->getVar($var)) {
                break;
            }
        }

        if ($message) {
            return Security::permissionFailure(null, $message);
        }
    }
}
