<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Wolfgang Zenker (zenker@punkt.de)
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
/**
 * Plugin 'Switch user' for the 'pt_gsauserreg' extension.
 *
 * $Id: class.tx_ptgsauserreg_pi5.php,v 1.11 2008/04/01 10:49:37 ry37 Exp $
 *
 * @author	Wolfgang Zenker <zenker@punkt.de>
 */


require_once(PATH_tslib.'class.tslib_pibase.php');

require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_formTemplateHandler.php'; // library class with form handling methods
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_msgBox.php'; // displaying information and messages in a HTML-Messagebox
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // miscellanious helper functions
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_adminFilter.php'; //user input on Admin Interface Search page
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_userCollection.php';

#$trace     = 1; // (int) trace options @see tx_pttools_debug::trace() [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]

/**
 * Provides a frontend plugin to switch to another user (only for admin purposes).
 *
 * @author  Wolfgang Zenker <t3extensions@punkt.de>
 * @package TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_pi5 extends tslib_pibase {

    /**
     * Constants
     */
    const SESSION_KEY_FILTER = 'pt_gsauserregFilter'; // (string) session key name to store filter object in session
	const MAX_SEARCHRESULTS = 100;		// max. nr. of search results allowed
    
    /**
     * Properties
     */

	var $prefixId = 'tx_ptgsauserreg_pi5';		// Same as class name
	var $scriptRelPath = 'pi5/class.tx_ptgsauserreg_pi5.php';	// Path to this script relative to the extension dir.
	var $extKey = 'pt_gsauserreg';	// The extension key.
	
    protected $templateFile;
	protected $formHandler;

	protected $formlist = array(
		'SearchForm',
	);

	protected $formdesc = array(

		// form for user search
		'SearchForm' => array(

			'itemstext' => array(
				'gsa_kundnr' => array(false, 'Digit', 10, 6),
				'username' => array(false, 'Text', 40, 50),
				'company' => array(false, 'Text', 40, 60),
				'firstname' => array(false, 'Text', 40, 60),
				'lastname' => array(false, 'Text', 40, 60),
				'streetAndNo' => array(false, 'Text', 40, 40),
				'zip' => array(false, 'Text', 10, 8),
				'city' => array(false, 'Text', 40, 40),
				'email1' => array(false, 'Text', 40, 60),
			),

			'itemscheckbox' => array(
				'exactMatch' => array(false),
			),

			'itemsbutton' => array(
				'submit' => array(),
				'reset' => array('reset'),
			),

		),

		// form for user selection
		'SelectForm' => array(

			'statictexts' => array(
			),
			'itemsselect' => array(
				'selectedId' => array(true, false, 1, false),
			),
			'itemsbutton' => array(
				'back' => array(),
				'submit' => array(),
			),
		),

	);

	/**
     * The main method of the PlugIn
     *
     * @param   string  The PlugIn content
     * @param   array   The PlugIn configuration
     * @return  string  The content that is displayed on the website
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj=1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
	
		// HOOK: allow multiple hooks to manipulate formdesc
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi5_hooks']['main_formdescHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi5_hooks']['main_formdescHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$this->formdesc = $hookObj->main_formdescHook($this, $this->formdesc);
			}
		}

		/* get template and instantiate form handler */
		trace($conf);
		$templateFilePath = $this->conf['templateFile'];
		$this->templateFile = $this->cObj->fileResource($templateFilePath);
		$this->formHandler = new tx_pttools_formTemplateHandler($this, $this->formdesc);

		// load filter object from session, if available; else create new
		$filter = tx_pttools_sessionStorageAdapter::getInstance()->read(self::SESSION_KEY_FILTER);
		if (is_object($filter)) {
			trace('loaded filter from session');
			trace($filter);
			tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_FILTER);
		}
		else {
			trace('create new filter object');
			$filter = new tx_ptgsauserreg_adminFilter();
		}

		// default form is SearchForm
		$nextform = 'SearchForm';
		$myObj = $filter;

		// check for form submits
		$checkmsg = '';
		switch (true) {
			// we check if the case labels evaluate to true

			case isset($this->piVars['submit_SearchForm']):
				trace('found submit from SearchForm');
				// verify and process form input
				$checkmsg = $this->processSearchForm($filter);
				if ($checkmsg == '') {
					// load search result and proceed to select form
					$userCollection = new tx_ptgsauserreg_userCollection();
					$checkmsg = $this->searchUsers($userCollection, $filter);
					if ($checkmsg != '') {
						// search failed, complain
						$msgBoxObj = new tx_pttools_msgBox('error', $checkmsg);
						$checkmsg = $msgBoxObj->__toString();
						$nextform = 'SearchForm';
						$myObj = $filter;
					}
					else {
						$nextform = 'SelectForm';
						$myObj = $userCollection;
						tx_pttools_sessionStorageAdapter::getInstance()->store(self::SESSION_KEY_FILTER, $filter);
						trace('storing filter in session');
						trace($filter);
					}
				}
				else {
					$nextform = 'SearchForm';
					$myObj = $filter;
				}
				break;

			case isset($this->piVars['submit_SelectForm']):
				trace('found submit from SelectForm');
				// verify and process form input
				$userCollection = new tx_ptgsauserreg_userCollection();
				$checkmsg = $this->searchUsers($userCollection, $filter);
				if ($checkmsg != '') {
					// should be impossible: we are using a formerly successful filter
					throw new tx_pttools_exception('filter failed', 3);
				}
				$checkmsg = $this->processSelectForm($userCollection);
				if ($checkmsg == '') {
					$user = $userCollection->getSelectedItem();
					trace($user);
					$this->switchUser($user);
				}
				throw new tx_pttools_exception('oops, I lost my identity!', 0);
				break;
				
			case isset($this->piVars['back_SelectForm']):
				trace('go back from SelectForm');
				$nextform = 'SearchForm';
				$myObj = $filter;
				break;
		}


		$method = 'display'.$nextform;
		$content = $checkmsg.$this->$method($myObj);
	
		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * search users according to filter criteria
     * @param   tx_ptgsauserreg_userCollection  
     * @param   object      user input on Search page       
     * @return  string      HTML plugin content for output on the page  
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function searchUsers($userCollection, $filter)	{
        trace('[CMD] '.__METHOD__);

		$filterlist = $filter->getDataArray();
		$searchlist = array();
		$exactMatch = false;
		$specified = false;
		$result = '';
		foreach ($filterlist as $key => $value) {
			if ($value == '') {
				// ignore empty search fields
				continue;
			}
			switch ($key) {
				case 'gsa_kundnr':
					// special case; find gsauid by gsa_kundnr
					break;

				case 'exactMatch':
					// switch match mode
					$exactMatch = $value;
					break;

				default:
					// copy field to searchlist
					$specified = true;
					$searchlist[$key] = $value;
			}
		}
		if (! $specified) {
			$result = $this->pi_getLL('msg_searchreq', '[msg_searchreq]');
		}
		else {
			$cnt = $userCollection->loadBySearchlist($searchlist, $exactMatch, self::MAX_SEARCHRESULTS);
			if ($cnt > self::MAX_SEARCHRESULTS) {
				// to many hits, complain
				$result = $this->pi_getLL('msg_toomany', '[msg_toomany]');
			}
			else if (! $cnt) {
				// nothing found, complain
				$result = $this->pi_getLL('msg_notfound', '[msg_notfound]');
			}
		}
		trace($result);
		return $result;
	}

	/**
	 * switch login to given user
     * @param   object  user Object  
     * @return  mixed   Error message, if redirect fails  
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function switchUser($user)	{
		trace('[CMD] '.__METHOD__);

		// logoff current user
		$GLOBALS['TSFE']->fe_user->logoff();

		// create user session for new user
		$feUser = array();
		$feUser['uid'] = $user->get_feuid();
		$feUser['username'] = $user->get_username();
		$feUser['password'] = $user->get_password();
		$feUser['tx_ptgsauserreg_gsa_adresse_id'] = $user->get_gsauid();
		$feUser['pid'] = intval($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['feusersSysfolderPid']);
		$GLOBALS['TSFE']->fe_user->createUserSession($feUser);

		// initialize TSFE sufficiently to access protected pages
		$GLOBALS['TSFE']->initFEuser();
		$GLOBALS['TSFE']->initUserGroups();
		$GLOBALS['TSFE']->setSysPageWhereClause();

		// get next Page from conf, use "/" if not defined
		$backURL = $this->pi_getPageLink($this->conf['nextPage']);
		if (! $backURL) {
			$backURL = '/';
		}

		// redirect to backURL
		tx_pttools_div::localRedirect($backURL);
		return 'redirect failed';
	}

	/**
	 * display user search form
     * @param   object      user input on Search page  
     * @return  string      HTML plugin content for output on the page  
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function displaySearchForm($filter)	{
        trace('[CMD] '.__METHOD__);

		$formname = 'SearchForm';
        $tmplForm = $this->cObj->getSubpart($this->templateFile, '###'.strtoupper($formname).'###');

		$choicesArray = array();
		$disableArray = array();
		$hideArray = array();

		$formMarkerArray = $this->formHandler->prepareConfSubst($formname, $filter, $choicesArray, $disableArray, $hideArray);

		// HOOK: allow multiple hooks to manipulate formMarkerArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi5_hooks']['displaySearchFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi5_hooks']['displaySearchFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$formMarkerArray = $hookObj->displaySearchFormHook($this, $formMarkerArray);
			}
		}

        return $this->cObj->substituteMarkerArray($tmplForm, $formMarkerArray);
	}

	/**
	 * display user selection form
     * @param   tx_ptgsauserreg_userCollection  
     * @return  string  HTML plugin content for output on the page  
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function displaySelectForm($userCollection)	{
		trace('[CMD] '.__METHOD__);

		$formname = 'SelectForm';
        $tmplForm = $this->cObj->getSubpart($this->templateFile, '###'.strtoupper($formname).'###');

		$choicesArray = array();
        $choicesArray['itemsselect']['selectedId'] = $userCollection->getUserSelectionArray();
		$disableArray = array();
		$hideArray = array();
		$relaxArray = array();

		$formMarkerArray = $this->formHandler->prepareConfSubst($formname, $userCollection, $choicesArray, $disableArray, $hideArray, $relaxArray);

		// HOOK: allow multiple hooks to manipulate formMarkerArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi5_hooks']['displaySelectFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi5_hooks']['displaySelectFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$formMarkerArray = $hookObj->displaySelectFormHook($this, $formMarkerArray);
			}
		}

        return $this->cObj->substituteMarkerArray($tmplForm, $formMarkerArray);
	}

	/**
	 * process user search form
     * @param   object      user input on Search page  
     * @return  string      empty if ok, else HTML code for MessageBox 
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function processSearchForm($filter)	{
		trace('[CMD] '.__METHOD__);

		$formname = 'SearchForm';
		$choicesArray = array();
		$disableArray = array();
		$hideArray = array();

		$failArray = $this->formHandler->fillFormIntoObject($formname, $filter, $choicesArray, $disableArray, $hideArray);
		// check for failures
		$msgArray = array();
		foreach ($failArray as $item) {
			$msgArray[] = $item.' failed';
		}

		// HOOK: allow multiple hooks to evaluate piVars and manipulate msgArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi5_hooks']['processSearchFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi5_hooks']['processSearchFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$msgArray = $hookObj->processSearchFormHook($this, $msgArray, $this->piVars);
			}
		}

		return $this->formHandler->checkObjectInForm($formname, $filter, $msgArray, $hideArray);
	}

	/**
	 * process user selection form
     * @param   tx_ptgsauserreg_userCollection  
     * @return  string      empty if ok, else HTML code for MessageBox 
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function processSelectForm($userCollection)	{
		trace('[CMD] '.__METHOD__);

		$formname = 'SelectForm';
		$choicesArray = array();
        $choicesArray['itemsselect']['selectedId'] = $userCollection->getUserSelectionArray();
		$disableArray = array();
		$hideArray = array();
		$relaxArray = array();

		$failArray = $this->formHandler->fillFormIntoObject($formname, $userCollection, $choicesArray, $disableArray, $hideArray);
		// check for failures
		$msgArray = array();
		foreach ($failArray as $item) {
			$msgArray[] = $item.' failed';
		}

		// HOOK: allow multiple hooks to evaluate piVars and manipulate msgArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi5_hooks']['processSelectFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi5_hooks']['processSelectFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$msgArray = $hookObj->processSelectFormHook($this, $msgArray, $this->piVars);
			}
		}

		return $this->formHandler->checkObjectInForm($formname, $userCollection, $msgArray, $hideArray, $relaxArray);
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/pi5/class.tx_ptgsauserreg_pi5.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/pi5/class.tx_ptgsauserreg_pi5.php']);
}

?>
