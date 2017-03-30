<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Extension;

use Bigfork\SilverStripeOAuth\Client\Extension\ControllerExtension;
use Bigfork\SilverStripeOAuth\Client\Test\LoginTestCase;
use Controller;
use Injector;
use Member;
use ReflectionMethod;
use Session;

class ControllerExtensionTest extends LoginTestCase
{
    protected static $fixture_file = 'ControllerExtensionTest.yml';

    public function testAfterGetAccessToken()
    {
        $mockToken = $this->getMock('OAuthAccessToken', ['write']);
        $mockToken->expects($this->once())
            ->method('write');
        $mockRequest = $this->getConstructorlessMock('SS_HTTPRequest');

        $mockValidationResult = $this->getMock('ValidationResult', ['valid']);
        $mockValidationResult->expects($this->once())
            ->method('valid')
            ->will($this->returnValue(true));

        $mockMember = $this->getMock('Member', ['canLogIn', 'logIn']);
        $mockMember->expects($this->at(0))
            ->method('canLogIn')
            ->will($this->returnValue($mockValidationResult));
        $mockMember->expects($this->at(1))
            ->method('logIn');

        // Give the mock member an ID
        $mockMember->ID = 123;

        $mockExtension = $this->getConstructorlessMock(
            'Bigfork\SilverStripeOAuth\Client\Extension\ControllerExtension',
            ['findOrCreateMember']
        );
        $mockExtension->expects($this->once())
            ->method('findOrCreateMember')
            ->with($mockToken)
            ->will($this->returnValue($mockMember));

        $mockExtension->afterGetAccessToken($mockToken, $mockRequest);
        $this->assertEquals(123, $mockToken->MemberID, 'Token not related to member');
    }

    public function testAfterGetAccessTokenMemberCannotLogIn()
    {
        $mockToken = $this->getMock('OAuthAccessToken');
        $mockRequest = $this->getConstructorlessMock('SS_HTTPRequest');

        $mockValidationResult = $this->getMock('ValidationResult', ['valid']);
        $mockValidationResult->expects($this->once())
            ->method('valid')
            ->will($this->returnValue(false));

        $mockMember = $this->getMock('Member', ['canLogIn', 'logIn']);
        $mockMember->expects($this->once())
            ->method('canLogIn')
            ->will($this->returnValue($mockValidationResult));

        $mockExtension = $this->getConstructorlessMock(
            'Bigfork\SilverStripeOAuth\Client\Extension\ControllerExtension',
            ['findOrCreateMember']
        );
        $mockExtension->expects($this->once())
            ->method('findOrCreateMember')
            ->with($mockToken)
            ->will($this->returnValue($mockMember));

        $response = $mockExtension->afterGetAccessToken($mockToken, $mockRequest);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testFindOrCreateMember()
    {
        $mockAccessToken = $this->getConstructorlessMock('League\OAuth2\Client\Token\AccessToken');

        $mockResourceOwner = $this->getConstructorlessMock(
            'League\OAuth2\Client\Provider\GenericResourceOwner',
            ['getId']
        );
        $mockResourceOwner->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue(123456789));

        $mockProvider = $this->getConstructorlessMock(
            'League\OAuth2\Client\Provider\GenericProvider',
            ['getResourceOwner']
        );
        $mockProvider->expects($this->once())
            ->method('getResourceOwner')
            ->with($mockAccessToken)
            ->will($this->returnValue($mockResourceOwner));

        $mockToken = $this->getMock('OAuthAccessToken', ['convertToAccessToken', 'getTokenProvider']);
        $mockToken->expects($this->at(0))
            ->method('convertToAccessToken')
            ->will($this->returnValue($mockAccessToken));
        $mockToken->expects($this->at(1))
            ->method('getTokenProvider')
            ->will($this->returnValue($mockProvider));
        $mockToken->ID = 123;

        $member = $this->objFromFixture('Member', 'member1');

        $mockExtension = $this->getConstructorlessMock(
            'Bigfork\SilverStripeOAuth\Client\Extension\ControllerExtension',
            ['createMember']
        );
        $mockExtension->expects($this->once())
            ->method('createMember')
            ->with($mockToken)
            ->will($this->returnValue($member));
        $mockExtension->setOwner(new Controller);

        $reflectionMethod = new ReflectionMethod(
            'Bigfork\SilverStripeOAuth\Client\Extension\ControllerExtension',
            'findOrCreateMember'
        );
        $reflectionMethod->setAccessible(true);

        $this->assertEquals($member, $reflectionMethod->invoke($mockExtension, $mockToken));

        $passport = $member->Passports()->first();
        $this->assertNotNull($passport);
        $this->assertEquals(123, $passport->TokenID);
        $this->assertEquals(123456789, $passport->Identifier);
    }

