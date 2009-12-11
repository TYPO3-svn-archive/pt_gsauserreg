<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Wolfgang Zenker <t3extensions@punkt.de>
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
 * Plugin 'Postal Address' for the 'pt_gsauserreg' extension.
 *
 * $Id: class.tx_ptgsauserreg_pi4.php,v 1.29 2008/06/26 14:18:45 ry42 Exp $
 *
 * @author  Wolfgang Zenker <t3extensions@punkt.de>
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_formTemplateHandler.php'; // library class with form handling methods
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_sessionStorageAdapter.php'; // storage adapter for TYPO3 _browser_ sessions
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_msgBox.php'; // msgBoxes
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_gsanschCollection.php';
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_gsansch.php';
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_user.php';
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_countrySpecifics.php';

#$trace     = 1; // (int) trace options @see tx_pttools_debug::trace() [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]



/**
 * Provides a frontend plugin displaying and entering 'Postal Addresses'
 *
 * @author	Wolfgang Zenker <t3extensions@punkt.de>
 * @package	TYPO3
 * @subpackage	tx_ptgsauserreg
 */
class tx_ptgsauserreg_pi4 extends tslib_pibase {

    /**
     * Constants
     */
    const SESSION_KEY_NAME = 'pt_gsauserregAnsch'; // (string) session key name to store gsansch object in session
    
    /**
     * Properties
     */

	var $prefixId = 'tx_ptgsauserreg_pi4';		// Same as class name
	var $scriptRelPath = 'pi4/class.tx_ptgsauserreg_pi4.php';	// Path to this script relative to the extension dir.
	var $extKey = 'pt_gsauserreg';	// The extension key.
    
    protected $templateFile;
	protected $formHandler;

	protected $formlist = array(
		'SelectionForm',
		'AddressForm',
		'ConfirmForm',
		'DisplayForm',
	);

