<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2007 Michael Gagnon <mgagnon@infoglobe.ca>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * Class tx_igldapssoauth_utility_Db.
 *
 * Manage query. This class use API TYPO3 DBAL.
 *
 * @access	public
 * @package	TYPO3
 * @subpackage	iglib
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @copyright	(c) 2007 Michael Gagnon <mgagnon@infoglobe.ca>
 * @version	$Id: class.tx_igldapssoauth_utility_db.php
 * @see class.tx_igldapssoauth_utility_db.php
 */
class tx_igldapssoauth_utility_Db {

	/**
	 * Execute insert query.
	 *
	 * @access	public
	 * @param array 	Insert query array for at exec_INSERTquery function.
	 * @return integer	Last inserted uid.
	 */
	function insert($query = array()) {

		$link = $GLOBALS['TYPO3_DB']->exec_INSERTquery($query['TABLE'], $query['FIELDS_VALUES'], $query['NO_QUOTE_FIELDS']);

		return $GLOBALS['TYPO3_DB']->sql_insert_id();
	}

	/**
	 * Execute update query.
	 *
	 * @access	public
	 * @param	array		Updatd query array for exec_UPDATEquery function.
	 * @return	pointer		MySQL result pointer / DBAL object.
	 */
	function update($query = array()) {

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery($query['TABLE'], $query['WHERE'], $query['FIELDS_VALUES'], $query['NO_QUOTE_FIELDS']);

		return $GLOBALS['TYPO3_DB']->sql_affected_rows();
	}

	/**
	 * Execute select query.
	 *
	 * @access	public
	 * @param	array	Select query array for exec_SELECTgetRows function.
	 * @return	array	Query result array.
	 */
	function select($query = array()) {

		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($query['SELECT'], $query['FROM'], $query['WHERE'], $query['GROUP_BY'], $query['ORDER_BY'], $query['LIMIT'], $query['UID_INDEX_FIELD']);
	}

	/**
	 * Execute select many-many query.
	 *
	 * @access	public
	 * @param	array 	Select many-many query for exec_SELECT_mm_query function.
	 * @return	pointer	MySQL result pointer / DBAL object.
	 */
	function select_mm($query = array()) {

		return $this->get_rows($GLOBALS['TYPO3_DB']->exec_SELECT_mm_query($query['SELECT'], $query['LOCAL_TABLE'], $query['MM_TABLE'], $query['FOREIGN_TABLE'], $query['WHERE'], $query['GROUP_BY'], $query['ORDER_BY'], $query['LIMIT']));
	}

	/**
	 * Make array of results.
	 *
	 * @access	private
	 * @param	pointer	MySQL result pointer / DBAL object.
	 * @return	array 	Result array.
	 */
	function get_rows($link) {

		$result = array();

		if ($link) {

			//Build array of results.
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($link)) {

				$result[] = $row;
			}
		}

		return $result;
	}

	/**
	 * Return table structure.
	 *
	 * @access	private
	 * @param	array 	Select many-many query for exec_SELECT_mm_query function.
	 * @return	pointer	MySQL result pointer / DBAL object.
	 */
	function get_columns_from($table = null) {

		return $GLOBALS['TYPO3_DB']->admin_get_fields($table);
	}

}

?>
