<?php

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Technoized\TestUtil\MockFunctions;

class ResourceOwnerErrorTest extends \PHPUnit\Framework\TestCase {
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
						body: '{"errors":[[2, "Invalid API key"]]}',
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

	public function testAuthorizationFlow() {
		$provider = self::getProvider();

		$token = $provider->getAccessToken('authorization_code', ['code' => 'test_authorization_code']);

		self::assertSame('test_access_token', $token->getToken());
		self::assertNull($token->getRefreshToken());
		self::assertNull($token->getExpires());

		self::expectException(IdentityProviderException::class);
		self::expectExceptionMessage('Invalid API key');

		$user = $provider->getResourceOwner($token);
	}
}
