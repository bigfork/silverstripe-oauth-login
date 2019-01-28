<?php

namespace Bigfork\SilverStripeOAuth\Client\Form;

use Bigfork\SilverStripeOAuth\Client\Helper\Helper;
use Config;
use Controller;
use Director;
use FieldList;
use FormAction;
use HiddenField;
use HTTP;
use LoginForm as SilverStripeLoginForm;
use Security;
use Session;
use SS_HTTPResponse;

class LoginForm extends SilverStripeLoginForm
{
    /**
     * @var string
     */
    protected $authenticator_class = 'Bigfork\SilverStripeOAuth\Client\Authenticator\Authenticator';

    /**
     * {@inheritdoc}
     */
    public function __construct($controller, $name)
    {
        parent::__construct($controller, $name, new FieldList(), new FieldList());
        
        $fields = $this->getFields();
        $actions = $this->getActions();
        $fields->setForm($this);
		$actions->setForm($this);
        
        $this->setFields($fields);
        $this->setActions($actions);
        
        $this->setHTMLID('OAuthAuthenticator');
        $this->setTemplate('OAuthLoginForm');
    }

    /**
     * @return FieldList
     */
    public function getFields()
    {
        $backURL = Session::get('BackURL');
        if (isset($_REQUEST['BackURL'])) {
            $backURL = $_REQUEST['BackURL'];
        }

        $fields = FieldList::create(
            HiddenField::create('AuthenticationMethod', null, $this->authenticator_class, $this)
        );

        if ($backURL) {
            $fields->push(HiddenField::create('BackURL', 'BackURL', $backURL));
        }

        $this->extend('updateFields', $fields);

        return $fields;
    }

    /**
     * @return FieldList
     */
    public function getActions()
    {
        $actions = FieldList::create();
        $providers = Config::inst()->get($this->authenticator_class, 'providers');

        foreach ($providers as $provider => $config) {
            $name = isset($config['name']) ? $config['name'] : $provider;
            $text = _t(
                'Bigfork\SilverStripeOAuth\Client\Form\LoginForm.BUTTON',
                'Sign in with {provider}',
                ['provider' => $name]
            );

            $action = FormAction::create('authenticate_' . $provider, $text)
                ->setTemplate("FormAction_OAuth_{$provider}");
            $actions->push($action);
        }

        $this->extend('updateActions', $actions);

        return $actions;
    }

    /**
     * Handle a submission for a given provider - build redirection
     *
     * @param string $name
     * @return SS_HTTPResponse
     */
    public function handleProvider($name)
    {
        $this->extend('onBeforeHandleProvider', $name);

        $providers = Config::inst()->get($this->authenticator_class, 'providers');
        $config = $providers[$name];
        $scope = isset($config['scopes']) ? $config['scopes'] : ['email']; // We need at least an email address!
        $authUrl = Helper::buildAuthorisationUrl($name, 'login', $scope);

        // Preserve BackURL parameter if present
        if (isset($_REQUEST['BackURL']) && Director::is_site_url($_REQUEST['BackURL'])) {
            $backURL = Director::absoluteURL($_REQUEST['BackURL']);
        } else if ($url = Security::config()->default_login_dest) {
            // Inherit Security.default_login_dest behaviour from MemberLoginForm
            $backURL = Controller::join_links(Director::absoluteBaseURL(), $url);
        }

        // Add the BackURL parameter to the authentication endpoint URL
        if (isset($backURL)) {
            $authUrl = HTTP::setGetVar('BackURL', Director::absoluteURL($backURL), $authUrl, '&');
        }

        return $this->getController()->redirect($authUrl);
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
}
