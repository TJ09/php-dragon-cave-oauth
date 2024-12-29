<?php

namespace DragonCave\API\OAuth;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class DragonCaveResourceOwner implements ResourceOwnerInterface {
	use ArrayAccessorTrait;

	/** @var array{user_id: int, username: string, email?: string} */
	protected array $response;

	public function __construct(array $response) {
		$this->response = $response['data'];
	}

	public function getId(): int {
		return (int)$this->getValueByKey($this->response, 'user_id');
	}

	public function getUsername(): string {
		return $this->getValueByKey($this->response, 'username');
	}

	public function getEmail(): ?string {
		return $this->getValueByKey($this->response, 'email');
	}

	public function toArray(): array {
		return $this->response;
	}
}
