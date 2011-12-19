<?php

/* * *************************************************************
 * Copyright notice
 *
 * (c) 2007-2011 Michaël Gagnon <mgagnon@infoglobe.ca>
 * All rights reserved
 *
 * Is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * Class tx_igldapssoauth_utility_Debug.
 *
 * Debug TYPO3 variables.
 *
 * @access public
 * @package TYPO3
 * @subpackage iglib
 * @author	   Michael Gagnon <mgagnon@infoglobe.ca>
 * @copyright Copyright (c) Infoglobe 2007
 * @version $Id: tx_igldapssoauth_utility_Debug
 *
 * $BE_USER
 * $LANG
 * $BACK_PATH
 * $TCA_DESCR
 * $TCA
 * $CLIENT
 * $TYPO3_CONF_VARS
 */
class tx_igldapssoauth_utility_Debug {

	function print_this($value = null, $comment = null, $var_dump = false) {

		echo '<hr />';

		echo $comment ? '<h2>' . $comment . '</h2>' : null;

		if ($var_dump) {

			echo '<strong>var_dump: </strong><br /><br />';

			//var_export($to_print);

			print_r("<code>");
			print_r(var_dump($value));
			print_r("</code>");

			echo '<br /><br /><strong>print_r: </strong>';
		}

		print_r("<code>");
		print_r($value);
		print_r("</code>");

		echo '<hr />';
	}

	function session($comment = null) {

		tx_igldapssoauth_utility_Debug::print_this($_SESSION, $comment ? $comment : 'SESSION');
	}

	function post($comment = null) {

		tx_igldapssoauth_utility_Debug::print_this($_POST, $comment ? $comment : 'POST');
	}

	function get($comment = null) {

		tx_igldapssoauth_utility_Debug::print_this($_GET, $comment ? $comment : 'GET');
	}

	function be_user($comment = null) {

		global $BE_USER;

		tx_igldapssoauth_utility_Debug::print_this($BE_USER, $comment ? $comment : 'BE_USER');
	}

	function lang($comment = null) {

		global $LANG;

		tx_igldapssoauth_utility_Debug::print_this($LANG, $comment ? $comment : 'LANG');
	}

	function tca($comment = null) {

		global $TCA;

		tx_igldapssoauth_utility_Debug::print_this($TCA, $comment ? $comment : 'TCA');
	}

}

?>