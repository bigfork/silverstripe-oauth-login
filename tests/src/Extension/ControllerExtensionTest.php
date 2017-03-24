<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Extension;

use Bigfork\SilverStripeOAuth\Client\Exception\TokenlessUserExistsException;
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
        $mockAccessToken = $this->getConstructorlessMock('League\OAuth2\Client\Token\AccessToken');

        $mockToken = $this->getMock('OAuthAccessToken', ['convertToAccessToken', 'write']);
        $mockToken->expects($this->at(0))
            ->method('convertToAccessToken')
            ->will($this->returnValue($mockAccessToken));
        $mockToken->expects($this->at(1))
            ->method('write');

        $mockResourceOwner = $this->getConstructorlessMock('League\OAuth2\Client\Provider\GenericResourceOwner');

        $mockProvider = $this->getConstructorlessMock(
            'League\OAuth2\Client\Provider\GenericProvider',
            ['getResourceOwner']
        );
        $mockProvider->expects($this->once())
            ->method('getResourceOwner')
            ->with($mockAccessToken)
            ->will($this->returnValue($mockResourceOwner));

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
            ['memberFromResourceOwner']
        );
        $mockExtension->expects($this->once())
            ->method('memberFromResourceOwner')
            ->with($mockResourceOwner, 'ProviderName')
            ->will($this->returnValue($mockMember));

        $mockExtension->afterGetAccessToken($mockProvider, $mockToken, 'ProviderName', $mockRequest);
        $this->assertEquals(123, $mockToken->MemberID, 'Token not related to member');
    }

    public function testAfterGetAccessTokenMemberCannotLogIn()
    {
        $mockAccessToken = $this->getConstructorlessMock('League\OAuth2\Client\Token\AccessToken');

        $mockToken = $this->getMock('OAuthAccessToken', ['convertToAccessToken']);
        $mockToken->expects($this->once())
            ->method('convertToAccessToken')
            ->will($this->returnValue($mockAccessToken));

        $mockResourceOwner = $this->getConstructorlessMock('League\OAuth2\Client\Provider\GenericResourceOwner');

        $mockProvider = $this->getConstructorlessMock(
            'League\OAuth2\Client\Provider\GenericProvider',
            ['getResourceOwner']
        );
        $mockProvider->expects($this->once())
            ->method('getResourceOwner')
            ->with($mockAccessToken)
            ->will($this->returnValue($mockResourceOwner));

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
            ['memberFromResourceOwner']
        );
        $mockExtension->expects($this->once())
            ->method('memberFromResourceOwner')
            ->with($mockResourceOwner, 'ProviderName')
            ->will($this->returnValue($mockMember));

        $response = $mockExtension->afterGetAccessToken($mockProvider, $mockToken, 'ProviderName', $mockRequest);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAfterGetAccessTokenUserExistsWithoutToken()
    {
        $mockAccessToken = $this->getConstructorlessMock('League\OAuth2\Client\Token\AccessToken');

        $mockToken = $this->getMock('OAuthAccessToken', ['convertToAccessToken']);
        $mockToken->expects($this->once())
            ->method('convertToAccessToken')
            ->will($this->returnValue($mockAccessToken));

        $mockResourceOwner = $this->getConstructorlessMock('League\OAuth2\Client\Provider\GenericResourceOwner');

        $mockProvider = $this->getConstructorlessMock(
            'League\OAuth2\Client\Provider\GenericProvider',
            ['getResourceOwner']
        );
        $mockProvider->expects($this->once())
            ->method('getResourceOwner')
            ->with($mockAccessToken)
            ->will($this->returnValue($mockResourceOwner));

        $mockRequest = $this->getConstructorlessMock('SS_HTTPRequest');

        $mockExtension = $this->getConstructorlessMock(
            'Bigfork\SilverStripeOAuth\Client\Extension\ControllerExtension',
            ['memberFromResourceOwner']
        );
        $mockExtension->expects($this->once())
            ->method('memberFromResourceOwner')
            ->with($mockResourceOwner, 'ProviderName')
            ->will($this->throwException(new TokenlessUserExistsException('Test error message')));

        $response = $mockExtension->afterGetAccessToken($mockProvider, $mockToken, 'ProviderName', $mockRequest);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testMemberFromResourceOwner()
    {
        $mockResourceOwner = $this->getConstructorlessMock(
            'League\OAuth2\Client\Provider\GenericResourceOwner',
            ['getEmail']
        );
        $mockResourceOwner->expects($this->once())
            ->method('getEmail')
            ->will($this->returnValue('foo@bar.com'));

        $mockMember = $this->getMock('Member', ['write']);
        $mockMember->expects($this->once())
            ->method('write');

        $mockMemberMapper = $this->getConstructorlessMock(
            'Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper',
            ['map']
        );
        $mockMemberMapper->expects($this->once())
            ->method('map')
            ->with($this->isInstanceOf('Member'), $mockResourceOwner)
            ->will($this->returnValue($mockMember));

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
            'memberFromResourceOwner'
        );
        $reflectionMethod->setAccessible(true);

        $this->assertEquals($mockMember, $reflectionMethod->invoke($mockExtension, $mockResourceOwner, 'ProviderName'));
    }

    /**
     * @expectedException Bigfork\SilverStripeOAuth\Client\Exception\TokenlessUserExistsException
     */
    public function testMemberFromResourceOwnerMemberExists()
    {
        $member = $this->objFromFixture('Member', 'member1');
        $email = $member->Email;

        $mockResourceOwner = $this->getConstructorlessMock(
            'League\OAuth2\Client\Provider\GenericResourceOwner',
            ['getEmail']
        );
        $mockResourceOwner->expects($this->once())
            ->method('getEmail')
            ->will($this->returnValue($email));

        $extension = new ControllerExtension;
        $extension->setOwner(new Controller);
        $reflectionMethod = new ReflectionMethod(
            'Bigfork\SilverStripeOAuth\Client\Extension\ControllerExtension',
            'memberFromResourceOwner'
        );
        $reflectionMethod->setAccessible(true);

        $reflectionMethod->invoke($extension, $mockResourceOwner, 'ProviderName');
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