    public function testCreateMember()
    {
        $mockAccessToken = $this->getConstructorlessMock('League\OAuth2\Client\Token\AccessToken');
        $mockResourceOwner = $this->getConstructorlessMock('League\OAuth2\Client\Provider\GenericResourceOwner');

        $mockProvider = $this->getConstructorlessMock(
            'League\OAuth2\Client\Provider\GenericProvider',
            ['getResourceOwner']
        );
        $mockProvider->expects($this->once())
            ->method('getResourceOwner')
            ->with($mockAccessToken)
            ->will($this->returnValue($mockResourceOwner));

        $mockToken = $this->getMock('OAuthAccessToken', ['convertToAccessToken', 'getTokenProvider']);
        $mockToken->expects($this->at(0))
            ->method('convertToAccessToken')
            ->will($this->returnValue($mockAccessToken));
        $mockToken->expects($this->at(1))
            ->method('getTokenProvider')
            ->will($this->returnValue($mockProvider));
        $mockToken->Provider = 'ProviderName';

        $mockMemberMapper = $this->getConstructorlessMock(
            'Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper',
            ['map']
        );
        $mockMemberMapper->expects($this->once())
            ->method('map')
            ->with($this->isInstanceOf('Member'), $mockResourceOwner)
            ->will($this->returnArgument(0));

        $mockExtension = $this->getConstructorlessMock(
            'Bigfork\SilverStripeOAuth\Client\Extension\ControllerExtension',
            ['getMapper']
        );
        $mockExtension->expects($this->once())
            ->method('getMapper')
            ->with('ProviderName')
            ->will($this->returnValue($mockMemberMapper));
        $mockExtension->setOwner(new Controller);

        $reflectionMethod = new ReflectionMethod(
            'Bigfork\SilverStripeOAuth\Client\Extension\ControllerExtension',
            'createMember'
        );
        $reflectionMethod->setAccessible(true);

        $member = $reflectionMethod->invoke($mockExtension, $mockToken);
        $this->assertInstanceOf('Member', $member);
        $this->assertEquals('ProviderName', $member->OAuthSource);
    }

    public function testGetMapper()
    {
        // Store original
        $injector = Injector::inst();

        $mockMemberMapper = $this->getConstructorlessMock(
            'Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper'
        );

        $mockMapperFactory = $this->getMock(
            'Bigfork\SilverStripeOAuth\Client\Factory\MemberMapperFactory',
            ['createMapper']
        );
        $mockMapperFactory->expects($this->once())
            ->method('createMapper')
            ->with('ProviderName')
            ->will($this->returnValue($mockMemberMapper));

        $mockInjector = $this->getMock('Injector', ['get']);
        $mockInjector->expects($this->once())
            ->method('get')
            ->with('MemberMapperFactory')
            ->will($this->returnValue($mockMapperFactory));

        Injector::set_inst($mockInjector);

        $extension = new ControllerExtension;
        $extension->setOwner(new Controller);
        $reflectionMethod = new ReflectionMethod(
            'Bigfork\SilverStripeOAuth\Client\Extension\ControllerExtension',
            'getMapper'
        );
        $reflectionMethod->setAccessible(true);

        $this->assertEquals($mockMemberMapper, $reflectionMethod->invoke($extension, 'ProviderName'));

        // Restore things
        Injector::set_inst($injector);
    }
}
