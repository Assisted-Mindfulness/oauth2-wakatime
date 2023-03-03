<?php

namespace AssistedMindfulness\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class WakaTimeUser implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * Raw response
     *
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param array $response
     */
    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    /**
     * Get username
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->getValueByKey($this->response, 'data.username');
    }

    public function getId()
    {
        return $this->getValueByKey($this->response, 'data.id');
    }

    public function toArray()
    {
        return $this->getValueByKey($this->response, 'data');
    }
}
