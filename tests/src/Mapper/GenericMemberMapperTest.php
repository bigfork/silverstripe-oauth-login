<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Mapper;

use Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper;
use Bigfork\SilverStripeOAuth\Client\Test\TestCase;
use Config;
use Member;
use ReflectionMethod;

class GenericMemberMapperTest extends TestCase
{
    public function testSetAndGetProvider()
    {
        $mapper = new GenericMemberMapper('ProviderName');

        $this->assertEquals('ProviderName', $mapper->getProvider());

        $this->assertSame($mapper, $mapper->setProvider('AnotherProvider'));
        $this->assertEquals('AnotherProvider', $mapper->getProvider());
    }

    public function testGetMapping()
    {
        $mapping = [
            'ProviderName' => [
                'FirstName' => 'FirstName',
                'Nickname' => 'Nickname'
            ],
            'default' => [
                'Surname' => 'Surname',
                'Email' => 'Email'
            ]
        ];

        Config::inst()->remove('Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper', 'mapping');
        Config::inst()->update('Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper', 'mapping', $mapping);

        $reflectionMethod = new ReflectionMethod(
            'Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper',
            'getMapping'
        );
        $reflectionMethod->setAccessible(true);

        $mapper = new GenericMemberMapper('ProviderName');
        $this->assertEquals($mapping['ProviderName'], $reflectionMethod->invoke($mapper));

        $mapper = new GenericMemberMapper('UnmappedProvider');
        $this->assertEquals($mapping['default'], $reflectionMethod->invoke($mapper));
    }

    public function testMap()
    {
        $mapping = [
            'FirstName' => 'FirstName',
            'Surname' => 'LastName',
            'Email' => 'Email'
        ];

        $member = new Member;

        $mockResourceOwner = $this->getConstructorlessMock(
            'League\OAuth2\Client\Provider\GenericResourceOwner',
            ['toArray', 'getLastName', 'getEmail']
        );
        $mockResourceOwner->expects($this->at(0))
            ->method('toArray')
            ->will($this->returnValue(['FirstName' => 'Foo']));
        $mockResourceOwner->expects($this->at(1))
            ->method('getLastName')
            ->will($this->returnValue('Bar'));
        $mockResourceOwner->expects($this->at(2))
            ->method('getEmail')
            ->will($this->returnValue('foo.bar@example.com'));

        $mockMapper = $this->getConstructorlessMock(
            'Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper',
            ['getMapping']
        );
        $mockMapper->expects($this->once())
            ->method('getMapping')
            ->will($this->returnValue($mapping));

        $member = $mockMapper->map($member, $mockResourceOwner);
        $this->assertEquals('Foo', $member->FirstName);
        $this->assertEquals('Bar', $member->Surname);
        $this->assertEquals('foo.bar@example.com', $member->Email);
    }
}
