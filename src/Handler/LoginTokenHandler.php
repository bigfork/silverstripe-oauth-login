<?php

namespace Bigfork\SilverStripeOAuth\Client\Handler;

use Controller;
use Injector;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Member;
use OAuthPassport;
use Security;
use Session;
use SS_HTTPResponse;

class LoginTokenHandler implements TokenHandler
{
    /**
     * {@inheritdoc}
     */
    public function handleToken(AccessToken $token, AbstractProvider $provider)
    {
        try {
            // Find or create a member from the token
            $member = $this->findOrCreateMember($token, $provider);
        } catch (ValidationException $e) {
            return Security::permissionFailure(null, $e->getMessage());
        }

        // Check whether the member can log in before we proceed
        $result = $member->canLogIn();
        if (!$result->valid()) {
            return Security::permissionFailure(null, $result->message());
        }

        // Log the member in
        $member->logIn();
    }

    /**
     * Find or create a member from the given access token
     *
     * @param AccessToken $token
     * @param AbstractProvider $provider
     * @return Member
     */
    protected function findOrCreateMember(AccessToken $token, AbstractProvider $provider)
    {
        $user = $provider->getResourceOwner($token);

        $passport = OAuthPassport::get()->filter([
            'Identifier' => $user->getId()
        ])->first();

        if (!$passport) {
            // Create the new member
            $member = $this->createMember($token, $provider);

            // Create a passport for the new member
            $passport = OAuthPassport::create()->update([
                'Identifier' => $user->getId(),
                'MemberID' => $member->ID
            ]);
            $passport->write();
        }

        return $passport->Member();
    }

    /**
     * Create a member from the given token
     *
     * @param AccessToken $token
     * @param AbstractProvider $provider
     * @return Member
     */
    protected function createMember(AccessToken $token, AbstractProvider $provider)
    {
        $session = $this->getSession();
        $providerName = $session->inst_get('oauth2.provider');
        $user = $provider->getResourceOwner($token);

        $member = Member::create();
        $member = $this->getMapper($providerName)->map($member, $user);
        $member->OAuthSource = $providerName;
        $member->write();

        return $member;
    }

    /**
     * @param string $providerName
     * @return Bigfork\SilverStripeOAuth\Client\Mapper\MemberMapperInterface
     */
    protected function getMapper($providerName)
    {
        return Injector::inst()->get('MemberMapperFactory')->createMapper($providerName);
    }

    /**
     * @return Session
     */
    protected function getSession()
    {
        if (Controller::has_curr()) {
            return Controller::curr()->getSession();
        }

        return Injector::inst()->create('Session', isset($_SESSION) ? $_SESSION : []);
    }
}
