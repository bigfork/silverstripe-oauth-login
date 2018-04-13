<?php

namespace Bigfork\SilverStripeOAuth\Client\Test\Extension;

use ArrayIterator;
use Bigfork\SilverStripeOAuth\Client\Extension\MemberExtension;
use Bigfork\SilverStripeOAuth\Client\Model\Passport;
use Bigfork\SilverStripeOAuth\Client\Test\LoginTestCase;
use SilverStripe\ORM\DataList;

class MemberExtensionTest extends LoginTestCase
{
    public function testOnBeforeDelete()
    {
        $mockPassport = $this->getMock(Passport::class, ['delete']);
        $mockPassport->expects($this->once())
            ->method('delete');

        $mockDataList = $this->getConstructorlessMock(DataList::class, ['getIterator']);
        $mockDataList->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new ArrayIterator([$mockPassport])));

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
