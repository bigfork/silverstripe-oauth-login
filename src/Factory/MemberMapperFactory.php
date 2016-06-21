<?php

namespace Bigfork\SilverStripeOAuth\Client\Factory;

use Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator;
use Config;
use Injector;

class MemberMapperFactory
{
    /**
     * @var array
     */
    private static $mappers = [];

    /**
     * @param string $name
     * @return Bigfork\SilverStripeOAuth\Client\Mapper\MemberMapperInterface
     */
    public function createMapper($name)
    {
        $mappers = Config::inst()->get(__CLASS__, 'mappers');

        if (isset($mappers[$name])) {
            return Injector::inst()->get($mappers[$name]);
        }

        return Injector::inst()->createWithArgs('Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper', [$name]);
    }
}
