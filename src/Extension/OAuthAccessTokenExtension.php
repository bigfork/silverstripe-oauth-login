<?php

namespace Bigfork\SilverStripeOAuth\Client\Extension;

use FieldList;

class OAuthAccessTokenExtension extends \Extension
{
    /**
     * @var array
     */
    private static $belongs_to = [
        'Passport' => 'OAuthPassport'
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'Member' => 'Member'
    ];

    /**
     * {@inheritdoc}
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName('MemberID');
    }
}
