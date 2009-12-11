<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Wolfgang Zenker (t3extensions@punkt.de)
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
 * Plugin 'User Data' for the 'pt_gsauserreg' extension.
 *
 * $Id: class.tx_ptgsauserreg_pi2.php,v 1.58 2009/11/20 10:50:35 ry25 Exp $
 *
 * @author	Wolfgang Zenker <t3extensions@punkt.de>
 */


require_once(PATH_tslib.'class.tslib_pibase.php');

require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_formTemplateHandler.php'; // library class with form handling methods
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_sessionStorageAdapter.php'; // storage adapter for TYPO3 _browser_ sessions
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_msgBox.php'; // class for message boxes
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_customer.php';
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_user.php';
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_userCollection.php';
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_countrySpecifics.php';

#$trace     = 1; // (int) trace options @see tx_pttools_debug::trace() [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]



/**
 * Provides a frontend plugin displaying and entering User Data  
 *
 * @author      Wolfgang Zenker <zenker@punkt.de>
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_pi2 extends tslib_pibase {

    /**
     * Constants
     */
    const SESSION_KEY_NAME = 'pt_gsauserregCustomer'; // (string) session key name to store customer in session
    const SESSION_KEY_ACTPAGE = 'pt_gsauserreg_actpage'; // (string) session key name to store active page in session
    const SESSION_KEY_BACKURL = 'pt_gsauserreg_backurl'; // (string) session key name to store back url in session
    const SESSION_KEY_USER = 'pt_gsauserregUser'; // (string) session key name to store user object in session

	const MIN_PWLEN = 6;	// Minimum password length
    
    /**
     * Properties
     */

	var $prefixId = 'tx_ptgsauserreg_pi2';		// Same as class name
	var $scriptRelPath = 'pi2/class.tx_ptgsauserreg_pi2.php';	// Path to this script relative to the extension dir.
	var $extKey = 'pt_gsauserreg';	// The extension key.

	protected $formdesc = array(

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
				'new' => array(),
				'delete' => array(),
			),
		),

		// form for user address data
		'UserForm' => array(

			'statictexts' => array(
			),
			'sections' => array(
				'user',
				'snailmail',
				'media',
				'misc',
			),
			'itemshidden' => array(
			),
			'itemstext' => array(
				'company' => array(false, 'Text', 40, 60),
				'department' => array(false, 'Text', 40, 60),
				'firstname' => array(true, 'Text', 40, 60),
				'lastname' => array(true, 'Text', 40, 60),
				'streetAndNo' => array(true, 'Text', 40, 40),
				'zip' => array(true, 'Text', 10, 8),
				'city' => array(true, 'Text', 40, 40),
				'state' => array(true, 'Text', 40, 30),
				'phone1' => array(false, 'Tel', 20, 40),
				'fax1' => array(false, 'Tel', 20, 40),
				'mobile1' => array(false, 'Tel', 20, 40),
				'email1' => array(true, 'Email', 40, 60),
				'url' => array(false, 'Text', 40, 128),
				'username' => array(true, 'Text', 40, 50),
			),
			'itemspasswd' => array(
				'password' => array(true, 'Text', 40, 40),
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
				'isPrivileged' => array(false),
				'isRestricted' => array(false),
			),
			'itemsbutton' => array(
				'submit' => array(),
				'back' => array(),
			),
		),
	);

    protected $templateFile;
	protected $formHandler;
	protected $opsmode;
	protected $loginUser = NULL;
	
    /**
     * Main method of the User Data plugin: Prepares properties and instances, interprets submit buttons to control plugin behaviour
     *
     * @param   string      HTML-Content of the plugin to be displayed within the TYPO3 page
     * @param   array       Global configuration for this plugin (mostly done in Constant Editor/TS setup)
     * @return  string      HTML plugin content for output on the page (if not redirected before)
     * @author  Wolfgang Zenker <zenker@punkt.de>
     */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj=1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
	
		// HOOK: allow multiple hooks to manipulate formdesc
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi2_hooks']['main_formdescHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi2_hooks']['main_formdescHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$this->formdesc = $hookObj->main_formdescHook($this, $this->formdesc);
			}
		}

		/* check if we require phone numbers */
		if ($this->conf['requirePhone']) {
			$this->formdesc['UserForm']['itemstext']['phone1'][0] = true;
		}

		/* get template and instantiate form handler */
		trace($conf);
		$templateFilePath = $this->conf['templateFile'];
		$this->templateFile = $this->cObj->fileResource($templateFilePath);
		$this->formHandler = new tx_pttools_formTemplateHandler($this, $this->formdesc);

        /* load user & customer data */
		// if user is logged in, use that one
        if ($GLOBALS['TSFE']->loginUser == 1) {
            trace('Loading UserCollection from DB');
			$userCollection = new tx_ptgsauserreg_userCollection($GLOBALS['TSFE']->fe_user->user['tx_ptgsauserreg_gsa_adresse_id']);
			$userCollection->set_selectedId($GLOBALS['TSFE']->fe_user->user['uid']);
			$user = $userCollection->getSelectedItem();
			$gsauid = $user->get_gsauid();
            trace('Loading Customer from DB');
			$customer = new tx_ptgsauserreg_customer($gsauid);
			$this->opsmode = 'edit';
			$this->loginUser = $user;
			if ($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['miniCustUser']) {
				// always update user with current customer address
				$this->loadAddressFromCustomer($user, $customer);
				$user->storeSelf();
				$nextform = 'UserForm';
			} else {
				$nextform = $user->get_isPrivileged() ? 'SelectForm' : 'UserForm';
			}
		}
		else {
			$gsauid = 0;	// no customer known yet
        	// try to get customer object from session
            trace('Looking for Customer in Session');
            $customer = tx_pttools_sessionStorageAdapter::getInstance()->read(self::SESSION_KEY_NAME);
            // complain if session customer instance is invalid
            if (!(is_object($customer) && ($customer instanceof tx_ptgsauserreg_customer))) { 
				$content = $this->pi_getLL('msg_nocust', '[msg_nocust]');
				return $this->pi_wrapInBaseClass($content);
			}
			$this->opsmode = 'create';
			// Create new user from customer data
			$user = $this->createNewUserObj(0);
			$this->loadAddressFromCustomer($user, $customer);
			// the first user for new a customer is privileged by definition
			$user->set_isPrivileged(true);
			
			if ($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['newCustGroup']) {
				// we have a special group for new customers, add it
				$user->addGroupId(intval($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['newCustGroup']));
			}
			

			// TODO: username/password auswürfeln 
		    			
			$nextform = 'UserForm';
		}
		
		// check if we have a user stored in session
		$sess_user = tx_pttools_sessionStorageAdapter::getInstance()->read(self::SESSION_KEY_USER);
		if (is_object($sess_user)) {
			tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_USER);
			if ($sess_user->get_gsauid() != $gsauid) {
				trace('removed left-over user from session');
			}
			else {
				trace('Extracted user object from session');
				$user = $sess_user;
			}
		}
		trace($user);
		trace($customer);

		// check for form submits
		$checkmsg = '';
		switch (true) {
			// we check if the case labels evaluate to true

			case isset($this->piVars['submit_SelectForm']):
				trace('found submit from SelectForm');
				// verify and process form input
				$checkmsg = $this->processSelectForm($userCollection);
				if ($checkmsg == '') {
					// load selected user and proceed to user form
					$user = $userCollection->getSelectedItem();
					$nextform = 'UserForm';
				}
				else {
					$nextform = 'SelectForm';
				}
				break;

			case isset($this->piVars['new_SelectForm']):
				trace('found new button from SelectForm');
				// verify and process form input
				$checkmsg = $this->processSelectForm($userCollection);
				if ($checkmsg == '') {
					// create new user, clear name fields and proceed to user form
					$user = $this->createNewUserObj($gsauid);
					$this->loadAddressFromCustomer($user, $customer);
					$user->set_firstname('');
					$user->set_lastname('');
					$user->set_salutation('');
					$user->set_title('');
					$user->set_gsauid($gsauid);
					$nextform = 'UserForm';
				}
				else {
					$nextform = 'SelectForm';
				}
				break;

			case isset($this->piVars['delete_SelectForm']):
				trace('found delete from SelectForm');
				// verify and process form input
				$checkmsg = $this->processSelectForm($userCollection);
				if ($checkmsg == '') {
					// delete selected user
					$user = $userCollection->getSelectedItem();
					$user->deleteSelf();
            		tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_NAME);
					$content = $this->continueFromForm($user);
				}
				else {
					$nextform = 'SelectForm';
				}
				break;

            // handle user generic temporary User is set
			case $this->conf['temporaryUser']:
                if ($gsauid == 0) {
                    $this->generateUserPassword($user);
                	$customer->storeSelf();
	                $user->set_gsauid($customer->get_gsauid());
	                $user->storeSelf();
	                tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_NAME);
                }
	            $content = $this->continueFromForm($user);
                break;
			    
			    
            case isset($this->piVars['submit_UserForm']):
				trace('found submit from UserForm');
				// verify and process form input
				$checkmsg = $this->processUserForm($user);
				if ($checkmsg == '') {
					// store relevant data
					if ($gsauid == 0) {
						$customer->storeSelf();
						$gsauid = $customer->get_gsauid();
            			tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_NAME);
						$user->set_gsauid($gsauid);
					}
					trace($user);
					
					// inherit groups
					$this->inheritGroupsFromCurrentUser($user);
					
					$user->storeSelf();
					$content = $this->continueFromForm($user);
				}
				else {
					$nextform = 'UserForm';
				}
				break;

			case isset($this->piVars['back_UserForm']):
			case isset($this->piVars['back_SelectForm']):
				tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_NAME);
				tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_ACTPAGE);
				tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_USER);
				$backURL = $this->getsetBackURL();
				tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_BACKURL);
				if ($backURL) {
					tx_pttools_div::localRedirect($backURL);
				}
				unset($nextform);
				$content = $this->pi_getLL('msg_cancelled', '[msg_cancelled]');
				break;

			default:
				break;
		}

		if (! $content) {
			switch ($nextform) {
				case 'SelectForm':
					$content = $checkmsg . $this->displaySelectForm($userCollection);
					break;
				case 'UserForm':
           			tx_pttools_sessionStorageAdapter::getInstance()->store(self::SESSION_KEY_USER, $user);
					$content = $checkmsg . $this->displayUserForm($user);
					break;
				default:
					$content = 'kaputt!';
					break;
			}
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * create new initialised user object with ID of customer database record
     * @param   integer     ID of customer database record (GSA: ADRESSE.NUMMER) 
     * @return  object      new initialised user object   
     * @global  object      $GLOBALS['TSFE']->tmpl->setup 
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function createNewUserObj($gsauid)	{
		trace('[CMD] '.__METHOD__);

		$dataArray = array(
			'feuid' => 0,
			'pid' => intval($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['feusersSysfolderPid']),
			'isPrivileged' => false,
			'isRestricted' => false,
			'usergroup' => intval($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['defaultGroup']),
			'username' => '',
			'password' => '',
		);
		$user = new tx_ptgsauserreg_user(0, $dataArray);
		$user->set_gsauid($gsauid);

		return $user;
	}


	/**
	 * load address data from customer into user object
     * @param   object     user object 
     * @param   object     customer object 
     * @return  void           
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function loadAddressFromCustomer($user, $customer)	{
		trace('[CMD] '.__METHOD__);

		$addressDataArray = $customer->getAddressDataArray();
		foreach ($addressDataArray as $key => $value) {
			$setter = 'set_'.$key;
			$user->$setter($value);
		}
	}

	
	/**
	 * inherit additional groups from the current user, if defined in typoscript
	 * $inheritGroups = $creatorGroups - $allowedGroupsToInherit
	 * 
	 * @return void
	 * @author Daniel Lienert <lienert@punkt.de>
	 * @since 19.11.2009
	 */
	private function inheritGroupsFromCurrentUser(tx_ptgsauserreg_user $user) {
		
		$allowedGroupsToInherit = t3lib_div::trimExplode(',', $this->conf['inheritGroups'], true); 
		$creatorGroups = t3lib_div::trimExplode(',', $GLOBALS['TSFE']->fe_user->user['usergroup'], true);
		$inheritGroups = array_intersect($allowedGroupsToInherit, $creatorGroups);
		
		foreach($inheritGroups as $group) {
			$user->addGroupId($group);
		}		
	}
	
	

    /**
     * get available backURL and set it in session
     * @param   void      
     * @return  string   retrieved backURL   
     * @author  Wolfgang Zenker <zenker@punkt.de>
     */
	private function getsetBackURL()	{
		trace('[CMD] '.__METHOD__);

		// try to get backURL from form or GPVARS
		$backURL = $this->piVars['backURL'];
trace($backURL);
		if (! $backURL) {
			$backURL = t3lib_div::_GP('backURL');
trace($backURL);
		}

		// check or set session
		if ($backURL) {
			// store in session for stuff such as language change
           	tx_pttools_sessionStorageAdapter::getInstance()->store(self::SESSION_KEY_BACKURL, $backURL);
		}
		else {
			// try to fetch from session
			$backURL = tx_pttools_sessionStorageAdapter::getInstance()->read(self::SESSION_KEY_BACKURL);
trace($backURL);
		}

		trace($backURL);
		return $backURL;
	}


	/**
	 * display user selection form via formTemplateHandler Class (extension pt_tools)
     * @param  tx_ptgsauserreg_userCollection   
     * @return  string      HTML plugin content for output on the page  
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
		if ($userCollection->count() <= 1) {
			// don't allow do delete last user
			$hideArray[] = 'delete';
		}

		$formMarkerArray = $this->formHandler->prepareConfSubst($formname, $userCollection, $choicesArray, $disableArray, $hideArray, $relaxArray);

		$backURL = $this->getsetBackURL();
		$backURL = tx_pttools_div::htmlOutput($backURL);
		$formMarkerArray['###BACKURL###'] = $backURL;

		// HOOK: allow multiple hooks to manipulate formMarkerArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi2_hooks']['displaySelectFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi2_hooks']['displaySelectFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$formMarkerArray = $hookObj->displaySelectFormHook($this, $formMarkerArray);
			}
		}

        return $this->cObj->substituteMarkerArray($tmplForm, $formMarkerArray);
	}

	/**
	 * display user data form via formTemplateHandler Class (extension pt_tools)
     * @param   object      user data   
     * @return  string      HTML plugin content for output on the page  
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function displayUserForm($user)	{
		trace('[CMD] '.__METHOD__);

		$formname = 'UserForm';
        $tmplForm = $this->cObj->getSubpart($this->templateFile, '###'.strtoupper($formname).'###');

		$choicesArray = array();
        $choicesArray['itemsselect']['country'] = tx_ptgsauserreg_countrySpecifics::getCountryList();
		$disableArray = array();
		$hideArray = array();
		if ($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['miniCustUser']) {
			$hideArray[] = 'user';
			$hideArray[] = 'snailmail';
			$hideArray[] = 'media';
			$hideArray[] = 'company';
			$hideArray[] = 'department';
			$hideArray[] = 'firstname';
			$hideArray[] = 'lastname';
			$hideArray[] = 'streetAndNo';
			$hideArray[] = 'zip';
			$hideArray[] = 'city';
			$hideArray[] = 'state';
			$hideArray[] = 'phone1';
			$hideArray[] = 'fax1';
			$hideArray[] = 'mobile1';
			$hideArray[] = 'email1';
			$hideArray[] = 'url';
			$hideArray[] = 'salutation';
			$hideArray[] = 'title';
			$hideArray[] = 'country';
			$hideArray[] = 'isPrivileged';
			$hideArray[] = 'isRestricted';
		}
		else {
			switch ($this->opsmode) {
				case 'create':
					$hideArray[] = 'user';
					$hideArray[] = 'snailmail';
					$hideArray[] = 'media';
					$hideArray[] = 'company';
					$hideArray[] = 'department';
					$hideArray[] = 'firstname';
					$hideArray[] = 'lastname';
					$hideArray[] = 'streetAndNo';
					$hideArray[] = 'zip';
					$hideArray[] = 'city';
					$hideArray[] = 'state';
					$hideArray[] = 'phone1';
					$hideArray[] = 'fax1';
					$hideArray[] = 'mobile1';
					$hideArray[] = 'email1';
					$hideArray[] = 'url';
					$hideArray[] = 'salutation';
					$hideArray[] = 'title';
					$hideArray[] = 'country';
					$hideArray[] = 'isPrivileged';
					$hideArray[] = 'isRestricted';
					break;
	
				case 'edit':
					if (! $this->loginUser->get_isPrivileged()) {
						$hideArray[] = 'isPrivileged';
						$hideArray[] = 'isRestricted';
					}
					break;
			}
		}
		// disable username editing for existing users
		if ($user->get_feuid() != 0) {
			$disableArray[] = 'username';
		}

		// check if state is needed
		$relaxArray = array();
		$country = $user->get_country();
		if (! ($country && tx_ptgsauserreg_countrySpecifics::needsRegion($country))) {
			$relaxArray[] = 'state';
		}

		$formMarkerArray = $this->formHandler->prepareConfSubst($formname, $user, $choicesArray, $disableArray, $hideArray, $relaxArray);

		$backURL = $this->getsetBackURL();
		$backURL = tx_pttools_div::htmlOutput($backURL);
		$formMarkerArray['###BACKURL###'] = $backURL;

		// HOOK: allow multiple hooks to manipulate formMarkerArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi2_hooks']['displayUserFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi2_hooks']['displayUserFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$formMarkerArray = $hookObj->displayUserFormHook($this, $formMarkerArray);
			}
		}

		// Show newuser intro if new user
		$content = '';
		if ($this->loginUser == NULL) {
			$msgBoxObj = new tx_pttools_msgBox('info', $this->pi_getLL('st_newuser', '[st_newuser]'));
			$content .= $msgBoxObj->__toString();
		}
        $content .= $this->cObj->substituteMarkerArray($tmplForm, $formMarkerArray);
        return $content;
	}

	/**
	 * continue from user data form. This Method is called after handling User Data and redirects to the next page. 
     * @param   object      user data   
     * @return  string      HTML plugin content for output on the page  
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function continueFromForm($user)	{
		trace('[CMD] '.__METHOD__);

		if (($GLOBALS['TSFE']->loginUser != 1) && ($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['autoLogin'])) {
			// create user session (auto login)
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
		}

		// get backURL from form, GPVARS, hook, conf['nextPage'] or use '/'
		$backURL = $this->getsetBackURL();
		if (! $backURL) {
			$hookFunc = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['redirect_url'];
			if ($hookFunc) {
				$_params = array('redirect_url' => $redirect_url);
				$backURL = t3lib_div::callUserFunction($hookFunc, $_params, $this);
			}
		}
		if (! $backURL) {
			$backURL = $this->pi_getPageLink($this->conf['nextPage']);
		}
		if (! $backURL) {
			$backURL = '/';
		}
		tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_ACTPAGE);
		tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_BACKURL);

		// redirect to backURL
		tx_pttools_div::localRedirect($backURL);
		return 'redirect failed';
	}

	/**
	 * process user selection form 
     * @param  tx_ptgsauserreg_userCollection   
     * @return  string  empty if ok, else HTML code for MessageBox 
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
		if ($userCollection->count() <= 1) {
			// don't allow do delete last user
			$hideArray[] = 'delete';
		}

		$failArray = $this->formHandler->fillFormIntoObject($formname, $userCollection, $choicesArray, $disableArray, $hideArray);
		// check for failures
		$msgArray = array();
		foreach ($failArray as $item) {
			$msgArray[] = $item.' failed';
		}

		// HOOK: allow multiple hooks to evaluate piVars and manipulate msgArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi2_hooks']['processSelectFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi2_hooks']['processSelectFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$msgArray = $hookObj->processSelectFormHook($this, $msgArray, $this->piVars);
			}
		}

		return $this->formHandler->checkObjectInForm($formname, $userCollection, $msgArray, $hideArray, $relaxArray);
	}

	/**
	 * process user data form
     * @param   object  user data    
     * @return  string  empty if ok, else HTML code for MessageBox 
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function processUserForm($user)	{
		trace('[CMD] '.__METHOD__);

		$formname = 'UserForm';
		$choicesArray = array();
        $choicesArray['itemsselect']['country'] = tx_ptgsauserreg_countrySpecifics::getCountryList();

		$disableArray = array();
		$hideArray = array();
		if ($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['miniCustUser']) {
			$hideArray[] = 'user';
			$hideArray[] = 'snailmail';
			$hideArray[] = 'media';
			$hideArray[] = 'misc';
			$hideArray[] = 'company';
			$hideArray[] = 'department';
			$hideArray[] = 'firstname';
			$hideArray[] = 'lastname';
			$hideArray[] = 'streetAndNo';
			$hideArray[] = 'zip';
			$hideArray[] = 'city';
			$hideArray[] = 'state';
			$hideArray[] = 'phone1';
			$hideArray[] = 'fax1';
			$hideArray[] = 'mobile1';
			$hideArray[] = 'email1';
			$hideArray[] = 'url';
			$hideArray[] = 'salutation';
			$hideArray[] = 'title';
			$hideArray[] = 'country';
			$hideArray[] = 'isPrivileged';
			$hideArray[] = 'isRestricted';
		}
		else {
			switch ($this->opsmode) {
				case 'create':
					$hideArray[] = 'user';
					$hideArray[] = 'snailmail';
					$hideArray[] = 'media';
					$hideArray[] = 'misc';
					$hideArray[] = 'company';
					$hideArray[] = 'department';
					$hideArray[] = 'firstname';
					$hideArray[] = 'lastname';
					$hideArray[] = 'streetAndNo';
					$hideArray[] = 'zip';
					$hideArray[] = 'city';
					$hideArray[] = 'state';
					$hideArray[] = 'phone1';
					$hideArray[] = 'fax1';
					$hideArray[] = 'mobile1';
					$hideArray[] = 'email1';
					$hideArray[] = 'url';
					$hideArray[] = 'salutation';
					$hideArray[] = 'title';
					$hideArray[] = 'country';
					$hideArray[] = 'isPrivileged';
					$hideArray[] = 'isRestricted';
					break;
	
				case 'edit':
					if (! $this->loginUser->get_isPrivileged()) {
						$hideArray[] = 'isPrivileged';
						$hideArray[] = 'isRestricted';
					}
					break;
			}
		}
		// disable username editing for existing users
		if ($user->get_feuid() != 0) {
			$disableArray[] = 'username';
		}
		$relaxArray = array();

		$failArray = $this->formHandler->fillFormIntoObject($formname, $user, $choicesArray, $disableArray, $hideArray);
		// check for failures
		$msgArray = array();
		foreach ($failArray as $item) {
			if ($item == 'password') {
				$msgArray[] = $this->pi_getLL('msg_pwmismatch', '[msg_pwmismatch]');
			}
			else {
				$msgArray[] = $item.' failed';
			}
		}
		// check if state is needed
		$country = $user->get_country();
		if (! ($country && tx_ptgsauserreg_countrySpecifics::needsRegion($country))) {
			$relaxArray[] = 'state';
		}
		// check for duplicate usernames
		if ($user->get_username() && (! $user->usernameOk())) {
			$msgArray[] = $this->pi_getLL('msg_userexists', '[msg_userexists]');
		}
		// check for minimum password length
		if ($user->get_password() && (strlen($user->get_password()) < self::MIN_PWLEN)) {
			$msgArray[] = $this->pi_getLL('msg_pwshort', '[msg_pwshort]');
		}

		// HOOK: allow multiple hooks to evaluate piVars and manipulate msgArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi2_hooks']['processUserFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi2_hooks']['processUserFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$msgArray = $hookObj->processUserFormHook($this, $msgArray, $this->piVars);
			}
		}

		return $this->formHandler->checkObjectInForm($formname, $user, $msgArray, $hideArray, $relaxArray);
	}

    /**
     * generate username password for user
     * @param   tx_ptgsauserreg_user  user data    
     * @return  void 
     * @author  Dorit Rottner <rottner@punkt.de>
     */
    private function generateUserPassword(tx_ptgsauserreg_user $user) {
        trace('[CMD] '.__METHOD__);
        
        if (!$GLOBALS['TSFE']->loginUser != 1) {
            $userNameFound = true;
            while ($userNameFound == true) {
                $userName = $this->conf['usernamePrefix'].date("Ymdhms").'-'.rand().'#'.$GLOBALS['TSFE']->fe_user->id;
                $user->set_username($userName);
	            if ($user->usernameOk()) {
	            	$userNameFound = false;
	            }
	            
            }
        }
        $password = tx_pttools_div::createPassword();
        $user->set_password($password);
    }
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/pi2/class.tx_ptgsauserreg_pi2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/pi2/class.tx_ptgsauserreg_pi2.php']);
}

?>
