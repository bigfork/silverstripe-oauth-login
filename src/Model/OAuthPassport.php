<?php

namespace Bigfork\SilverStripeOAuth\Client\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;

/**
 * @property string $Identifier
 * @method Member Member()
 */
class Passport extends DataObject
{
    /**
     * @var array
     */
    private static $db = [
        'Identifier' => 'Varchar(255)'
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'Member' => Member::class
    ];

    private static $table_name = 'SS_OAuth_Passport';
}
