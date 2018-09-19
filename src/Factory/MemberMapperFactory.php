<?php

namespace Bigfork\SilverStripeOAuth\Client\Factory;

use Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper;
use Bigfork\SilverStripeOAuth\Client\Mapper\MemberMapperInterface;
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
     * @return MemberMapperInterface
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
