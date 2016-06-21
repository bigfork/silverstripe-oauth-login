<?php

namespace Bigfork\SilverStripeOAuth\Client\Mapper;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Member;

interface MemberMapperInterface
{
    /**
     * @param Member $member
     * @param ResourceOwnerInterface $resourceOwner
     * @return Member
     */
    public function map(Member $member, ResourceOwnerInterface $resourceOwner);
}
