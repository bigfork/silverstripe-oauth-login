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

        $mockMember = $this->getMock('stdClass', ['AccessTokens']);
        $mockMember->expects($this->once())
            ->method('AccessTokens')
            ->will($this->returnValue($mockDataList));

        $extension = new MemberExtension;
        $extension->setOwner($mockMember, 'Member');
        $extension->onBeforeDelete();
    }

    public function testClearTokensFromProvider()
    {
        $mockDataList = $this->getMock('stdClass', ['filter', 'count', 'removeAll']);
        $mockDataList->expects($this->at(0))
            ->method('filter')
            ->with(['Provider' => 'ProviderName'])
            ->will($this->returnValue($mockDataList));
        $mockDataList->expects($this->once())
            ->method('removeAll');

        $mockMember = $this->getMock('stdClass', ['AccessTokens']);
        $mockMember->expects($this->once())
            ->method('AccessTokens')
            ->will($this->returnValue($mockDataList));

        $extension = new MemberExtension;
        $extension->setOwner($mockMember, 'Member');
        $extension->clearTokensFromProvider('ProviderName');
    }
}
