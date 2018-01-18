<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Factory;

use Bigfork\SilverStripeOAuth\Client\Factory\MemberMapperFactory;
use Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper;
use Bigfork\SilverStripeOAuth\Client\Mapper\MemberMapperInterface;
use Bigfork\SilverStripeOAuth\Client\Test\LoginTestCase;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorLoader;
use SilverStripe\Security\Member;

class MemberMapperFactoryTest extends LoginTestCase
{
    public function testCreateMapper()
    {
        Config::modify()->update(
            MemberMapperFactory::class,
            'mappers',
            ['TestProvider' => MemberMapperFactoryTest_Mapper::class]
        );

        $factory = new MemberMapperFactory();
        $this->assertInstanceOf(MemberMapperFactoryTest_Mapper::class, $factory->createMapper('TestProvider'));

        $genericMapper = new GenericMemberMapper('test');

        $mockInjector = $this->getMockBuilder(Injector::class)
            ->setMethods(['createWithArgs'])
            ->getMock();
        $mockInjector->expects($this->once())
            ->method('createWithArgs')
            ->with(GenericMemberMapper::class, ['AnotherTestProvider'])
            ->will($this->returnValue($genericMapper));

        // Inject mock
        InjectorLoader::inst()->pushManifest($mockInjector);

        $this->assertSame($genericMapper, $factory->createMapper('AnotherTestProvider'));

        // Restore things
        InjectorLoader::inst()->popManifest();
    }
}

class MemberMapperFactoryTest_Mapper implements MemberMapperInterface
{
    public function map(Member $member, ResourceOwnerInterface $resourceOwner)
    {
        return $member;
    }
}
