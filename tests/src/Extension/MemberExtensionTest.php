<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Extension;

use Bigfork\SilverStripeOAuth\Client\Extension\MemberExtension;
use Bigfork\SilverStripeOAuth\Client\Model\Passport;
use Bigfork\SilverStripeOAuth\Client\Test\LoginTestCase;
use SilverStripe\Security\Member;

class MemberExtensionTest extends LoginTestCase
{
    protected $usesDatabase = true;

    public function testOnBeforeDelete()
    {
        /** @var Member|MemberExtension $member */
        $member = new Member();
        $member->Email = 'memberextension@test.com';
        $member->write();
        $passport = new Passport();
        $passport->write();
        $member->Passports()->add($passport);
        $id = $passport->ID;
        $member->delete();
        $this->assertNull(Passport::get()->byID($id));

    }
}
