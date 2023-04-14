<?php

declare(strict_types=1);

namespace Causal\IgLdapSsoAuth\Event;

/**
 * Event for user disabled
 */
class UserDisabledEvent implements LdapEvent
{
	/**
	 * UserDisabledEvent constructor.
	 *
	 * @param string $table
	 * @param int $uid
	 */
	public function __construct(protected string $table, protected int $uid)
	{
	}

	/**
	 * @return string
	 */
	public function getTable(): string
	{
		return $this->table;
	}

	/**
	 * @return int
	 */
	public function getUid(): int
	{
		return $this->uid;
	}
}
