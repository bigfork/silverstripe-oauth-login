<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Factory;

use Bigfork\SilverStripeOAuth\Client\Factory\MemberMapperFactory;
use Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper;
use Bigfork\SilverStripeOAuth\Client\Mapper\MemberMapperInterface;
use Bigfork\SilverStripeOAuth\Client\Test\LoginTestCase;
use Config;
use Injector;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Member;

class MemberMapperFactoryTest extends LoginTestCase
{
    public function testCreateMapper()
    {
        Config::inst()->update(
            'Bigfork\SilverStripeOAuth\Client\Factory\MemberMapperFactory',
            'mappers',
            ['TestProvider' => 'Bigfork\SilverStripeOAuth\Client\Test\Factory\MemberMapperFactoryTest_Mapper']
        );

        $factory = new MemberMapperFactory();
        $this->assertInstanceOf(
            'Bigfork\SilverStripeOAuth\Client\Test\Factory\MemberMapperFactoryTest_Mapper',
            $factory->createMapper('TestProvider')
        );

        // Store original
        $injector = Injector::inst();

        $genericMapper = new GenericMemberMapper('test');

        $mockInjector = $this->getMock('Injector', ['createWithArgs']);
        $mockInjector->expects($this->once())
            ->method('createWithArgs')
            ->with('Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper', ['AnotherTestProvider'])
            ->will($this->returnValue($genericMapper));

        Injector::set_inst($mockInjector);

        $this->assertSame($genericMapper, $factory->createMapper('AnotherTestProvider'));

        // Restore things
        Injector::set_inst($injector);
    }
}

class MemberMapperFactoryTest_Mapper implements MemberMapperInterface
{
    public function map(Member $member, ResourceOwnerInterface $resourceOwner)
    {
        return $member;
    }
}
