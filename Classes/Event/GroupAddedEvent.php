<?php

declare(strict_types=1);

namespace Causal\IgLdapSsoAuth\Event;

/**
 * Event for group added
 */
class GroupAddedEvent implements LdapEvent
{
	/**
	 * GroupAddedEvent constructor.
	 *
	 * @param string $table
	 * @param array $data
	 */
	public function __construct(protected string $table, protected array $data)
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
	 * @return array
	 */
	public function getData(): array
	{
		return $this->data;
	}
}
