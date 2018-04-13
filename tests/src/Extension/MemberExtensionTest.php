<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Extension;

use ArrayIterator;
use Bigfork\SilverStripeOAuth\Client\Extension\MemberExtension;
use Bigfork\SilverStripeOAuth\Client\Test\LoginTestCase;

class MemberExtensionTest extends LoginTestCase
{
    public function testOnBeforeDelete()
    {
        $mockPassport = $this->getMock('OAuthPassport', ['delete']);
        $mockPassport->expects($this->once())
            ->method('delete');

        $mockDataList = $this->getConstructorlessMock('DataList', ['getIterator']);
        $mockDataList->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new ArrayIterator([$mockPassport])));

        $mockMember = $this->getMock('stdClass', ['Passports']);
        $mockMember->expects($this->once())
            ->method('Passports')
            ->will($this->returnValue($mockDataList));

        $extension = new MemberExtension;
        $extension->setOwner($mockMember, 'Member');
        $extension->onBeforeDelete();
    }
}
