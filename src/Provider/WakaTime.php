<?php

namespace AssistedMindfulness\OAuth2\Client\Provider;

use GuzzleHttp\Exception\BadResponseException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

class WakaTime extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * @var string
     */
    private $responseResourceOwnerId = 'uid';

    /**
     * @var string
     */
    protected $baseUrlApi = 'https://wakatime.com/api/v1';

    /**
     * @var string
     */
    protected $baseUrl = 'https://wakatime.com';

    public function getBaseAuthorizationUrl()
    {
        return $this->baseUrl.'/oauth/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->baseUrl.'/oauth/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->baseUrlApi.'/users/current';
    }

    protected function getDefaultScopes()
    {
        return [];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['error'])) {
            throw new IdentityProviderException(
                ($data['error']['message'] ?? $response->getReasonPhrase()),
                $response->getStatusCode(),
                $data
            );
        }
    }

    // need scope 'email'
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new WakaTimeUser($response);
    }

    public function getAccessToken($grant, array $options = [])
    {
        $grant = $this->verifyGrant($grant);

        $params = [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
        ];

        $params = $grant->prepareRequestParameters($params, $options);
        $request = $this->getAccessTokenRequest($params);
        $response = $this->getParsedResponseToken($request);
        if (false === is_array($response)) {
            throw new UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }
        $prepared = $this->prepareAccessTokenResponse($response);
        $token = $this->createAccessToken($prepared, $grant);

        return $token;
    }

    public function getParsedResponseToken(RequestInterface $request)
    {
        try {
            $response = $this->getResponse($request);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
        }

        $parsed = $this->parseResponseToken($response);

        $this->checkResponse($response, $parsed);

        return $parsed;
    }

    protected function parseResponseToken(ResponseInterface $response)
    {
        $content = (string) $response->getBody();
        $tokenData = [];
        parse_str($content, $tokenData);

        return $tokenData;
    }
}
