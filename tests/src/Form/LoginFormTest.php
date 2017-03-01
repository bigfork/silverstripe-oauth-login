<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Form;

use Bigfork\SilverStripeOAuth\Client\Form\LoginForm;
use Bigfork\SilverStripeOAuth\Client\Test\LoginTestCase;
use Config;
use Controller;
use Director;
use Injector;
use SS_HTTPResponse;

class LoginFormTest extends LoginTestCase
{
    public function testGetActions()
    {
        $providers = [
            'ProviderOne' => [
                'scopes' => ['email']
            ],
            'ProviderTwo' => [
                'name' => 'Custom Name',
                'scopes' => ['email']
            ]
        ];

        Config::inst()->remove('Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator', 'providers');
        Config::inst()->update('Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator', 'providers', $providers);

        $form = new LoginForm(new Controller, 'FormName');
        $actions = $form->getActions();

        $this->assertInstanceOf('FieldList', $actions);
        $this->assertEquals(2, $actions->count());

        $first = $actions->first();
        $this->assertInstanceOf('FormAction', $first);
        $this->assertEquals('authenticate_ProviderOne', $first->actionName());

        $last = $actions->last();
        $this->assertInstanceOf('FormAction', $last);
        $this->assertEquals('authenticate_ProviderTwo', $last->actionName());
        $this->assertContains('Custom Name', $last->Title());
    }

    public function testHandleProvider()
    {
        $providers = [
            'ProviderName' => []
        ];

        Config::inst()->remove('Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator', 'providers');
        Config::inst()->update('Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator', 'providers', $providers);

        $controller = new LoginFormTest_Controller;
        Injector::inst()->registerService($controller, 'Bigfork\SilverStripeOAuth\Client\Control\Controller');

        $expectedUrl = Director::absoluteBaseURL() . 'loginformtest/authenticate/';
        $expectedUrl .= '?provider=ProviderName&scope%5B0%5D=email';

        $expectedResponse = new SS_HTTPResponse;

        $mockController = $this->getMock('Controller', ['redirect']);
        $mockController->expects($this->once())
            ->method('redirect')
            ->with($expectedUrl)
            ->will($this->returnValue($expectedResponse));

        $mockLoginForm = $this->getConstructorlessMock(
            'Bigfork\SilverStripeOAuth\Client\Form\LoginForm',
            ['getController']
        );
        $mockLoginForm->expects($this->once())
            ->method('getController')
            ->will($this->returnValue($mockController));

        $response = $mockLoginForm->handleProvider('ProviderName');
        $this->assertSame($response, $expectedResponse);
    }

    public function testMagicCallers()
    {
        $providers = [
            'ProviderName' => []
        ];

        Config::inst()->remove('Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator', 'providers');
        Config::inst()->update('Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator', 'providers', $providers);

        $expectedResponse = new SS_HTTPResponse;

        $mockLoginForm = $this->getConstructorlessMock(
            'Bigfork\SilverStripeOAuth\Client\Form\LoginForm',
            ['handleProvider']
        );
        $mockLoginForm->expects($this->once())
            ->method('handleProvider')
            ->with('ProviderName')
            ->will($this->returnValue($expectedResponse));

        $response = $mockLoginForm->authenticate_ProviderName();
        $this->assertSame($response, $expectedResponse);
    }
}

class LoginFormTest_Controller extends Controller
{
    public function Link()
    {
        return 'loginformtest/';
    }

    public function AbsoluteLink()
    {
        return 'http://mysite.com/loginformtest/';
    }
}
