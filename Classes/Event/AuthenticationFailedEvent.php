<?php

declare(strict_types=1);

namespace Causal\IgLdapSsoAuth\Event;

/**
 * Event for authentication failed
 */
class AuthenticationFailedEvent implements LdapEvent
{
	/**
	 * AuthenticationFailedEvent constructor.
	 *
	 * @param array $info
	 */
	public function __construct(protected array $info)
	{
	}

	/**
	 * @return array
	 */
	public function getInfo(): array
	{
		return $this->info;
	}
}
