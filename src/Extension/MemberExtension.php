<?php

namespace Bigfork\SilverStripeOAuth\Client\Extension;

use FieldList;

class MemberExtension extends \Extension
{
    /**
     * @var array
     */
    private static $db = [
        'OAuthSource' => 'Varchar(255)'
    ];

    /**
     * @var array
     */
    private static $has_many = [
        'AccessTokens' => 'OAuthAccessToken',
        'Passports' => 'OAuthPassport'
    ];

    /**
     * {@inheritdoc}
     */
    public function updateCMSFields(FieldList $fields)
    {
        $tokensField = $fields->dataFieldByName('AccessTokens');
        if ($tokensField) {
            $tokensField->getConfig()->removeComponentsByType('GridFieldDeleteAction');
        }
    }

    /**
     * Remove this member's access tokens on delete
     */
    public function onBeforeDelete()
    {
        $this->owner->AccessTokens()->removeAll();
    }

    /**
     * Remove all access tokens from the given provider
     *
     * @param string $provider
     */
    public function clearTokensFromProvider($provider)
    {
        $existingTokens = $this->owner->AccessTokens()->filter(['Provider' => $provider]);
        $existingTokens->removeAll();
    }
}
