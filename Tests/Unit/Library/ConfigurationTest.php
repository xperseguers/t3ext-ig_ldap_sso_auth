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

namespace Causal\IgLdapSsoAuth\Tests\Unit\Library;

use Causal\IgLdapSsoAuth\Library\Configuration;

/**
 * Test cases for class \Causal\IgLdapSsoAuth\Library\Configuration.
 */
class ConfigurationTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{

    /**
     * @test
     * @dataProvider usernameFilterProvider
     */
    public function canGetUsernameAttribute($filter, $expected)
    {
        $attribute = Configuration::getUsernameAttribute($filter);
        $this->assertSame($expected, $attribute);
    }

    public function usernameFilterProvider()
    {
        return [
            ['', ''],
            [null, ''],
            ['uid={USERNAME}', 'uid'],
            ['(uid={USERNAME})', 'uid'],
            ['(&(uid={USERNAME})(objectClass=posixAccount)', 'uid'],
            ['(&(objectClass=organizationalPerson)(mail=*@domain*)(sAMAccountName={USERNAME}))', 'sAMAccountName'],
        ];
    }

    /**
     * @test
     */
    public function emptyLinesAreRemovedFromMapping()
    {
        $mapping = <<<EOT

            pid = 1
            tstamp = {DATE}

            email=<mail>


            first_name =  <givenName>
            last_name   =<sn>

EOT;
        $expected = [
            'pid' => '1',
            'tstamp' => '{DATE}',
            'email' => '<mail>',
            'first_name' => '<givenName>',
            'last_name' => '<sn>',
        ];

        $actual = Configuration::parseMapping($mapping);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function commentsOrInvalidMappingLinesAreIgnored()
    {
        $mapping = <<<EOT
            // This is a comment
            pid = 1
            tstamp = {DATE}

            // Another comment
            email = <mail>

            partial_definition =
EOT;
        $expected = [
            'pid' => '1',
            'tstamp' => '{DATE}',
            'email' => '<mail>',
        ];

        $actual = Configuration::parseMapping($mapping);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function canCombineLdapAttributeAndStaticContentInMapping()
    {
        $mapping = <<<EOT
            name = <sn>, <givenName>
            telephone = Tel. <telephoneNumber>
EOT;
        $expected = [
            'name' => '<sn>, <givenName>',
            'telephone' => 'Tel. <telephoneNumber>',
        ];

        $actual = Configuration::parseMapping($mapping);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function canUseEqualSignInMapping()
    {
        $mapping = <<<EOT
            myfield = <sn> =   <givenName>
            other = <sn> <=> <givenName>
EOT;
        $expected = [
            'myfield' => '<sn> =   <givenName>',
            'other' => '<sn> <=> <givenName>',
        ];

        $actual = Configuration::parseMapping($mapping);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function canParseSimpleTypoScriptWrap()
    {
        $mapping = <<<EOT
            name = <sn>, <givenName>
            name.wrap = prefix- | -suffix
EOT;

        $expected = [
            'name' => '<sn>, <givenName>',
            'name.' => [
                'wrap' => 'prefix- | -suffix',
            ],
        ];

        $actual = Configuration::parseMapping($mapping);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function canParseMultiLineTypoScript()
    {
        $mapping = <<<EOT
            name = <sn>, <givenName>
            name {
                wrap = prefix- |
                wrap2 = | -suffix
            }
EOT;

        $expected = [
            'name' => '<sn>, <givenName>',
            'name.' => [
                'wrap' => 'prefix- |',
                'wrap2' => '| -suffix',
            ],
        ];

        $actual = Configuration::parseMapping($mapping);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function simpleMappingIsNotExtended()
    {
        $mapping = <<<EOT
            name = <sn>, <givenName>
            first_name = <givenName>
            last_name = <sn>
            address = <street>, <postalCode> <l>, <co>
EOT;

        $mapping = Configuration::parseMapping($mapping);
        $result = Configuration::hasExtendedMapping($mapping);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function mappingWithHooksIsExtended()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraMergeField'] = ['foo'];
        $mapping = <<<EOT
            name = {special}
EOT;

        $mapping = Configuration::parseMapping($mapping);
        $result = Configuration::hasExtendedMapping($mapping);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function mappingWithExtraDatasIsExtended()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraDataProcessing'] = ['foo'];
        $mapping = <<<EOT
            custom-field = foobar
EOT;

        $mapping = Configuration::parseMapping($mapping);
        $result = Configuration::hasExtendedMapping($mapping);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function mappingWithTypoScriptIsExtended()
    {
        $mapping = <<<EOT
            name = <sn>, <givenName>
            name.wrap = |
EOT;

        $mapping = Configuration::parseMapping($mapping);
        $result = Configuration::hasExtendedMapping($mapping);

        $this->assertTrue($result);
    }

}
