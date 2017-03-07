<?php

namespace Bigfork\SilverStripeOAuth\Client\Extension;

use Bigfork\SilverStripeOAuth\Client\Exception\TokenlessUserExistsException;
use Injector;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Member;
use OAuthAccessToken;
use Security;
use SS_HTTPRequest;

class ControllerExtension extends \Extension
{
    /**
     * @param AbstractProvider $provider
     * @param OAuthAccessToken $token
     * @param string $providerName
     * @param SS_HTTPRequest $request
     */
    public function afterGetAccessToken(
        AbstractProvider $provider,
        OAuthAccessToken $token,
        $providerName,
        SS_HTTPRequest $request
    ) {
        $accessToken = $token->convertToAccessToken();
        $user = $provider->getResourceOwner($accessToken);

        try {
            // Find or create a member from the resource owner
            $member = $this->memberFromResourceOwner($user, $providerName);
        } catch (TokenlessUserExistsException $e) {
            return Security::permissionFailure($this->owner, $e->getMessage());
        }

        // Check whether the member can log in before we proceed
        $result = $member->canLogIn();
        if (!$result->valid()) {
            return Security::permissionFailure($this->owner, $result->message());
        }

        // Clear old access tokens for this provider
        // @todo make this behaviour optional, or just remove it in favour of pruning expired tokens?
        $staleTokens = $member->AccessTokens()->filter([
            'Provider' => $providerName,
            'ID:not' => $token->ID
        ]);
        $staleTokens->removeAll();

        // Log the member in
        $member->logIn();
    }

    /**
     * Find or create a member from the given resource owner ("user")
     *
     * @todo Implement $overwriteExisting. Could use priorities? I.e. Facebook data > Google data
     * @param ResourceOwnerInterface $user
     * @param string $providerName
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
            $member = $this->getMapper($providerName)->map($member, $user);
            $member->write();
        }

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
}