	protected $formdesc = array(

		// form for address selection
		'SelectionForm' => array(

			'statictexts' => array(
				'selectintro',
			),
			'itemsselect' => array(
				'selectedId' => array(true, false, 1, false),
			),
			'itemsbutton' => array(
				'choose' => array(),
				'submit' => array(),
				'new' => array(),
				'delete' => array(),
			),
		),

		// form for user address data
		'AddressForm' => array(

			'statictexts' => array(
			),
			'sections' => array(
				'name',
				'snailmail',
				'media',
				'label'
			),
			'itemshidden' => array(
			),
			'itemstext' => array(
				'company' => array(false, 'Text', 40, 60),
				'firstname' => array(false, 'Text', 40, 60),
				'lastname' => array(false, 'Text', 40, 60),
			    #'streetAndNo' => array(true, 'Text', 40, 40),
				'streetAndNo' => array(true, 'Text', 40, 25), // ry44, changed for Klickbilderbox!
				'addrSupplement' => array(false, 'Text', 40, 40),
				'zip' => array(true, 'Text', 8, 8),
				'city' => array(true, 'Text', 40, 40),
				'state' => array(false, 'Text', 30, 30),
				'phone1' => array(false, 'Tel', 20, 40),
				'mobile1' => array(false, 'Tel', 20, 40),
				'fax1' => array(false, 'Tel', 20, 40),
				'email1' => array(false, 'Email', 40, 60),
			),
			'itemsselect' => array(
                #'country' => array(true, false, 1, false),
                'country' => array(true, false, 1, true), #changed by ry42
			),
			'itemscombo' => array(
				'salutation' => array(false, 'Text', true, 20, 40),
				'title' => array(false, 'Text', true, 20, 40),
			),
			'itemscheckbox' => array(
			),
			'itemsbutton' => array(
				'back' => array(),
				'submit' => array(),
			),
		),

		// form for address data confirmation
		'ConfirmForm' => array(

			'statictexts' => array(
			),
			'sections' => array(
				'label'
			),
			'itemshidden' => array(
			),
			'itemstext' => array(
				'post1' => array(false, 'Text', 40, 40),
				'post2' => array(false, 'Text', 40, 40),
				'post3' => array(false, 'Text', 40, 40),
				'post4' => array(false, 'Text', 40, 40),
				'post5' => array(false, 'Text', 40, 40),
				'post6' => array(false, 'Text', 40, 40),
				'post7' => array(false, 'Text', 40, 40),
			),
			'itemsselect' => array(
			),
			'itemscombo' => array(
			),
			'itemscheckbox' => array(
			),
			'itemsbutton' => array(
				'back' => array(),
				'submit' => array(),
			),
		),

		// form for address data confirmation
		'DisplayForm' => array(

			'sections' => array(
				'label'
			),
			'itemshidden' => array(
			),
			'itemstext' => array(
				'post1' => array(false, 'Text', 40, 40),
				'post2' => array(false, 'Text', 40, 40),
				'post3' => array(false, 'Text', 40, 40),
				'post4' => array(false, 'Text', 40, 40),
				'post5' => array(false, 'Text', 40, 40),
				'post6' => array(false, 'Text', 40, 40),
				'post7' => array(false, 'Text', 40, 40),
			),
			'itemsselect' => array(
			),
			'itemscombo' => array(
			),
			'itemscheckbox' => array(
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
	 * @param	string	$content: The PlugIn content
	 * @param	array	$conf: The PlugIn configuration
	 * @return	string  The content that is displayed on the website
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj=1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
	
		// HOOK: allow multiple hooks to manipulate formdesc
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['main_formdescHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['main_formdescHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$this->formdesc = $hookObj->main_formdescHook($this, $this->formdesc);
			}
		}

		/* get template and instantiate form handler */
		trace($conf);
		$templateFilePath = $this->conf['templateFile'];
		$this->templateFile = $this->cObj->fileResource($templateFilePath);
		$this->formHandler = new tx_pttools_formTemplateHandler($this, $this->formdesc);
		// if no user is logged in, complain; otherwise load user
        if ($GLOBALS['TSFE']->loginUser == 1) {
            trace('Loading User from DB');
			$user = new tx_ptgsauserreg_user(intval($GLOBALS['TSFE']->fe_user->user['uid']));
			$gsauid = $user->get_gsauid();
		}
		else {
			return $this->pi_wrapInBaseClass($this->pi_getLL('msg_no_user_logged_in', '[msg_no_user_logged_in]'));
		}
		$anschCollection
			= new tx_ptgsauserreg_gsanschCollection($gsauid, $user->get_defShipAddr(), $user->get_defBillAddr());
		trace($anschCollection);
trace($anschCollection->getDefShippingAddress());
		$myObj = $anschCollection;
        // try to get gsansch object from session
		trace('Looking for Ansch in Session');
		$ansch = tx_pttools_sessionStorageAdapter::getInstance()->read(self::SESSION_KEY_NAME);
		if (!(is_object($ansch) && ($ansch instanceof tx_ptgsauserreg_gsansch))) { 
			trace('no suitable object found');
			unset($ansch);
		}
		else {
			trace($ansch);
		}
		$nextform = $this->formlist[0];
		if (isset($this->piVars['action'])) {
			switch ($this->piVars['action']) {
				case 'choose':
					$nextform = 'SelectionForm';
					break;
				case 'new':
					$nextform = 'AddressForm';
					$anschCollection->set_selectedId(0);
					$myObj = $anschCollection->getSelectedItem();
					$myObj->set_gsauid($gsauid);
					trace('store new empty object in session');
					tx_pttools_sessionStorageAdapter::getInstance()->store(self::SESSION_KEY_NAME, $myObj);
					break;
				default:
					break;
			}
		}
		$checkmsg = '';
		// check normal form submits
		foreach ($this->formlist as $formname) {
			if (isset($this->piVars['submit_'.$formname])) {
				trace('found submit from '.$formname);
				// process and verify form input
				$processor = 'process'.$formname;
				if ($formname != 'SelectionForm') {
					$myObj = $ansch;
				}
				$checkmsg = $this->$processor($myObj);
				if (! $checkmsg) {
					// no problem with form, postprocessing
					switch ($formname) {
						case 'SelectionForm':
							trace($anschCollection->get_selectedId());
							$myObj = $anschCollection->getSelectedItem();
							$myObj->set_gsauid($gsauid);	// set gsauid in case its an empty object
							// store gsansch in session
							trace('Storing Ansch IN SESSION');
							tx_pttools_sessionStorageAdapter::getInstance()->store(self::SESSION_KEY_NAME, $myObj);
							if ($myObj->isBaseAdress()) {
								$checkmsg = $this->pi_getLL('msg_no_base_modify', '[msg_no_base_modify]');
								$msgBox = new tx_pttools_msgBox('info', $checkmsg);
					 			$checkmsg = $msgBox->__toString();
								$nextform = 'DisplayForm';
							}
							else {
								$nextform = 'AddressForm';
							}
							break;

						case 'AddressForm':
							// store gsansch in session
							$ansch->rewritePostFields();
							trace('Storing Ansch IN SESSION');
							tx_pttools_sessionStorageAdapter::getInstance()->store(self::SESSION_KEY_NAME, $myObj);
							$nextform = 'ConfirmForm';
							break;

						case 'ConfirmForm':
							// store gsansch in DB and remove from session
							$myObj->storeSelf();
							tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_NAME);
							$anschCollection->set_selectedId($myObj->get_uid());
							unset($nextform);
							break;

						case 'DisplayForm':
							// clone gsansch as base for new address
							$myObj = clone $myObj;
							$myObj->set_uid(0);
							trace('Storing clone IN SESSION');
							tx_pttools_sessionStorageAdapter::getInstance()->store(self::SESSION_KEY_NAME, $myObj);
							$nextform = 'AddressForm';
							break;

						default:
							break;
					}
				}
				else {
					$nextform = $formname;
				}
				break;
			}
		}
		// special submit cases
		if (isset($this->piVars['back_ConfirmForm'])) {
			trace('found back button from ConfirmForm');
			$nextform = 'AddressForm';
			$myObj = $ansch;
		}
		if (isset($this->piVars['back_DisplayForm'])
			|| isset($this->piVars['back_AddressForm'])) {
			trace('found back button from DisplayForm');
			$nextform = 'SelectionForm';
			$myObj = $anschCollection;
		}
		if (isset($this->piVars['choose_SelectionForm'])) {
			trace('found choose button from SelectionForm');
			$checkmsg = $this->processSelectionForm($anschCollection);
			if ($checkmsg == '') {
				unset($nextform);
			}
			trace($anschCollection->get_selectedId());
		}
		if (isset($this->piVars['new_SelectionForm'])) {
			trace('found new button from SelectionForm');
			$nextform = 'AddressForm';
			$anschCollection->set_selectedId(0);
			$myObj = $anschCollection->getSelectedItem();
			$myObj->set_gsauid($gsauid);
			trace('store new empty object in session');
			tx_pttools_sessionStorageAdapter::getInstance()->store(self::SESSION_KEY_NAME, $myObj);
		}
		if (isset($this->piVars['delete_SelectionForm'])) {
			trace('found delete button from SelectionForm');
			$nextform = 'SelectionForm';
			$myObj = $anschCollection;
			$checkmsg = $this->processSelectionForm($anschCollection);
			if ($checkmsg == '') {
				$ansch = $myObj->getSelectedItem();
				if ($ansch->isBaseAdress()) {
					$checkmsg = $this->pi_getLL('msg_no_base_del', '[msg_no_base_del]');
					$msgBox = new tx_pttools_msgBox('info', $checkmsg);
					 $checkmsg = $msgBox->__toString();
				}
				else {
					$delId = $ansch->get_uid();
					$updateUser = false;
					if ($user->get_defShipAddr() && ($user->get_defShipAddr() == $delId)) {
						$user->set_defShipAddr(0);
						$updateUser = true;
					}
					if ($user->get_defBillAddr() && ($user->get_defBillAddr() == $delId)) {
						$user->set_defBillAddr(0);
						$updateUser = true;
					}
					if ($updateUser) {
						$user->storeSelf();
					}
					$ansch->set_deprecated(true);
					$ansch->storeSelf();
				}
			}
		}
		if ($nextform) {
			// display next form
			$processor = 'display'.$nextform;
			$content = $checkmsg.$this->$processor($myObj);
		}
		else {
			// redirect to backURL or next pid
			$backLink = $this->piVars['backURL'];
			if ($backLink == '') {
				$backLink = $this->pi_getPageLink($this->conf['nextPage']);
			}
			if ($backLink != '') {
				if (isset($this->piVars['return_pivar_name'])) {
					if (! strpos($backLink, '?')) {
						$backLink .= '?';
					}
					$backLink .= '&'.$this->piVars['return_pivar_name'].'='.$anschCollection->get_selectedId();
				}
				tx_pttools_div::localRedirect($backLink);
				$content = 'redirect failed';
			}
			else
			{
				$content = $this->pi_getLL('msg_no_next_page', '[msg_no_next_page]');
			}
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * display address selection form
     * @param   tx_ptgsauserreg_gsanschCollection Collection of Postal Addresses 
     * @return  string      HTML plugin content for output on the page  
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function displaySelectionForm($anschCollection)	{
        trace('[CMD] '.__METHOD__);

		$formname = 'SelectionForm';
        $tmplForm = $this->cObj->getSubpart($this->templateFile, '###'.strtoupper($formname).'###');

		$choicesArray = array();
        $choicesArray['itemsselect']['selectedId'] = $anschCollection->getAddressSelectionArray();
		$backURL = $this->piVars['backURL'];
		if ($backURL == '') {
			$backURL = t3lib_div::_GP('backURL');
		}
		$return_pivar_name = $this->piVars['return_pivar_name'];
		if ($return_pivar_name == '') {
			$return_pivar_name = t3lib_div::_GP('return_pivar_name');
		}
		$disableArray = array();
		$hideArray = array();
		if ($return_pivar_name == '') {
			$hideArray[] = 'choose';
		}
		if ($anschCollection->count() <= 1) {
			$hideArray[] = 'delete';
		}

		$formMarkerArray = $this->formHandler->prepareConfSubst($formname, $anschCollection, $choicesArray, $disableArray, $hideArray);

		// try to get backURL from form or GPVARS
		$backURL = tx_pttools_div::htmlOutput($backURL);
		$formMarkerArray['###BACKURL###'] = $backURL;
		$return_pivar_name = tx_pttools_div::htmlOutput($return_pivar_name);
		$formMarkerArray['###RETURNPIVARNAME###'] = $return_pivar_name;

		// HOOK: allow multiple hooks to manipulate formMarkerArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['displaySelectionFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['displaySelectionFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$formMarkerArray = $hookObj->displaySelectionFormHook($this, $formMarkerArray);
			}
		}

        return $this->cObj->substituteMarkerArray($tmplForm, $formMarkerArray);
	}


