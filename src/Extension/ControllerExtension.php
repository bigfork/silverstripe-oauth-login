<?php

namespace Bigfork\SilverStripeOAuth\Client\Extension;

use Bigfork\SilverStripeOAuth\Client\Exception\TokenlessUserExistsException;
use Bigfork\SilverStripeOAuth\Client\Factory\ProviderFactory;
use Controller;
use Director;
use Injector;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Member;
use OAuthAccessToken;
use OAuthScope;
use Session;
use Security;
use SS_HTTPRequest;

class ControllerExtension extends \Extension
{
    /**
     * @param AbstractProvider $provider
     * @param AccessToken $token
     * @param string $providerName
     * @param SS_HTTPRequest $request
     */
    public function afterGetAccessToken(
        AbstractProvider $provider,
        AccessToken $token,
        $providerName,
        SS_HTTPRequest $request
    ) {
        $user = $provider->getResourceOwner($token);

        try {
            $member = $this->memberFromResourceOwner($user, $providerName);
            $this->owner->setMember($member);
        } catch (TokenlessUserExistsException $e) {
            return Security::permissionFailure($this->owner, $e->getMessage());
        }

        $result = $member->canLogIn();
        if (!$result->valid()) {
            return Security::permissionFailure($this->owner, $result->message());
        }

        $member->logIn();
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
            $mapper = Injector::inst()->get('MemberMapperFactory')->createMapper($providerName);

            $member = $mapper->map($member, $user);
            $member->write();
        }

        return $member;
    }
}
