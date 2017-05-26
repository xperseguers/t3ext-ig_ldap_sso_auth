<?php
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

namespace Causal\IgLdapSsoAuth\Tests\Functional\Library;

/**
 * Test cases for class \Causal\IgLdapSsoAuth\Library\Ldap.
 */
class LdapTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{

    /**
     * @var \Causal\IgLdapSsoAuth\Library\Ldap
     */
    protected $fixture;

    /**
     * @var array
     */
    protected $data;

    protected function setUp()
    {
        $ldapUtility = $this->getMock(\Causal\IgLdapSsoAuth\Utility\LdapUtility::class, ['bind', 'search', 'getEntries', 'getFirstEntry', 'getDn']);
        $ldapUtility->expects($this->any())->method('bind')->will($this->returnCallback([$this, 'bindExecuteCallback']));
        $ldapUtility->expects($this->any())->method('search')->will($this->returnCallback([$this, 'searchExecuteCallback']));
        $ldapUtility->expects($this->any())->method('getEntries')->will($this->returnCallback([$this, 'getEntriesExecuteCallback']));
        $ldapUtility->expects($this->any())->method('getFirstEntry')->will($this->returnCallback([$this, 'getFirstEntryExecuteCallback']));
        $ldapUtility->expects($this->any())->method('getDn')->will($this->returnCallback([$this, 'getDnExecuteCallback']));

        $this->fixture = new \Causal\IgLdapSsoAuth\Library\Ldap();
        $this->inject($this->fixture, 'ldapUtility', $ldapUtility);
    }

    /**
     * @test
     * @dataProvider usernamePasswordProvider
     */
    public function validateUserReturnsEitherValidDnOrfalse($filter, $username, $password, $expected)
    {
        $result = $this->fixture->validateUser($username, $password, 'cn=read-only-admin,dc=example,dc=com', $filter);
        $this->assertEquals($expected, $result);
    }

    public function usernamePasswordProvider()
    {
        return [
            // Valid username/password using uid
            ['(uid={USERNAME})', 'newton', 'password', 'uid=newton,dc=example,dc=com'],
            ['(uid={USERNAME})', 'einstein', 'password', 'uid=einstein,dc=example,dc=com'],
            ['(uid={USERNAME})', 'tesla', 'password', 'uid=tesla,dc=example,dc=com'],
            ['(uid={USERNAME})', 'galieleo', 'password', 'uid=galieleo,dc=example,dc=com'],
            // Valid username/password using mail
            ['(mail={USERNAME})', 'newton@ldap.forumsys.com', 'password', 'uid=newton,dc=example,dc=com'],
            ['(mail={USERNAME})', 'einstein@ldap.forumsys.com', 'password', 'uid=einstein,dc=example,dc=com'],
            ['(mail={USERNAME})', 'tesla@ldap.forumsys.com', 'password', 'uid=tesla,dc=example,dc=com'],
            ['(mail={USERNAME})', 'galieleo@ldap.forumsys.com', 'password', 'uid=galieleo,dc=example,dc=com'],
            // Invalid username/password using uid
            ['(uid={USERNAME})', '', '', false],
            ['(uid={USERNAME})', '', 'password', false],
            ['(uid={USERNAME})', null, '', false],
            ['(uid={USERNAME})', 'newton', '', false],
            ['(uid={USERNAME})', 'einstein', 'invalid password', false],
            // Invalid username/password using mail
            ['(mail={USERNAME})', '', '', false],
            ['(mail={USERNAME})', '', 'password', false],
            ['(mail={USERNAME})', null, '', false],
            ['(mail={USERNAME})', 'newton@ldap.forumsys.com', '', false],
            ['(mail={USERNAME})', 'einstein@ldap.forumsys.com', 'invalid password', false],
            // null password using uid (no SSO here)
            ['(uid={USERNAME})', '', null, false],
            ['(uid={USERNAME})', null, null, false],
            ['(uid={USERNAME})', 'newton', null, false],
            ['(uid={USERNAME})', 'einstein', null, false],
            ['(uid={USERNAME})', 'tesla', null, false],
            ['(uid={USERNAME})', 'galieleo', null, false],
        ];
    }

