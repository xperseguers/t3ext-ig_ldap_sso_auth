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
 * Testcase for class \Causal\IgLdapSsoAuth\Library\Configuration.
 */
class ConfigurationTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 * @dataProvider usernameFilterProvider
	 */
	public function canGetUsernameAttribute($filter, $expected) {
		$attribute = \Causal\IgLdapSsoAuth\Library\Configuration::get_username_attribute($filter);
		$this->assertSame($expected, $attribute);
	}

	public function usernameFilterProvider() {
		return array(
			array('', ''),
			array(NULL, ''),
			array('uid={USERNAME}', 'uid'),
			array('(uid={USERNAME})', 'uid'),
			array('(&(objectClass=organizationalPerson)(mail=*@domain*)(sAMAccountName={USERNAME}))', 'sAMAccountName'),
		);
	}

}