<?php

namespace DragonCave\API\OAuth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class DragonCaveOAuthProvider extends AbstractProvider {
	use BearerAuthorizationTrait;

	private string $apiBase = 'https://dragcave.net';

	/**
	 * @param array{
	 *   dcBase?: string,
	 *   clientId?: string,
	 *   clientSecret?: string,
	 *   redirectUri?: string,
	 *   state?: string,
	 * } $options
	 */
	public function __construct(array $options = [], array $collaborators = []) {
		if (isset($options['dcBase'])) {
			$this->apiBase = $options['dcBase'];
			unset($options['dcBase']);
		}

		parent::__construct($options, $collaborators);
	}

	public function getBaseAuthorizationUrl(): string {
		return $this->apiBase.'/oauth_login';
	}

	public function getBaseAccessTokenUrl(array $params): string {
		return $this->apiBase.'/api/oauth2/token';
	}

	public function getResourceOwnerDetailsUrl(AccessToken $token): string {
		return $this->apiBase.'/api/v2/me';
	}

	public function getDefaultScopes(): array {
		return ['identify'];
	}

	protected function getScopeSeparator() {
		return ' ';
	}

	protected function getPkceMethod() {
		return static::PKCE_METHOD_S256;
	}

	protected function checkResponse(ResponseInterface $response, $data): void {
		if (!empty($data['error'])) {
			$error = $data['error'];
			if (isset($data['error_description'])) {
				$error .= ': '.$data['error_description'];
			}

			throw new IdentityProviderException($error, $response->getStatusCode(), $data);
		}

		// Handle DC error format.
		if (isset($data['errors'])) {
			/** @var array<array{0: int, 1: string}> */
			$errors = array_filter(
				$data['errors'],
				/** @param array{0: int, 1: string} $error */
				fn($error) => $error[0] !== 0,
			);
			if ($errors) {
				throw new IdentityProviderException($errors[0][1], $response->getStatusCode(), $data);

			}
		}
	}

	protected function createResourceOwner(array $response, AccessToken $token): DragonCaveResourceOwner {
		return new DragonCaveResourceOwner($response);
	}

	protected function getDefaultHeaders(): array {
		return [
			'Client-ID' => $this->clientId,
		];
	}

	protected function getAuthorizationHeaders($token = null): array {
		if ($token === null) {
			return [];
		}

		return [
			'Authorization' => 'Bearer '.$token,
		];
	}
}
