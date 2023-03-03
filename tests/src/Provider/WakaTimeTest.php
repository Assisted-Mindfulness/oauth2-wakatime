<?php
namespace AssistedMindfulness\OAuth2\Client\Test\Provider;

use AssistedMindfulness\OAuth2\Client\Provider\WakaTime;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class WakaTimeTest extends TestCase
{
    protected $provider;

    public function tearDown():void
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/oauth/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/oauth/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');

        $response->shouldReceive('getBody')->andReturn('access_token=mock_access_token&refresh_token=mock_refresh_token&uid=12815ec2-a6c1-406f-97b4-76f8924a1ee8&token_type=bearer&expires_in=3600&scope=email%2Cread_stats%2Cread_logged_time%2Cread_orgs');

        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'text/html; charset=utf-8']);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertLessThanOrEqual(time() + 3600, $token->getExpires());
        $this->assertGreaterThanOrEqual(time(), $token->getExpires());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData()
    {
        $username = uniqid();
        $id = rand(1000, 9999);

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');

        $postResponse->shouldReceive('getBody')->andReturn('access_token=mock_access_token&refresh_token=mock_refresh_token&uid=12815ec2-a6c1-406f-97b4-76f8924a1ee8&token_type=bearer&expires_in=3600&scope=email%2Cread_stats%2Cread_logged_time%2Cread_orgs');

        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'text/html; charset=utf-8']);


        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');

        $userResponse->shouldReceive('getBody')->andReturn(
            '{"data":{"bio":null,"city":null,"color_scheme":"Dark","created_at":"2023-02-28T06:59:22Z","date_format":"DD-MM-YYYY","default_dashboard_range":"Last 7 Days","display_name":"Anonymous User","durations_slice_by":"Language","email":"test@test.ru","full_name":null,"has_premium_features":false,"human_readable_website":null,"id":"'.$id.'","invoice_id_format":"INV-{iiiii}","is_email_confirmed":true,"is_email_public":false,"is_hireable":false,"is_onboarding_finished":true,"languages_used_public":false,"last_heartbeat_at":"2023-03-03T09:10:12Z","last_plugin":"wakatime/v1.68.3 (linux-5.4.0-139-generic-x86_64) go1.19.6 PhpStorm/2022.2.4 PhpStorm-wakatime/14.1.4","last_plugin_name":"PhpStorm","last_project":"Emotion-Tracker","location":null,"logged_time_public":false,"meetings_over_coding":false,"modified_at":null,"needs_payment_method":false,"photo":"https://wakatime.com/photo/12815ec2-a6c1-406f-97b4-76f8924a1ee8","photo_public":true,"plan":"basic","profile_url":"https://wakatime.com/@12815ec2-a6c1-406f-97b4-76f8924a1ee8","profile_url_escaped":"https://wakatime.com/@12815ec2-a6c1-406f-97b4-76f8924a1ee8","public_email":null,"public_profile_time_range":"last_7_days","share_all_time_badge":null,"share_last_year_days":null,"show_machine_name_ip":false,"time_format_24hr":true,"time_format_display":"digital","timeout":15,"timezone":"Europe/Moscow","username":"'.$username.'","website":null,"weekday_start":1,"writes_only":false}}'

        );
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/json']);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(2)->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $this->provider->getResourceOwner($token);

        $this->assertEquals($username, $user->getUsername());
        $this->assertEquals($username, $user->toArray()['username']);
        $this->assertEquals($id, $user->getId());
        $this->assertEquals($id, $user->toArray()['id']);
    }


    protected function setUp(): void
    {
        $this->provider = new WakaTime(
            [
                'clientId' => 'mock_client_id',
                'clientSecret' => 'mock_secret',
                'redirectUri' => 'none',
            ]
        );
    }



}
