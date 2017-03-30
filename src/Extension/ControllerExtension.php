<?php

namespace Bigfork\SilverStripeOAuth\Client\Extension;

use Injector;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Member;
use OAuthAccessToken;
use OAuthPassport;
use Security;
use SS_HTTPRequest;
use ValidationException;

class ControllerExtension extends \Extension
{
    /**
     * @param OAuthAccessToken $token
     * @param SS_HTTPRequest $request
     * @return mixed
     */
    public function afterGetAccessToken(OAuthAccessToken $token, SS_HTTPRequest $request)
    {
        try {
            // Find or create a member from the token
            $member = $this->findOrCreateMember($token);
        } catch (ValidationException $e) {
            return Security::permissionFailure($this->owner, $e->getMessage());
        }

        // Check whether the member can log in before we proceed
        $result = $member->canLogIn();
        if (!$result->valid()) {
            return Security::permissionFailure($this->owner, $result->message());
        }

        // Store the access token against the member
        $token->MemberID = $member->ID;
        $token->write();

        // Clear old access tokens for this provider
        // @todo make this behaviour optional, or just remove it in favour of pruning expired tokens?
        $staleTokens = $member->AccessTokens()->filter([
            'Provider' => $token->Provider,
            'ID:not' => $token->ID
        ]);

        foreach ($staleTokens as $token) {
            $token->delete();
        }

        // Log the member in
        $member->logIn();
    }

    /**
     * Find or create a member from the given token
     *
     * @param OAuthAccessToken $token
     * @return Member
     */
    protected function findOrCreateMember(OAuthAccessToken $token)
    {
        $accessToken = $token->convertToAccessToken();
        $user = $token->getTokenProvider()->getResourceOwner($accessToken);

        $passport = OAuthPassport::get()->filter([
            'Identifier' => $user->getId(),
            'Token.Provider' => $token->Provider
        ])->first();

        if (!$passport) {
            // Create the new member
            $member = $this->createMember($token);

            // Create a passport for the new member
            $passport = OAuthPassport::create()->update([
                'Identifier' => $user->getId(),
                'MemberID' => $member->ID
            ]);
        }

        // The new token is now the "active" token for this passport
        $passport->TokenID = $token->ID;
        $passport->write();

        return $passport->Member();
    }

    /**
     * Create a member from the given token
     *
     * @param OAuthAccessToken $token
     * @return Member
     */
    protected function createMember(OAuthAccessToken $token)
    {
        $accessToken = $token->convertToAccessToken();
        $user = $token->getTokenProvider()->getResourceOwner($accessToken);

        $member = Member::create();
        $member = $this->getMapper($token->Provider)->map($member, $user);
        $member->OAuthSource = $token->Provider;
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
}