	/**
	 * display address data form
     * @param   object      Postal Address 
     * @return  string      HTML plugin content for output on the page  
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function displayAddressForm($ansch)	{
        trace('[CMD] '.__METHOD__);

		$formname = 'AddressForm';
        $tmplForm = $this->cObj->getSubpart($this->templateFile, '###'.strtoupper($formname).'###');

		$choicesArray = array();
        $choicesArray['itemsselect']['country'] = tx_ptgsauserreg_countrySpecifics::getCountryList(true);
		$disableArray = array(
			'post1',
			'post2',
			'post3',
			'post4',
			'post5',
			'post6',
			'post7',
		);

		$formMarkerArray = $this->formHandler->prepareConfSubst($formname, $ansch, $choicesArray, $disableArray);

		// try to get backURL from form or GPVARS
		$backURL = $this->piVars['backURL'];
		if ($backURL == '') {
			$backURL = t3lib_div::_GP('backURL');
		}
		$backURL = tx_pttools_div::htmlOutput($backURL);
		$formMarkerArray['###BACKURL###'] = $backURL;
		$return_pivar_name = $this->piVars['return_pivar_name'];
		if ($return_pivar_name == '') {
			$return_pivar_name = t3lib_div::_GP('return_pivar_name');
		}
		$return_pivar_name = tx_pttools_div::htmlOutput($return_pivar_name);
		$formMarkerArray['###RETURNPIVARNAME###'] = $return_pivar_name;

		// HOOK: allow multiple hooks to manipulate formMarkerArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['displayAddressFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['displayAddressFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$formMarkerArray = $hookObj->displayAddressFormHook($this, $formMarkerArray);
			}
		}

        return $this->cObj->substituteMarkerArray($tmplForm, $formMarkerArray);
	}


	/**
	 * display address confirmation form
     * @param   object      Postal Address 
     * @return  string      HTML plugin content for output on the page  
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function displayConfirmForm($ansch)	{
        trace('[CMD] '.__METHOD__);

		$formname = 'ConfirmForm';
        $tmplForm = $this->cObj->getSubpart($this->templateFile, '###'.strtoupper($formname).'###');

		$formMarkerArray = $this->formHandler->prepareDisplaySubst($formname, $ansch, $choicesArray);

		// try to get backURL from form or GPVARS
		$backURL = $this->piVars['backURL'];
		if ($backURL == '') {
			$backURL = t3lib_div::_GP('backURL');
		}
		$backURL = tx_pttools_div::htmlOutput($backURL);
		$formMarkerArray['###BACKURL###'] = $backURL;
		$return_pivar_name = $this->piVars['return_pivar_name'];
		if ($return_pivar_name == '') {
			$return_pivar_name = t3lib_div::_GP('return_pivar_name');
		}
		$return_pivar_name = tx_pttools_div::htmlOutput($return_pivar_name);
		$formMarkerArray['###RETURNPIVARNAME###'] = $return_pivar_name;

		// HOOK: allow multiple hooks to manipulate formMarkerArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['displayConfirmFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['displayConfirmFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$formMarkerArray = $hookObj->displayConfirmFormHook($this, $formMarkerArray);
			}
		}

        return $this->cObj->substituteMarkerArray($tmplForm, $formMarkerArray);
	}


	/**
	 * display address display form 
     * @param   object      Postal Address 
     * @return  string      HTML plugin content for output on the page  
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function displayDisplayForm($ansch)	{
        trace('[CMD] '.__METHOD__);

		$formname = 'DisplayForm';
        $tmplForm = $this->cObj->getSubpart($this->templateFile, '###'.strtoupper($formname).'###');

		$formMarkerArray = $this->formHandler->prepareDisplaySubst($formname, $ansch, $choicesArray);

		// try to get backURL from form or GPVARS
		$backURL = $this->piVars['backURL'];
		if ($backURL == '') {
			$backURL = t3lib_div::_GP('backURL');
		}
		$backURL = tx_pttools_div::htmlOutput($backURL);
		$formMarkerArray['###BACKURL###'] = $backURL;
		$return_pivar_name = $this->piVars['return_pivar_name'];
		if ($return_pivar_name == '') {
			$return_pivar_name = t3lib_div::_GP('return_pivar_name');
		}
		$return_pivar_name = tx_pttools_div::htmlOutput($return_pivar_name);
		$formMarkerArray['###RETURNPIVARNAME###'] = $return_pivar_name;

		// HOOK: allow multiple hooks to manipulate formMarkerArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['displayDisplayFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['displayDisplayFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$formMarkerArray = $hookObj->displayDisplayFormHook($this, $formMarkerArray);
			}
		}

        return $this->cObj->substituteMarkerArray($tmplForm, $formMarkerArray);
	}


	/**
	 * process address selection form
     * @param   tx_ptgsauserreg_gsanschCollection Collection of Postal Addresses 
     * @return  string  empty if ok, else HTML code for MessageBox 
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function processSelectionForm($anschCollection)	{
        trace('[CMD] '.__METHOD__);

		$formname = 'SelectionForm';
		$choicesArray = array();
        $choicesArray['itemsselect']['selectedId'] = $anschCollection->getAddressSelectionArray();
		$disableArray = array();
		$hideArray = array();
		if ($return_pivar_name == '') {
			$hideArray[] = 'choose';
		}
		if ($anschCollection->count() <= 1) {
			$hideArray[] = 'delete';
		}
		$failArray = $this->formHandler->fillFormIntoObject($formname, $anschCollection, $choicesArray, $disableArray, $hideArray);
		$msgArray = array();
		foreach ($failArray as $item) {
			$msgArray[] = $item.' failed';
		}

		// HOOK: allow multiple hooks to evaluate piVars and manipulate msgArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['processSelectionFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['processSelectionFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$msgArray = $hookObj->processSelectionFormHook($this, $msgArray, $this->piVars);
			}
		}

		return $this->formHandler->checkObjectInForm($formname, $anschCollection, $msgArray, $hideArray);
	}


	/**
	 * process address data form
     * @param   object  Postal Address 
     * @return  string  empty if ok, else HTML code for MessageBox 
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function processAddressForm($ansch)	{
        trace('[CMD] '.__METHOD__);

		$formname = 'AddressForm';
		$choicesArray = array();
        $choicesArray['itemsselect']['country'] = tx_ptgsauserreg_countrySpecifics::getCountryList(true);
		$disableArray = array(
			'post1',
			'post2',
			'post3',
			'post4',
			'post5',
			'post6',
			'post7',
		);
		$failArray = $this->formHandler->fillFormIntoObject($formname, $ansch, $choicesArray, $disableArray);
		$msgArray = array();
		if (($ansch->get_company() == '') && ($ansch->get_lastname() == '')) {
			$msgArray[] = $this->pi_getLL('msg_comp_or_name', '[msg_comp_or_name]');
		}
		foreach ($failArray as $item) {
			$msgArray[] = $item.' failed';
		}

		// HOOK: allow multiple hooks to evaluate piVars and manipulate msgArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['processAddressFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['processAddressFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$msgArray = $hookObj->processAddressFormHook($this, $msgArray, $this->piVars);
			}
		}

		return $this->formHandler->checkObjectInForm($formname, $ansch, $msgArray);
	}

	/**
	 * process address confirmation form
     * @param   object  Postal Address 
     * @return  string  empty because nothing has to be done 
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function processConfirmForm($ansch)	{
        trace('[CMD] '.__METHOD__);

		$formname = 'ConfirmForm';
		// nothing to do because only display fields
		return '';
	}

	/**
	 * process address display form
     * @param   object  Postal Address 
     * @return  string  empty if ok, else HTML code for MessageBox 
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function processDisplayForm($ansch)	{
        trace('[CMD] '.__METHOD__);

		$formname = 'DisplayForm';
		$disableArray = array(
			'post1',
			'post2',
			'post3',
			'post4',
			'post5',
			'post6',
			'post7',
		);
		$failArray = $this->formHandler->fillFormIntoObject($formname, $ansch, $disableArray);
		$msgArray = array();
		foreach ($failArray as $item) {
			$msgArray[] = $item.' failed';
		}

		// HOOK: allow multiple hooks to evaluate piVars and manipulate msgArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['processDisplayFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi4_hooks']['processDisplayFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$msgArray = $hookObj->processDisplayFormHook($this, $msgArray, $this->piVars);
			}
		}

		return $this->formHandler->checkObjectInForm($formname, $ansch, $msgArray);
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/pi4/class.tx_ptgsauserreg_pi4.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/pi4/class.tx_ptgsauserreg_pi4.php']);
}

?>
