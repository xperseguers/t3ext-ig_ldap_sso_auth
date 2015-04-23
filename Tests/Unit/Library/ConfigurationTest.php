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
 * Test cases for class \Causal\IgLdapSsoAuth\Library\Configuration.
 */
class ConfigurationTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 * @dataProvider usernameFilterProvider
	 */
	public function canGetUsernameAttribute($filter, $expected) {
		$attribute = \Causal\IgLdapSsoAuth\Library\Configuration::getUsernameAttribute($filter);
		$this->assertSame($expected, $attribute);
	}

	public function usernameFilterProvider() {
		return array(
			array('', ''),
			array(NULL, ''),
			array('uid={USERNAME}', 'uid'),
			array('(uid={USERNAME})', 'uid'),
			array('(&(uid={USERNAME})(objectClass=posixAccount)', 'uid'),
			array('(&(objectClass=organizationalPerson)(mail=*@domain*)(sAMAccountName={USERNAME}))', 'sAMAccountName'),
		);
	}

	/**
	 * @test
	 */
	public function emptyLinesAreRemovedFromMapping() {
		$mapping = <<<EOT

			pid = 1
			tstamp = {DATE}

			email = <mail>


			first_name = <givenName>
			last_name = <sn>

EOT;
		$expected = array(
			'pid' => '1',
			'tstamp' => '{DATE}',
			'email' => '<mail>',
			'first_name' => '<givenName>',
			'last_name' => '<sn>',
		);

		$actual = \Causal\IgLdapSsoAuth\Library\Configuration::makeMapping($mapping);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function commentsOrInvalidMappingLinesAreIgnored() {
		$mapping = <<<EOT
			// This is a comment
			pid = 1
			tstamp = {DATE}

			// Another comment
			email = <mail>

			partial_definition =
EOT;
		$expected = array(
			'pid' => '1',
			'tstamp' => '{DATE}',
			'email' => '<mail>',
		);

		$actual = \Causal\IgLdapSsoAuth\Library\Configuration::makeMapping($mapping);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function canCombineLdapAttributeAndStaticContentInMapping() {
		$mapping = <<<EOT
			name = <sn>, <givenName>
			telephone = Tel. <telephoneNumber>
EOT;
		$expected = array(
			'name' => '<sn>, <givenName>',
			'telephone' => 'Tel. <telephoneNumber>',
		);

		$actual = \Causal\IgLdapSsoAuth\Library\Configuration::makeMapping($mapping);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function canUseEqualSignInMapping() {
		$mapping = <<<EOT
			myfield = <sn> =   <givenName>
			other = <sn> <=> <givenName>
EOT;
		$expected = array(
			'myfield' => '<sn> =   <givenName>',
			'other' => '<sn> <=> <givenName>',
		);

		$actual = \Causal\IgLdapSsoAuth\Library\Configuration::makeMapping($mapping);
		$this->assertEquals($expected, $actual);
	}

}