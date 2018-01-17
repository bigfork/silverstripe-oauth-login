<?php

namespace Bigfork\SilverStripeOAuth\Client\Factory;

use Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator;
use Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;

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
        $mappers = Config::inst()->get(self::class, 'mappers');

        if (isset($mappers[$name])) {
            return Injector::inst()->get($mappers[$name]);
        }

        return Injector::inst()->createWithArgs(GenericMemberMapper::class, [$name]);
    }
}
