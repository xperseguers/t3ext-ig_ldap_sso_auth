<?php
namespace Causal\IgLdapSsoAuth\Tests\Unit\Library;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Test cases for class \Causal\IgLdapSsoAuth\Library\Authentication.
 */
class AuthenticationTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/** @var array */
	protected $ldapFixture;

	/** @var array */
	protected $typo3Fixture;

	protected function setUp() {
		$this->ldapFixture = array(
			'dn' => 'uid=newton,dc=example,dc=com',
			0 => 'sn',
			'sn' => array(
				0 => 'Newton',
				'count' => 1,
			),
			1 => 'cn',
			'cn' => array(
				0 => 'Isaac Newton',
				'count' => 1,
			),
			2 => 'objectclass',
			'objectclass' => array(
				0 => 'inetOrgPerson',
				1 => 'organizationalPerson',
				2 => 'person',
				3 => 'top',
			),
			3 => 'uid',
			'uid' => array(
				0 => 'newton',
				'count' => 1,
			),
			4 => 'mail',
			'mail' => array(
				0 => 'newton@ldap.forumsys.com',
				'count' => 1,
			),
			5 => 'l',
			'l' => array(
				0 => 'Woolsthorpe-by-Colsterworth',
				'count' => 1,
			),
			6 => 'postalcode',
			'postalcode' => array(
				0 => 'NG33',
				'count' => 1,
			),
			7 => 'street',
			'street' => array(
				0 => 'Woolsthorpe Manor',
				'count' => 1,
			),
			8 => 'c',
			'c' => array(
				0 => 'EN',
				'count' => 1,
			),
			9 => 'co',
			'co' => array(
				0 => 'England',
				'count' => 1,
			),
		);

		$this->typo3Fixture = array(
			'pid' => 0,
			'tstamp' => 0,
			'username' => '',
			'password' => '',
			'usergroup' => '',
			'name' => '',
			'first_name' => '',
			'last_name' => '',
			'address' => '',
			'telephone' => '',
			'email' => '',
			'title' => '',
			'zip' => '',
			'city' => '',
			'country' => '',
			'www' => '',
			'company' => '',
			'tx_igldapssoauth_dn' => '',
		);
	}

	/**
	 * @test
	 */
	public function canMapFieldsWithStaticValues() {
		$mapping = <<<EOT
			pid = 1
			username = MY USERNAME
			name = MY NAME
			first_name = MY FIRST NAME
			last_name = MY LAST NAME
			address = MY ADDRESS
			telephone = MY TELEPHONE
			email = MY EMAIL
			title = MY TITLE
			zip = MY ZIP
			city = MY CITY
			country = MY COUNTRY
			www = MY WWW
			company = MY COMPANY
EOT;

		$expected = array(
			'pid' => '1',
			'tstamp' => 0,
			'username' => 'MY USERNAME',
			'password' => '',
			'usergroup' => '',
			'name' => 'MY NAME',
			'first_name' => 'MY FIRST NAME',
			'last_name' => 'MY LAST NAME',
			'address' => 'MY ADDRESS',
			'telephone' => 'MY TELEPHONE',
			'email' => 'MY EMAIL',
			'title' => 'MY TITLE',
			'zip' => 'MY ZIP',
			'city' => 'MY CITY',
			'country' => 'MY COUNTRY',
			'www' => 'MY WWW',
			'company' => 'MY COMPANY',
			'tx_igldapssoauth_dn' => '',
		);

		$mapping = \Causal\IgLdapSsoAuth\Library\Configuration::parseMapping($mapping);
		$user = \Causal\IgLdapSsoAuth\Library\Authentication::merge($this->ldapFixture, $this->typo3Fixture, $mapping);

		$this->assertEquals($expected, $user);
	}

	/**
	 * @test
	 */
	public function canMapEmail() {
		$mapping = <<<EOT
			email = <mail>
EOT;

		$mapping = \Causal\IgLdapSsoAuth\Library\Configuration::parseMapping($mapping);
		$user = \Causal\IgLdapSsoAuth\Library\Authentication::merge($this->ldapFixture, $this->typo3Fixture, $mapping);

		$this->assertEquals('newton@ldap.forumsys.com', $user['email']);
	}

	/**
	 * @test
	 */
	public function canMapToCurrentTimestamp() {
		// Just to be sure of the actual value
		$GLOBALS['EXEC_TIME'] = time();

		$mapping = <<<EOT
			tstamp = {DATE}
EOT;

		$mapping = \Causal\IgLdapSsoAuth\Library\Configuration::parseMapping($mapping);
		$user = \Causal\IgLdapSsoAuth\Library\Authentication::merge($this->ldapFixture, $this->typo3Fixture, $mapping);

		$this->assertEquals($GLOBALS['EXEC_TIME'], $user['tstamp']);
	}

	/**
	 * @test
	 */
	public function canMapLdapAttributes() {
		$mapping = <<<EOT
name = <cn>
last_name = <sn>
city = <l>
tx_igldapssoauth_dn = <dn>
EOT;

		$mapping = \Causal\IgLdapSsoAuth\Library\Configuration::parseMapping($mapping);
		$user = \Causal\IgLdapSsoAuth\Library\Authentication::merge($this->ldapFixture, $this->typo3Fixture, $mapping);

		$this->assertEquals('Isaac Newton', $user['name']);
		$this->assertEquals('Newton', $user['last_name']);
		$this->assertEquals('Woolsthorpe-by-Colsterworth', $user['city']);
		$this->assertEquals('uid=newton,dc=example,dc=com', $user['tx_igldapssoauth_dn']);
	}

	public function canMapMixCasedAttribute() {
		$mapping = <<<EOT
			// Actual LDAP attribute is "postalcode" (lowercase)
			zip = <postalCode>
EOT;

		$mapping = \Causal\IgLdapSsoAuth\Library\Configuration::parseMapping($mapping);
		$user = \Causal\IgLdapSsoAuth\Library\Authentication::merge($this->ldapFixture, $this->typo3Fixture, $mapping);

		$this->assertEquals('NG33', $user['zip']);
	}

	/**
	 * @test
	 */
	public function canCombineAttributes() {
		$mapping = <<<EOT
			address = <street>, <postalCode> <l>, <co>
EOT;

		$mapping = \Causal\IgLdapSsoAuth\Library\Configuration::parseMapping($mapping);
		$user = \Causal\IgLdapSsoAuth\Library\Authentication::merge($this->ldapFixture, $this->typo3Fixture, $mapping);

		$this->assertEquals('Woolsthorpe Manor, NG33 Woolsthorpe-by-Colsterworth, England', $user['address']);
	}

	/**
	 * @test
	 */
	public function canMapToArbitraryFields() {
		$mapping = <<<EOT
			custom_field = <l>
			custom_field2 = <l> (<postalCode>)
EOT;

		$mapping = \Causal\IgLdapSsoAuth\Library\Configuration::parseMapping($mapping);
		$user = \Causal\IgLdapSsoAuth\Library\Authentication::merge($this->ldapFixture, $this->typo3Fixture, $mapping);

		$this->assertArrayHasKey('__extraData', $user);

		$expectedData = array(
			'custom_field' => 'Woolsthorpe-by-Colsterworth',
			'custom_field2' => 'Woolsthorpe-by-Colsterworth (NG33)',
		);

		$this->assertEquals($expectedData, $user['__extraData']);
	}

	/**
	 * @test
	 */
	public function canWrapFieldWithTypoScript() {
		$mapping = <<<EOT
			last_name = <sn>
			last_name.wrap = |-suffix
EOT;

		$expected = $this->typo3Fixture;
		$expected['last_name'] = 'Newton-suffix';

		$mapping = \Causal\IgLdapSsoAuth\Library\Configuration::parseMapping($mapping);
		$user = \Causal\IgLdapSsoAuth\Library\Authentication::merge($this->ldapFixture, $this->typo3Fixture, $mapping);

		$this->assertEquals($expected, $user);
	}

	/**
	 * @test
	 */
	public function canCombineMultiValuedAttribute() {
		$mapping = <<<EOT
			# Populate the "value" of usergroup with every object class attribute from LDAP
			# These LDAP values are automatically pre-processed and separated by a line-feed ("\n")
			usergroup {
				field = objectclass
				split {
					token.char = 10
					cObjNum = 1
					1.current = 1
					1.noTrimWrap = ||, |
				}
				substring = 0,-2
			}
EOT;

		$mapping = \Causal\IgLdapSsoAuth\Library\Configuration::parseMapping($mapping);
		$user = \Causal\IgLdapSsoAuth\Library\Authentication::merge($this->ldapFixture, $this->typo3Fixture, $mapping);

		$this->assertEquals('inetOrgPerson, organizationalPerson, person, top', $user['usergroup']);
	}

	/**
	 * @test
	 */
	public function canExtractGivenNameFromFullName() {
		$mapping = <<<EOT
			first_name = <cn>
			# Extract everything up to the last blank space
			first_name.replacement.10 {
				search = /^(.*) ([^ ]+)$/
				replace = $1
				useRegExp = 1
			}
EOT;

		$mapping = \Causal\IgLdapSsoAuth\Library\Configuration::parseMapping($mapping);
		$user = \Causal\IgLdapSsoAuth\Library\Authentication::merge($this->ldapFixture, $this->typo3Fixture, $mapping);

		$this->assertEquals('Isaac', $user['first_name']);
	}

}