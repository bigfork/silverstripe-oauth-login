<?php

class OAuthPassport extends DataObject
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
        'Member' => 'Member'
    ];
}
