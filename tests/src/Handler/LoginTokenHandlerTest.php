<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Handler;

use Bigfork\SilverStripeOAuth\Client\Handler\LoginTokenHandler;
use Bigfork\SilverStripeOAuth\Client\Test\LoginTestCase;
use Controller;
use Injector;
use Member;
use ReflectionMethod;
use Session;

class LoginTokenHandlerTest extends LoginTestCase
{
    protected static $fixture_file = 'LoginTokenHandlerTest.yml';

    public function testHandleToken()
    {
        $mockAccessToken = $this->getConstructorlessMock('League\OAuth2\Client\Token\AccessToken');
        $mockProvider = $this->getConstructorlessMock('League\OAuth2\Client\Provider\GenericProvider');

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

        $mockHandler = $this->getMock(
            'Bigfork\SilverStripeOAuth\Client\Handler\LoginTokenHandler',
            ['findOrCreateMember']
        );
        $mockHandler->expects($this->once())
            ->method('findOrCreateMember')
            ->with($mockAccessToken, $mockProvider)
            ->will($this->returnValue($mockMember));

        $mockHandler->handleToken($mockAccessToken, $mockProvider);
    }

    public function testAfterGetAccessTokenMemberCannotLogIn()
    {
        $mockAccessToken = $this->getConstructorlessMock('League\OAuth2\Client\Token\AccessToken');
        $mockProvider = $this->getConstructorlessMock('League\OAuth2\Client\Provider\GenericProvider');

        $mockValidationResult = $this->getMock('ValidationResult', ['valid']);
        $mockValidationResult->expects($this->once())
            ->method('valid')
            ->will($this->returnValue(false));

        $mockMember = $this->getMock('Member', ['canLogIn', 'logIn']);
        $mockMember->expects($this->once())
            ->method('canLogIn')
            ->will($this->returnValue($mockValidationResult));

        $mockHandler = $this->getMock(
            'Bigfork\SilverStripeOAuth\Client\Handler\LoginTokenHandler',
            ['findOrCreateMember']
        );
        $mockHandler->expects($this->once())
            ->method('findOrCreateMember')
            ->with($mockAccessToken, $mockProvider)
            ->will($this->returnValue($mockMember));

        $response = $mockHandler->handleToken($mockAccessToken, $mockProvider);
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

        $member = $this->objFromFixture('Member', 'member1');

        $mockHandler = $this->getMock(
            'Bigfork\SilverStripeOAuth\Client\Handler\LoginTokenHandler',
            ['createMember']
        );
        $mockHandler->expects($this->once())
            ->method('createMember')
            ->with($mockAccessToken, $mockProvider)
            ->will($this->returnValue($member));

        $reflectionMethod = new ReflectionMethod(
            'Bigfork\SilverStripeOAuth\Client\Handler\LoginTokenHandler',
            'findOrCreateMember'
        );
        $reflectionMethod->setAccessible(true);

        $this->assertEquals($member, $reflectionMethod->invoke($mockHandler, $mockAccessToken, $mockProvider));

        $passport = $member->Passports()->first();
        $this->assertNotNull($passport);
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

        $mockSession = $this->getConstructorlessMock('Session', ['inst_get']);
        $mockSession->expects($this->once())
            ->method('inst_get')
            ->with('oauth2.provider')
            ->will($this->returnValue('ProviderName'));

        $mockMemberMapper = $this->getConstructorlessMock(
            'Bigfork\SilverStripeOAuth\Client\Mapper\GenericMemberMapper',
            ['map']
        );
        $mockMemberMapper->expects($this->once())
            ->method('map')
            ->with($this->isInstanceOf('Member'), $mockResourceOwner)
            ->will($this->returnArgument(0));

        $mockHandler = $this->getConstructorlessMock(
            'Bigfork\SilverStripeOAuth\Client\Handler\LoginTokenHandler',
            ['getSession', 'getMapper']
        );
        $mockHandler->expects($this->at(0))
            ->method('getSession')
            ->will($this->returnValue($mockSession));
        $mockHandler->expects($this->at(1))
            ->method('getMapper')
            ->with('ProviderName')
            ->will($this->returnValue($mockMemberMapper));

        $reflectionMethod = new ReflectionMethod(
            'Bigfork\SilverStripeOAuth\Client\Handler\LoginTokenHandler',
            'createMember'
        );
        $reflectionMethod->setAccessible(true);

        $member = $reflectionMethod->invoke($mockHandler, $mockAccessToken, $mockProvider);
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

        $handler = new LoginTokenHandler;
        $reflectionMethod = new ReflectionMethod(
            'Bigfork\SilverStripeOAuth\Client\Handler\LoginTokenHandler',
            'getMapper'
        );
        $reflectionMethod->setAccessible(true);

        $this->assertEquals($mockMemberMapper, $reflectionMethod->invoke($handler, 'ProviderName'));

        // Restore things
        Injector::set_inst($injector);
    }
}
