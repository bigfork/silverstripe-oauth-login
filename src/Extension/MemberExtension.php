<?php

namespace Bigfork\SilverStripeOAuth\Client\Extension;

use Bigfork\SilverStripeOAuth\Client\Model\Passport;
use SilverStripe\Core\Extension as SilverStripeExtension;
use SilverStripe\ORM\HasManyList;

/**
 * @property string $OAuthSource
 * @property HasManyList|Passport[] Passports()
 */
class MemberExtension extends SilverStripeExtension
{
    /**
     * @config
     * @var array
     */
    private static $db = [
        'OAuthSource' => 'Varchar(255)'
    ];

    /**
     * @config
     * @var array
     */
    private static $has_many = [
        'Passports' => Passport::class
    ];

    /**
     * Cleanup passports on user deletion
     *
     * @config
     * @var array
     */
    private static $cascade_deletes = [
        'Passports',
    ];
}
