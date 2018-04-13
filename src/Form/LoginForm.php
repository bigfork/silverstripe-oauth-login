<?php

namespace Bigfork\SilverStripeOAuth\Client\Form;

use Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator;
use Bigfork\SilverStripeOAuth\Client\Factory\ProviderFactory;
use Bigfork\SilverStripeOAuth\Client\Helper\Helper;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Security\LoginForm as SilverStripeLoginForm;

class LoginForm extends SilverStripeLoginForm
{
    public function __construct(
        $controller,
        $authenticatorClass,
        $name,
        $fields = null,
        $actions = null
    ) {
        $this->setController($controller);
        $this->authenticator_class = $authenticatorClass;
        $this->setFormMethod('POST', true);

        $fields = $fields ?: $this->getFormFields();
        $actions = $actions ?: $this->getFormActions();

        parent::__construct($controller, $name, $fields, $actions);

        $this->setTemplate('OAuthLoginForm');
    }

    public function getFormFields()
    {
        $request = $this->getRequest();
        if ($request->getVar('BackURL')) {
            $backURL = $request->getVar('BackURL');
        } else {
            $backURL = $request->getSession()->get('BackURL');
        }

        $fields = FieldList::create(
            HiddenField::create('AuthenticationMethod', null, $this->authenticator_class, $this)
        );

        if (isset($backURL)) {
            $fields->push(HiddenField::create('BackURL', 'BackURL', $backURL));
        }

        $this->extend('updateFormFields', $fields);

        return $fields;
    }

    public function getFormActions()
    {
        $actions = FieldList::create();
        $providers = Config::inst()->get($this->authenticator_class, 'providers');

        foreach ($providers as $provider => $config) {
            $name = isset($config['name']) ? $config['name'] : $provider;
            $text = _t(
                self::class . '.BUTTON',
                'Sign in with {provider}',
                ['provider' => $name]
            );

            $action = FormAction::create('authenticate_' . $provider, $text)
                ->setTemplate("FormAction_OAuth_{$provider}");
            $actions->push($action);
        }

        $this->extend('updateFormActions', $actions);

        return $actions;
    }

    /**
     * Handle a submission for a given provider - build redirection
     *
     * @param string $name
     * @return HTTPResponse
     */
    public function handleProvider($name)
    {
        $this->extend('onBeforeHandleProvider', $name);

        $providers = Config::inst()->get($this->authenticator_class, 'providers');
        $config = $providers[$name];
        $scope = isset($config['scopes']) ? $config['scopes'] : ['email']; // We need at least an email address!
        $url = Helper::buildAuthorisationUrl($name, 'login', $scope);

        return $this->getController()->redirect($url);
    }

    /**
     * {@inheritdoc}
     */
    public function hasMethod($method)
    {
        if (strpos($method, 'authenticate_') === 0) {
            $providers = Config::inst()->get($this->authenticator_class, 'providers');
            $name = substr($method, strlen('authenticate_'));

            if (isset($providers[$name])) {
                return true;
            }
        }

        return parent::hasMethod($method);
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $args)
    {
        if (strpos($method, 'authenticate_') === 0) {
            $providers = Config::inst()->get($this->authenticator_class, 'providers');
            $name = substr($method, strlen('authenticate_'));

            if (isset($providers[$name])) {
                return $this->handleProvider($name);
            }
        }

        return parent::__call($method, $args);
    }

    /**
     * The name of this login form, to display in the frontend
     * Replaces Authenticator::get_name()
     *
     * @return string
     */
    public function getAuthenticatorName()
    {
        return _t(Authenticator::class . '.TITLE', 'Social sign-on');
    }
}
