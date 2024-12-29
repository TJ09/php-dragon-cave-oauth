# OAuth2 Provider for Dragon Cave

Simple package to allow implementing [Dragon Cave](https://dragcave.net) OAuth 2.0 Login using the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```bash
$ composer require tj09/dragon-cave-oauth
```

## Usage

See the [Dragon Cave API documentation](https://dragcave.net/api/docs) for more info and to set up an application.

Example usage:

```php
<?php

require __DIR__.'/vendor/autoload.php';

$provider = \DragonCave\API\OAuth\DragonCaveOAuthProvider([
	'clientId' => 'my.client.id',
	'clientSecret' => 'secret',
	'redirectUri' => 'http://localhost',
	'pkceCode' => $_SESSION['oauth_pkce'] ?? null,
]);

if (!isset($_GET['code'])) {
	$url = $oauth->getAuthorizationUrl([
		'scope' => ['identify', 'dragons'],
	]);

	// TODO: Actually managing session state is left as an exercise to the reader.
	$_SESSION['oauth_state'] = $oauth->getState();
	$_SESSION['oauth_pkce'] = $oauth->getPkceCode();

	header('Location: '.$url);
	die();
}

if (!isset($_SESSION['oauth_state']) || !isset($_SESSION['oauth_pkce']) || $request->getString('state') !== $_SESSION['oauth_state']) {
	unset($_SESSION['oauth_state']);
	unset($_SESSION['oauth_pkce']);

	// TODO: This is not a helpful error message for people using your application.
	echo 'Invalid state';
	die();
}

unset($_SESSION['oauth_state']);
unset($_SESSION['oauth_pkce']);

try {
	$token = $oauth->getAccessToken('authorization_code', [
		'code' => $_GET['code'],
	]);
} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
	// TODO: Consider logging the exception.
	echo 'Failed to verify your login with Dragon Cave.';
	die();
}

$oauth_user = $oauth->getResourceOwner($token);

$_SESSION['dcAccessToken'] = $token->getToken();

// TODO: store some information about $oauth_user.
echo "Welcome, {$oauth_user->getUsername()}!";
```
