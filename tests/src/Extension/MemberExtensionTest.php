<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Extension;

use Bigfork\SilverStripeOAuth\Client\Extension\MemberExtension;
use Bigfork\SilverStripeOAuth\Client\Test\LoginTestCase;

class MemberExtensionTest extends LoginTestCase
{
    public function testOnBeforeDelete()
    {
        $mockDataList = $this->getMock('stdClass', ['removeAll']);
        $mockDataList->expects($this->once())
            ->method('removeAll');

        $mockMember = $this->getMock('stdClass', ['Passports']);
        $mockMember->expects($this->once())
            ->method('Passports')
            ->will($this->returnValue($mockDataList));

        $extension = new MemberExtension;
        $extension->setOwner($mockMember, 'Member');
        $extension->onBeforeDelete();
    }
}
