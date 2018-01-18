<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Extension;

use Bigfork\SilverStripeOAuth\Client\Extension\MemberExtension;
use Bigfork\SilverStripeOAuth\Client\Test\LoginTestCase;

class MemberExtensionTest extends LoginTestCase
{
    public function testOnBeforeDelete()
    {
        $mockDataList = $this->getMockBuilder('stdClass')
            ->setMethods(['removeAll'])
            ->getMock();
        $mockDataList->expects($this->once())
            ->method('removeAll');

        $mockMember = $this->getMockBuilder('stdClass')
            ->setMethods(['Passports'])
            ->getMock();
        $mockMember->expects($this->once())
            ->method('Passports')
            ->will($this->returnValue($mockDataList));

        $extension = new MemberExtension;
        $extension->setOwner($mockMember);
        $extension->onBeforeDelete();
    }
}
