<?php

/***************************************************************
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

// DEFAULT initialization of a module [BEGIN]

unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');
require_once(PATH_t3lib.'class.t3lib_scbase.php');

// Load locallang
$LANG->includeLLFile('EXT:ig_ldap_sso_auth/res/locallang_mod1.xml');

// This checks permissions and exits if the users has no permission for entry.
$BE_USER->modAccess($MCONF,1);

// DEFAULT initialization of a module [END]

/**
 * Module 'LDAP configuration' for the 'ig_ldap_sso_auth' extension.
 *
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @package	TYPO3
 * @subpackage	tx_igldapssoauth
 *
 * $Id$
 */
class  tx_igldapssoauth_module1 extends t3lib_SCbase {

	var $pageinfo;
	var $config;
	var $lang;

	/**
	 * Initializes the Module
	 *
	 * @return	void
	 */
	function init()	{

		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();

		/*
		if (t3lib_div::_GP('clear_all_cache'))	{
			$this->include_once[] = PATH_t3lib.'class.t3lib_tcemain.php';
		}
		*/
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{

		global $LANG;

		$this->MOD_MENU = Array (
			'function' => Array (
				'1' => $LANG->getLL('view_configuration'),
				'2' => $LANG->getLL('wizard_search'),
				'4' => $LANG->getLL('import_groups'),
			)
		);

		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	[type]		...
	 */
	function main()	{

		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		// The page will show only if there is a valid page and if this page may be viewed by the user
		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{

			tx_igldapssoauth_config::init('', 0);

			$this->config = tx_igldapssoauth_config::get_values();

			$this->lang = $LANG;

			$this->doc = t3lib_div::makeInstance('mediumDoc');

			#HEADER
			$this->doc->backPath = $BACK_PATH;

			#JS
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';

			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';

			$this->doc->form='<form action="" method="POST">';

			$this->content[] = $this->doc->startPage($LANG->getLL('title'));

			#TITLE
			$this->content[] = $this->doc->header($LANG->getLL('title'));

			#MENU
			$this->content[] = $this->doc->funcMenu('',t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function']));

			#CONTENT
			global $EXT_CONFIG;
			$uidConf = $EXT_CONFIG['uidConfiguration'];
			$uidArray = t3lib_div::trimExplode(',', $uidConf);
			if(is_array($uidArray)) {
				foreach($uidArray as $uid) {
					tx_igldapssoauth_config::init(TYPO3_MODE, $uid);
					$this->config = tx_igldapssoauth_config::get_values();
					$this->content[] = '<h2>'.$this->lang->getLL('view_configuration_title').'&nbsp;'.$this->config['name'].'&nbsp;('.$this->config['uid'].')</h2>';
					$this->content[] = '<hr />';
					
					switch((string)$this->MOD_SETTINGS['function'])	{
		
						case 1:
		
							$this->view_configuration();
							break;
		
						case 2:
		
							$this->wizard_search(t3lib_div::_GP('search'));
							break;
		
						case 3:
		
							//$this->wizard_authentication(t3lib_div::_GP('authentication'));
							break;
		
						case 4:
		
							$this->import_groups();
							break;
	
					}
				}
			}

			#SHORTCUT
			if ($BE_USER->mayMakeShortcut())	{

				$this->content[] = $this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));

			}

			#FOOTER
			$this->content[] = $this->doc->spacer(10);
			$this->content[] = $this->doc->endPage();


		// If no access or if ID == zero
		} else {

			#HEADER
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->content[] = $this->doc->startPage($LANG->getLL('title'));

			#TITLE
			$this->content[] = $this->doc->header($LANG->getLL('title'));
			$this->content[] = $this->doc->spacer(5);

			#FOOTER
			$this->content[] = $this->doc->spacer(10);
			$this->content[] = $this->doc->endPage();

		}

	}
	

	function view_configuration () {

		// LDAP
		$this->content[] = '<h3>'.$this->lang->getLL('view_configuration_ldap').'</h3>';
		$this->content[] = '<hr />';

		if ($this->config['ldap']['host']) {

			$this->config['ldap']['server'] = tx_igldapssoauth_config::get_server_name($this->config['ldap']['server']);

			tx_igldapssoauth_ldap::connect($this->config['ldap']);
			$this->config['ldap']['password'] = $this->config['ldap']['password'] ? '********' : null;

			$this->content[] = t3lib_div::view_array($this->config['ldap']);


			$this->content[] = '<h3><strong>'.$this->lang->getLL('view_configuration_ldap_connexion_status').'</strong></h3>';
			$this->content[] = '<h4>'.t3lib_div::view_array(tx_igldapssoauth_ldap::get_status()).'</h4>';

		} else {

			$this->content[] = '<strong>'.$this->lang->getLL('view_configuration_ldap_disable').'</strong>';
			return false;

		}


		// CAS
		$this->content[] = '<h3>'.$this->lang->getLL('view_configuration_cas').'</h3>';
		$this->content[] = '<hr />';

		if ($this->config['fe']['LDAPAuthentication'] && $this->config['fe']['CASAuthentication']) {

			$this->content[] = t3lib_div::view_array($this->config['cas']);

		} else {

			$this->content[] = '<strong>'.$this->lang->getLL('view_configuration_cas_disable').'</strong>';

		}

		// BE
		$this->content[] = '<h3>'.$this->lang->getLL('view_configuration_backend_authentication').'</h3>';
		$this->content[] = '<hr />';

		if ($this->config['be']['LDAPAuthentication']) {

			$this->content[] = t3lib_div::view_array($this->config['be']);

		} else {

			$this->content[] = '<strong>'.$this->lang->getLL('view_configuration_backend_authentication_disable').'</strong>';

		}

		// FE
		$this->content[] = '<h3>'.$this->lang->getLL('view_configuration_frontend_authentication').'</h3>';
		$this->content[] = '<hr />';

		if ($this->config['fe']['LDAPAuthentication']) {

			$this->content[] = t3lib_div::view_array($this->config['fe']);

		} else {

			$this->content[] = '<strong>'.$this->lang->getLL('view_configuration_frontend_authentication_disable').'</strong>';

		}

	}

	function wizard_search ($search = array()) {

		switch ($search['action']) {

			case 'select' :

				list($typo3_mode, $table) = explode('_', $search['table']);

				$search['basedn'] = $this->config[$typo3_mode][$table]['basedn'];
				$search['filter']  = tx_igldapssoauth_config::replace_filter_markers($this->config[$typo3_mode][$table]['filter']);
				$search['attributes'] = $search['first_entry'] ? '' : implode(',', tx_igldapssoauth_config::get_ldap_attributes($this->config[$typo3_mode][$table]['mapping']));

			break;

			case 'search' :

			break;

			default :

				$search['table'] = 'be_users';

				list($typo3_mode, $table) = explode('_', $search['table']);

				$search['first_entry'] = true;
				$search['see_status'] = false;
				$search['basedn'] = $this->config[$typo3_mode][$table]['basedn'];
				$search['filter']  = tx_igldapssoauth_config::replace_filter_markers($this->config[$typo3_mode][$table]['filter']);
				$search['attributes'] = $search['first_entry'] ? '' : implode(',', tx_igldapssoauth_config::get_ldap_attributes($this->config[$typo3_mode][$table]['mapping']));

			break;

		}

		$this->content[] = '<h2>'.$this->lang->getLL('wizard_search_title').'</h2>';
		$this->content[] = '<hr />';

		if (tx_igldapssoauth_ldap::connect($this->config['ldap'])) {
			if(is_array($search['basedn'])) {
				$search['basedn'] = implode('||',$search['basedn']);
			}
			$first_entry = $search['first_entry'] ? 'checked="checked"' : "";
			$see_status = $search['see_status'] ? 'checked="checked"' : "";
			$be_users = ($search['table'] == 'be_users') ? 'checked="checked"' : "";
			$be_groups = ($search['table'] == 'be_groups') ? 'checked="checked"' : "";
			$fe_users = ($search['table'] == 'fe_users') ? 'checked="checked"' : "";
			$fe_groups = ($search['table'] == 'fe_groups') ? 'checked="checked"' : "";

			$this->content[] = '<form action="" method="post" name="search">';

			$this->content[] = '<fieldset>';

			$this->content[] = '<br /><div>';
			$this->content[] = '<span><input type="radio" name="search[table]" value="be_users" '.$be_users.' onclick="this.form.elements[\'search[action]\'].value=\'select\';submit();return false;" />&nbsp;<strong>'.$this->lang->getLL('wizard_search_radio_be_users').'</strong></span>';
			$this->content[] = '<span><input type="radio" name="search[table]" value="fe_users" '.$fe_users.' onclick="this.form.elements[\'search[action]\'].value=\'select\';submit();return false;" />&nbsp;<strong>'.$this->lang->getLL('wizard_search_radio_fe_users').'</strong></span>';
			$this->content[] = '<span><input type="radio" name="search[table]" value="be_groups" '.$be_groups.' onclick="this.form.elements[\'search[action]\'].value=\'select\';submit();return false;" />&nbsp;<strong>'.$this->lang->getLL('wizard_search_radio_be_groups').'</strong></span>';
			$this->content[] = '<span><input type="radio" name="search[table]" value="fe_groups" '.$fe_groups.' onclick="this.form.elements[\'search[action]\'].value=\'select\';submit();return false;" />&nbsp;<strong>'.$this->lang->getLL('wizard_search_radio_fe_groups').'</strong></span>';
			$this->content[] = '</div><br />';

			$this->content[] = '<div>';
			$this->content[] = '<span><input type="checkbox" name="search[first_entry]" value="true" '.$first_entry.' onclick="this.form.elements[\'search[action]\'].value=\'select\';submit();return false;" />&nbsp;<strong>'.$this->lang->getLL('wizard_search_checkbox_first_entry').'</strong></span>';
			$this->content[] = '<span><input type="checkbox" name="search[see_status]" value="true" '.$see_status.' onclick="this.form.elements[\'search[action]\'].value=\'select\';submit();return false;" />&nbsp;<strong>'.$this->lang->getLL('wizard_search_checkbox_see_status').'</strong></span>';
			$this->content[] = '</div><br />';

			$this->content[] = '<div><strong>'.$this->lang->getLL('wizard_search_input_base_dn').'</strong>&nbsp;<input type="text" name="search[basedn]" value="'.$search['basedn'].'" size="50" /></div><br />';
			$this->content[] = '<div><strong>'.$this->lang->getLL('wizard_search_input_filter').'</strong>&nbsp;<input type="text" name="search[filter]" value="'.$search['filter'].'" size="50" /></div><br />';
			$this->content[] = $search['attributes'] ? '<div><strong>'.$this->lang->getLL('wizard_search_input_attributes').'</strong>&nbsp;<input type="text" name="search[attributes]" value="'.$search['attributes'].'" size="50" /></div><br />' : '';

			$this->content[] = '<input type="hidden" name="search[action]" value="'.$search['action'].'" />';
			$this->content[] = '<input type="submit" value="'.$this->lang->getLL('wizard_search_submit_search').'" onclick="this.form.elements[\'search[action]\'].value=\'search\';" />';

			$this->content[] = '</fieldset>';

			$this->content[] = '</form><br />';

			$attributes = array();

			if (!$search['first_entry'] || !empty($search['attributes'])) {

				$attributes = explode(',', $search['attributes']);

			}
			$search['basedn'] = explode('||', $search['basedn']);
			if ($result = tx_igldapssoauth_ldap::search($search['basedn'], $search['filter'], $attributes, $search['first_entry'])) {

				$this->content[] = $search['see_status'] ? '<h2>'.$this->lang->getLL('wizard_search_ldap_status').'</h2><hr />'.t3lib_div::view_array(tx_igldapssoauth_ldap::get_status()) : null;
				$this->content[] = '<h2>'.$this->lang->getLL('wizard_search_result').'</h2>';
				$this->content[] = '<hr />';
				$this->content[] = t3lib_div::view_array($result);

			} else {

				$this->content[] = $search['see_status'] ? '<h2>'.$this->lang->getLL('wizard_search_ldap_status').'</h2><hr />'.t3lib_div::view_array(tx_igldapssoauth_ldap::get_status()) : null;
				$this->content[] = '<h2>'.$this->lang->getLL('wizard_search_no_result').'</h2>';
				$this->content[] = '<hr />';
				$this->content[] = t3lib_div::view_array(array());

			}

			tx_igldapssoauth_ldap::disconnect();

		} else {

			$this->content[] = '<h2>'.$this->lang->getLL('wizard_search_ldap_status').'</h2><hr />'.t3lib_div::view_array(tx_igldapssoauth_ldap::get_status());

		}

	}

	function import_groups () {

		$typo3_modes = array('fe', 'be');
		$import_groups = t3lib_div::_GP('import');

		$this->content[] = '<h2>'.$this->lang->getLL('import_groups_title').'</h2>';
		$this->content[] = '<hr />';

		if (tx_igldapssoauth_ldap::connect($this->config['ldap'])) {

			foreach ($typo3_modes as $typo3_mode) {

				if ($ldap_groups = tx_igldapssoauth_ldap::search($this->config[$typo3_mode]['groups']['basedn'], tx_igldapssoauth_config::replace_filter_markers($this->config[$typo3_mode]['groups']['filter']), tx_igldapssoauth_config::get_ldap_attributes($this->config[$typo3_mode]['groups']['mapping']))) {

					$this->content[] = '<form action="" method="post" name="import_'.$typo3_mode.'_groups">';

					$this->content[] = '<fieldset>';

					$this->content[] = '<table border="1">';

					$this->content[] = '<tr>' .
											'<th>'.$this->lang->getLL('import_groups_table_th_title').'</th>' .
											'<th>'.$this->lang->getLL('import_groups_table_th_dn').'</th>' .
											'<th>'.$this->lang->getLL('import_groups_table_th_pid').'</th>' .
											'<th>'.$this->lang->getLL('import_groups_table_th_uid').'</th>' .
											'<th>'.$this->lang->getLL('import_groups_table_th_import').'</th>' .
										'</tr>';

					$this->content[] = '<caption><h2>'.$this->lang->getLL('import_groups_'.$typo3_mode.'_title').'</h2></caption>';

					$typo3_group_pid = tx_igldapssoauth_config::get_pid($this->config[$typo3_mode]['groups']['mapping']);
					$typo3_groups = tx_igldapssoauth_auth::get_typo3_groups($ldap_groups, $this->config[$typo3_mode]['groups']['mapping'], $typo3_mode.'_groups', $typo3_group_pid);

					unset($ldap_groups['count']);

					foreach ($ldap_groups as $index => $ldap_group) {
					$typo3_group = tx_igldapssoauth_auth::merge($ldap_group, $typo3_groups[$index], $this->config[$typo3_mode]['groups']['mapping']);
						if (isset($import_groups[$typo3_mode]) && in_array($typo3_group['tx_igldapssoauth_dn'], $import_groups[$typo3_mode])) {
							unset($typo3_group['parentGroup']);
							$typo3_group = tx_igldapssoauth_typo3_group::insert($typo3_mode.'_groups', $typo3_group);
							$typo3_group = $typo3_group[0];
							
							$fieldParent = $this->config[$typo3_mode]['groups']['mapping']['parentGroup'];
							preg_match("`<([^$]*)>`", $fieldParent, $attribute);
							$fieldParent = $attribute[1];

							if(is_array($ldap_group[$fieldParent])){
								unset($ldap_group[$fieldParent]['count']);
								if(is_array($ldap_group[$fieldParent])){
									$this->setParentGroup($ldap_group[$fieldParent],$fieldParent,$typo3_group['uid'],$typo3_group_pid,$typo3_mode);
								}
							}
						}

						$this->content[] = '<tr>' .
												'<td>'.($typo3_group['title'] ? $typo3_group['title'] : '&nbsp;').'</td>' .
												'<td>'.$typo3_group['tx_igldapssoauth_dn'].'</td>' .
												'<td>'.($typo3_group['pid'] ? $typo3_group['pid'] : 0).'</td>' .
												'<td>'.($typo3_group['uid'] ? $typo3_group['uid'] : 0).'</td>' .
												'<td align="center"><input type="checkbox" name="import['.$typo3_mode.'][]" value="'.$typo3_group['tx_igldapssoauth_dn'].'" '.($typo3_group['uid'] ? 'checked="checked" disabled="disabled"' : null).' /></td>' .
											'</tr>';

					}

					$this->content[] = '</table><br />';

					$this->content[] = '<input type="hidden" name="import[action]" value="update" />';
					$this->content[] = '<input type="submit" value="'.$this->lang->getLL('import_groups_form_submit_value').'" onclick="this.form.elements[\'import[action]\'].value=\'update\';" />';

					$this->content[] = '</fieldset>';

					$this->content[] = '</form><br />';

				} else {

					$this->content[] = '<h3>'.$this->lang->getLL('import_groups_'.$typo3_mode.'_no_groups_found').'</h3>';
					//$this->content[] = '<hr />';
					//$this->content[] = t3lib_div::view_array(array());

				}

			}

			tx_igldapssoauth_ldap::disconnect();

		}

	}
	function setParentGroup($parentsLDAPGroups,$feildParent,$childUid,$typo3_group_pid,$typo3_mode){
		foreach($parentsLDAPGroups as $parentDn){
		    $typo3ParentGroup=tx_igldapssoauth_typo3_group::select ($typo3_mode.'_groups',  false, $typo3_group_pid, '', $parentDn);

		    if(is_array($typo3ParentGroup[0])){
		    	if(!empty($typo3ParentGroup[0]['subgroup'])){
		    		$subGroupList = t3lib_div::trimExplode(',',$typo3ParentGroup[0]['subgroup']);
		    	}
		    	//if(!is_array($subGroupList)||!in_array($childUid,$subGroupList)){
		    		$subGroupList[]=$childUid;
		    		$subGroupList = array_unique($subGroupList);
		    		$typo3ParentGroup[0]['subgroup'] = implode(',',$subGroupList);
		    		tx_igldapssoauth_typo3_group::update($typo3_mode.'_groups',$typo3ParentGroup[0]);
		    	//}
		    }else{
		    	
		    	if ($ldap_groups = tx_igldapssoauth_ldap::search($this->config[$typo3_mode]['groups']['basedn'],'(&'.tx_igldapssoauth_config::replace_filter_markers($this->config[$typo3_mode]['groups']['filter']).'&(distinguishedName='.$parentDn.'))', tx_igldapssoauth_config::get_ldap_attributes($this->config[$typo3_mode]['groups']['mapping']))) {
		    		if(is_array($ldap_groups)){
		    			$typo3_group_pid = tx_igldapssoauth_config::get_pid($this->config[$typo3_mode]['groups']['mapping']);

						$typo3_groups = tx_igldapssoauth_auth::get_typo3_groups($ldap_groups, $this->config[$typo3_mode]['groups']['mapping'], $typo3_mode.'_groups', $typo3_group_pid);
	
						unset($ldap_groups['count']);

						foreach ($ldap_groups as $index => $ldap_group) {
							$typo3_group = tx_igldapssoauth_auth::merge($ldap_group, $typo3_groups[$index], $this->config[$typo3_mode]['groups']['mapping']);
							unset($typo3_group['parentGroup']);
							$typo3_group['subgroup'] = $childUid;
							$typo3_group = tx_igldapssoauth_typo3_group::insert($typo3_mode.'_groups', $typo3_group);
							$typo3_group = $typo3_group[0];
	
							if(is_array($ldap_group[$feildParent])){
								unset($ldap_group[$feildParent]['count']);
								if(is_array($ldap_group[$feildParent])){
									$this->setParentGroup($ldap_group[$feildParent],$feildParent,$typo3_group['uid'],$typo3_group_pid,$typo3_mode);
								}

							}
						}
		    		}
		    	}
		    }
		}
	}
	/**
	 * Prints out the module HTML
	 *
	 * @return	string	HTML content.
	 */
	function printContent()	{

		echo implode(chr(10), $this->content);

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/mod1/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_igldapssoauth_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>