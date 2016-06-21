<?php

namespace Bigfork\SilverStripeOAuth\Client\Extension;

use Bigfork\SilverStripeOAuth\Client\Exception\TokenlessUserExistsException;
use Bigfork\SilverStripeOAuth\Client\Factory\ProviderFactory;
use Controller;
use Director;
use Injector;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Member;
use OAuthAccessToken;
use OAuthScope;
use Session;
use Security;
use SS_HTTPRequest;

class ControllerExtension extends \Extension
{
    /**
     * @var array
     */
    private static $allowed_actions = [
        'register'
    ];

    /**
     * @param SS_HTTPRequest $request
     * @return mixed
     */
    public function register(SS_HTTPRequest $request)
    {
        if (!$this->owner->validateState($request)) {
            return Security::permissionFailure($this->owner, 'Invalid session state.');
        }

        $providerName = Session::get('oauth2.provider');
        $redirectUri = Controller::join_links(Director::absoluteBaseURL(), $this->owner->Link(), 'register/');
        $provider = Injector::inst()->get('Bigfork\SilverStripeOAuth\Client\Factory\ProviderFactory')
            ->createProvider($providerName, $redirectUri);

        try {
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $request->getVar('code')
            ]);

            $user = $provider->getResourceOwner($token);
            $member = $this->memberFromResourceOwner($user, $providerName);
            $existingToken = $member->AccessTokens()->filter(['Provider' => $providerName])->first();

            if ($existingToken) {
                $existingToken->delete();
            }

            $accessToken = OAuthAccessToken::createFromAccessToken($providerName, $token);
            $accessToken->MemberID = $member->ID;
            $accessToken->write();

            $scopes = Session::get('oauth2.scope');
            foreach ($scopes as $scope) {
                $scope = OAuthScope::findOrMake($scope);
                $accessToken->Scopes()->add($scope);
            }
        } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
            return Security::permissionFailure($this->owner, 'Invalid access token.');
        } catch (TokenlessUserExistsException $e) {
            return Security::permissionFailure($this->owner, $e->getMessage());
        }

        $result = $member->canLogIn();
        if (!$result->valid()) {
            return Security::permissionFailure($this->owner, $result->message());
        }

        $member->logIn();

        $backUrl = Session::get('oauth2.backurl');
        if (!$backUrl || !Director::is_site_url($backUrl)) {
            $backUrl = Director::baseURL();
        }

        return $this->owner->redirect($backUrl);
    }

    /**
     * Find or create a member from the given resource owner ("user")
     *
     * @todo Implement $overwriteExisting. Could use priorities? I.e. Facebook data > Google data
     * @param ResourceOwnerInterface $user
     * @return Member
     * @throws TokenlessUserExistsException
     */
    protected function memberFromResourceOwner(ResourceOwnerInterface $user, $providerName)
    {
        $member = Member::get()->filter([
            'Email' => $user->getEmail()
        ])->first();

        if (!$member) {
            $member = Member::create();
        }

        if ($member->isInDB() && !$member->AccessTokens()->count()) {
            throw new TokenlessUserExistsException(
                'A user with the email address linked to this account already exists.'
            );
        }

        $overwriteExisting = false; // @todo
        if ($overwriteExisting || !$member->isInDB()) {
            $mapper = Injector::inst()->get('Bigfork\SilverStripeOAuth\Client\Factory\MemberMapperFactory')
                ->createMapper($providerName);

            $member = $mapper->map($member, $user);
            $member->write();
        }

        return $member;
    }
}
