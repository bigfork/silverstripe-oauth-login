<?php

namespace Bigfork\SilverStripeOAuth\Client\Test;

use SilverStripe\Dev\SapphireTest;

class LoginTestCase extends SapphireTest
{
    /**
     * @param string $class
     * @param array $methods
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function getConstructorlessMock($class, $methods = [])
    {
        $mockBuilder = $this->getMockBuilder($class)
            ->disableOriginalConstructor();

        if (!empty($methods)) {
            $mockBuilder = $mockBuilder->setMethods($methods);
        }

        return $mockBuilder->getMock();
    }
}
