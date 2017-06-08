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
        'Passports' => 'OAuthPassport'
    ];

    /**
     * Remove this member's OAuth passports on delete
     */
    public function onBeforeDelete()
    {
        $this->owner->Passports()->removeAll();
    }
}