    /**
     * @test
     * @dataProvider usernameSsoProvider
     */
    public function validateUserSupportsSSO($filter, $username, $expected)
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ig_ldap_sso_auth'] = serialize([
            'enableFESSO' => 1,
        ]);
        \Causal\IgLdapSsoAuth\Library\Configuration::initialize('fe', new \Causal\IgLdapSsoAuth\Domain\Model\Configuration());

        $result = $this->fixture->validateUser($username, null, 'cn=read-only-admin,dc=example,dc=com', $filter);
        $this->assertEquals($expected, $result);
    }

    public function usernameSsoProvider()
    {
        return [
            // Valid username using uid
            ['(uid={USERNAME})', 'newton', 'uid=newton,dc=example,dc=com'],
            ['(uid={USERNAME})', 'einstein', 'uid=einstein,dc=example,dc=com'],
            ['(uid={USERNAME})', 'tesla', 'uid=tesla,dc=example,dc=com'],
            ['(uid={USERNAME})', 'galieleo', 'uid=galieleo,dc=example,dc=com'],
            // Valid username using mail
            ['(mail={USERNAME})', 'newton@ldap.forumsys.com', 'uid=newton,dc=example,dc=com'],
            ['(mail={USERNAME})', 'einstein@ldap.forumsys.com', 'uid=einstein,dc=example,dc=com'],
            ['(mail={USERNAME})', 'tesla@ldap.forumsys.com', 'uid=tesla,dc=example,dc=com'],
            ['(mail={USERNAME})', 'galieleo@ldap.forumsys.com', 'uid=galieleo,dc=example,dc=com'],
            // Invalid username using uid
            ['(uid={USERNAME})', '', false],
            ['(uid={USERNAME})', null, false],
            ['(uid={USERNAME})', 'invalid-username', false],
            // Invalid username using mail
            ['(mail={USERNAME})', '', false],
            ['(mail={USERNAME})', null, false],
            ['(mail={USERNAME})', 'invalid-username', false],
        ];
    }

    /**
     * @test
     */
    public function searchUsersReturnsEmptyArrayForInvalidBaseDn()
    {
        $baseDn = 'cn=INVALID,dc=example,dc=com';
        $users = $this->fixture->search($baseDn);
        $this->assertEquals([], $users);
    }

    /**
     * @test
     */
    public function searchUsersReturnsResultsForValidBaseDn()
    {
        $baseDn = 'cn=read-only-admin,dc=example,dc=com';
        $filter = '(uid=*)';
        $users = $this->fixture->search($baseDn, $filter);
        $this->assertEquals(9, $users['count']);
        unset($users['count']);
        $this->assertEquals(9, count($users));
    }

    /**
     * @test
     */
    public function searchUsersReturnsExactlyOneResult()
    {
        $baseDn = 'cn=read-only-admin,dc=example,dc=com';
        $filter = '(uid=*)';
        $users = $this->fixture->search($baseDn, $filter, [], true);
        $this->assertEquals('uid=newton,dc=example,dc=com', $users['dn']);
    }

    /**
     * @test
     */
    public function searchGroupsReturnsEmptyArrayForInvalidBaseDn()
    {
        $baseDn = 'cn=INVALID,dc=example,dc=com';
        $groups = $this->fixture->search($baseDn);
        $this->assertEquals([], $groups);
    }

    /**
     * @test
     */
    public function searchGroupsReturnsResultsForValidBaseDn()
    {
        $baseDn = 'cn=read-only-admin,dc=example,dc=com';
        $filter = '(ou=*)';
        $groups = $this->fixture->search($baseDn, $filter);
        $this->assertEquals(3, $groups['count']);
        unset($groups['count']);
        $this->assertEquals(3, count($groups));
    }

    /**
     * @test
     */
    public function searchGroupsReturnsExactlyOneResult()
    {
        $baseDn = 'cn=read-only-admin,dc=example,dc=com';
        $filter = '(ou=*)';
        $groups = $this->fixture->search($baseDn, $filter, [], true);
        $this->assertEquals('ou=mathematicians,dc=example,dc=com', $groups['dn']);
    }

    public function bindExecuteCallback($dn, $password)
    {
        return $this->data[0]['dn'] === $dn && $password === 'password';
    }

    public function searchExecuteCallback($baseDn, $filter, array $attributes, $attributesOnly, $sizeLimit, $timeLimit, $dereferenceAliases)
    {
        $success = false;
        if ($baseDn === 'cn=read-only-admin,dc=example,dc=com') {
            // WARNING: support only for single condition "(key=value)"
            list($key, $searchValue) = explode('=', substr($filter, 1, -1), 2);
            $file = ($key === 'ou') ? 'Groups.json' : 'Users.json';
            $rows = $this->loadData($file);
            unset($rows['count']);

            $this->data = ['count' => 0];
            foreach ($rows as $row) {
                $testRow = $row;
                if (!isset($testRow[$key]) && !empty($testRow['dn'])) {
                    list($firstSegment,) = explode(',', $testRow['dn'], 2);
                    list ($firstSegmentKey, $value) = explode('=', $firstSegment, 2);
                    $testRow[$firstSegmentKey] = $value;
                }

                $match = false;
                if (isset($testRow[$key])) {
                    $match = ($searchValue === '*' || $testRow[$key] === $searchValue);
                    if (!$match && is_array($testRow[$key])) {
                        $values = $testRow[$key];
                        unset($values['count']);
                        for ($i = 0; $i < count($values) && !$match; $i++) {
                            $match |= $values[$i] === $searchValue;
                        }
                    }
                }

                if ($match) {
                    $this->data[] = $row;
                    $this->data['count']++;
                }
            }

            $success = $this->data['count'] > 0;
        }
        return $success;
    }

    public function getEntriesExecuteCallback()
    {
        return $this->data;
    }

    public function getFirstEntryExecuteCallback()
    {
        return $this->data[0];
    }

    public function getDnExecuteCallback()
    {
        return $this->data[0]['dn'];
    }

    private function loadData($file)
    {
        $extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ig_ldap_sso_auth');
        $json = file_get_contents($extPath . 'Tests/Functional/Fixtures/' . $file);
        $data = json_decode($json, true);
        return $data;
    }

}
