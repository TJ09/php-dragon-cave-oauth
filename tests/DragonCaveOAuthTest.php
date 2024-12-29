<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Technoized\TestUtil\MockFunctions;

class DragonCaveOAuthTest extends \PHPUnit\Framework\TestCase {
	protected function setUp(): void {
		MockFunctions::mockImplementation(\League\OAuth2\Client\Provider\AbstractProvider::class.'::getResponse', function(RequestInterface $request): ResponseInterface {
			$uri = $request->getUri();
			assert($uri->getHost() === 'dragcave.net');

			switch($uri->getPath()) {
				case '/api/oauth2/token':
					return new \GuzzleHttp\Psr7\Response(
						status: 200,
						headers: [
							'Content-Type' => 'application/json',
						],
						body: '{"access_token":"test_access_token","token_type":"Bearer"}',
					);
				case '/api/v2/me':
				return new \GuzzleHttp\Psr7\Response(
					status: 200,
					headers: [
						'Content-Type' => 'application/json',
					],
					body: '{"errors":[],"data":{"user_id":1234,"username":"test"}}',
				);
				default:
					throw new \Exception('Unknown request to '.$uri);
			}
		});
	}

	protected function tearDown(): void {
		MockFunctions::resetAllMocks();
	}

	private static function getProvider(): \DragonCave\API\OAuth\DragonCaveOAuthProvider {
		return new \DragonCave\API\OAuth\DragonCaveOAuthProvider([
			'clientId' => 'test_client_id',
			'clientSecret' => 'test_secret',
			'redirectUri' => 'http://localhost',
		]);
	}

	public function testAuthorizationUrl(): void {
		$provider = self::getProvider();

		$uri = $provider->getAuthorizationUrl();
		$uri_parts = parse_url($uri);
		parse_str($uri_parts['query'], $query);

		self::assertArrayHasKey('client_id', $query);
		self::assertArrayHasKey('redirect_uri', $query);
		self::assertArrayHasKey('state', $query);
		self::assertArrayHasKey('scope', $query);
		self::assertArrayHasKey('response_type', $query);
		self::assertArrayHasKey('approval_prompt', $query);
		self::assertArrayHasKey('code_challenge', $query);
		self::assertArrayHasKey('code_challenge_method', $query);
		self::assertSame($provider->getState(), $query['state']);
	}

	public function testAuthorizationFlow() {
		$provider = self::getProvider();

		$token = $provider->getAccessToken('authorization_code', ['code' => 'test_authorization_code']);

		self::assertSame('test_access_token', $token->getToken());
		self::assertNull($token->getRefreshToken());
		self::assertNull($token->getExpires());

		$user = $provider->getResourceOwner($token);
		self::assertSame(1234, $user->getID());
		self::assertSame('test', $user->getUsername());
		self::assertNull($user->getEmail());
	}
}
