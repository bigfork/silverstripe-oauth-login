<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Form;

use Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator;
use Bigfork\SilverStripeOAuth\Client\Control\Controller as OAuthController;
use Bigfork\SilverStripeOAuth\Client\Form\LoginForm;
use Bigfork\SilverStripeOAuth\Client\Test\LoginTestCase;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;

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

        Config::modify()->set(Authenticator::class, 'providers', $providers);

        $form = new LoginForm(new Controller, Authenticator::class, 'FormName');
        $actions = $form->getFormActions();

        $this->assertInstanceOf(FieldList::class, $actions);
        $this->assertEquals(2, $actions->count());

        $first = $actions->first();
        $this->assertInstanceOf(FormAction::class, $first);
        $this->assertEquals('authenticate_ProviderOne', $first->actionName());

        $last = $actions->last();
        $this->assertInstanceOf(FormAction::class, $last);
        $this->assertEquals('authenticate_ProviderTwo', $last->actionName());
        $this->assertContains('Custom Name', $last->Title());
    }

    public function testHandleProvider()
    {
        $providers = [
            'ProviderName' => []
        ];

        Config::modify()->set(Authenticator::class, 'providers', $providers);

        $controller = new LoginFormTest_Controller;
        Injector::inst()->registerService($controller, OAuthController::class);

        $expectedUrl = Director::absoluteBaseURL() . 'loginformtest/authenticate/';
        $expectedUrl .= '?provider=ProviderName&context=login&scope%5B0%5D=email';

        $expectedResponse = new HTTPResponse;

        $mockController = $this->getMockBuilder(Controller::class)
            ->setMethods(['redirect'])
            ->getMock();
        $mockController->expects($this->once())
            ->method('redirect')
            ->with($expectedUrl)
            ->will($this->returnValue($expectedResponse));

        $mockLoginForm = $this->getConstructorlessMock(LoginForm::class, ['getController']);
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

        Config::modify()->set(Authenticator::class, 'providers', $providers);

        $expectedResponse = new HTTPResponse;

        $mockLoginForm = $this->getMockBuilder(LoginForm::class)
            ->setConstructorArgs([new Controller, Authenticator::class, 'FormName'])
            ->setMethods(['handleProvider'])
            ->getMock();
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
    public function Link($action = null)
    {
        return 'loginformtest/';
    }

    public function AbsoluteLink()
    {
        return 'http://mysite.com/loginformtest/';
    }
}
