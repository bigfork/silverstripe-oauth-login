<?php

namespace Bigfork\SilverStripeOAuth\Client\Mapper;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Member;

class GenericMemberMapper implements MemberMapperInterface
{
    /**
     * @var array
     */
    private static $mapping = [
        'default' => [
            'Email' => 'Email',
            'FirstName' => 'FirstName',
            'Surname' => 'LastName'
        ]
    ];

    /**
     * @var string
     */
    protected $provider;

    /**
     * @param string $provider
     */
    public function __construct($provider)
    {
        $this->setProvider($provider);
    }

    /**
     * @param string $provider
     * @return self
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * {@inheritdoc}
     */
    public function map(Member $member, ResourceOwnerInterface $resourceOwner)
    {
        $mapping = $this->getMapping();
        $array = $resourceOwner->toArray();

        foreach ($mapping as $target => $source) {
            if (method_exists($resourceOwner, "get{$source}")) {
                $method = "get{$source}";
                $member->$target = $resourceOwner->$method();
            } elseif (array_key_exists($source, $array)) {
                $member->$target = $array[$source];
            }
        }

        return $member;
    }

    /**
     * @return array
     */
    protected function getMapping()
    {
        $mapping = Config::inst()->get(self::class, 'mapping');
        $provider = $this->getProvider();
        return (isset($mapping[$provider])) ? $mapping[$provider] : $mapping['default'];
    }
}
