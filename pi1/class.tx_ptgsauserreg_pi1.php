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
 * Plugin 'Customer Data' for the 'pt_gsauserreg' extension.
 *
 * $Id: class.tx_ptgsauserreg_pi1.php,v 1.103 2009/11/24 13:25:45 ry25 Exp $
 *
 * @author	Wolfgang Zenker <t3extensions@punkt.de>
 */


require_once(PATH_tslib.'class.tslib_pibase.php');

require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // helper class, we use localRedirect
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_formTemplateHandler.php'; // library class with form handling methods
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_sessionStorageAdapter.php'; // storage adapter for TYPO3 _browser_ sessions
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_msgBox.php'; // class for message boxes
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_customer.php';
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_user.php';
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_countrySpecifics.php';

//$trace     = 1; // (int) trace options @see tx_pttools_debug::trace() [for local temporary debugging use only, please COMMENT OUT this line if finished with debugging!]



/**
 * Provides a frontend plugin displaying the Customer Data like address or bank data  
 *
 * @author      Wolfgang Zenker <zenker@punkt.de>
 * @since       2005-03-07
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_pi1 extends tslib_pibase {

    /**
     * Constants
     */
    const SESSION_KEY_NAME = 'pt_gsauserregCustomer'; // (string) session key name to store customer in session
    const SESSION_KEY_ACTPAGE = 'pt_gsauserreg_actpage'; // (string) session key name to store active page in session
    const SESSION_KEY_BACKURL = 'pt_gsauserreg_backurl'; // (string) session key name to store back url in session
    const SESSION_KEY_RETURNVAR = 'pt_gsauserreg_returnvar'; // (string) session key name to store name of return variable in session
    /**
     * Properties
     */

	var $prefixId = 'tx_ptgsauserreg_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_ptgsauserreg_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'pt_gsauserreg';	// The extension key.

    protected $templateFile;
	protected $formHandler;

	protected $formlist = array(
		'AddressForm',
		'PostmanuForm',
		'BankForm',
	);

	protected $formdesc = array(

		// form for customer address data
		'AddressForm' => array(

			'statictexts' => array(
			),
			'sections' => array(
				'customer',
				'snailmail',
				'streetaddr',
				'pobox',
				'media',
			),
			'itemshidden' => array(
			),
			'itemstext' => array(
				'company' => array(false, 'Text', 40, 60),
				'firstname' => array(true, 'Text', 40, 60),
				'lastname' => array(true, 'Text', 40, 60),
				'streetAndNo' => array(true, 'Text', 40, 40),
				'addrSupplement' => array(false, 'Text', 40, 60),
				'zip' => array(true, 'Text', 10, 8),
				'city' => array(true, 'Text', 40, 40),
				'state' => array(true, 'Text', 40, 30),
				'poBox' => array(false, 'Text', 10, 9),
				'poBoxZip' => array(false, 'Text', 10, 8),
            	// 'poBoxCity' => array(false, 'Text', 40, 40),
				'phone1' => array(false, 'Tel', 20, 40),
				'phone2' => array(false, 'Tel', 20, 40),
				'fax1' => array(false, 'Tel', 20, 40),
				'fax2' => array(false, 'Tel', 20, 40),
				'mobile1' => array(false, 'Tel', 20, 40),
				'mobile2' => array(false, 'Tel', 20, 40),
				'email1' => array(true, 'Email', 40, 60),
				'email2' => array(false, 'Email', 40, 60),
				'url' => array(false, 'Text', 40, 128),
				'lob' => array(false, 'Text', 20, 40),
				'euVatId' => array(false, 'Text', 20, 15),
			),
			'itemsselect' => array(
                'country' => array(true, false, 1, true),
            ),
			'itemscombo' => array(
				'salutation' => array(false, 'Text', true, 20, 40),
				'title' => array(false, 'Text', true, 20, 40),
			),
			'itemsradio' => array(
			),
			'itemssplitdate' => array(
				'birthdate' => array(false),
			),
			'itemsbutton' => array(
				'submit' => array(),
				'back' => array(),
			),
		),

		// form for customer postal data
		'PostmanuForm' => array(

			'statictexts' => array(
				'postintro',
			),
			'itemstext' => array(
				'post1' => array(false, 'Text', 60, 60),
				'post2' => array(false, 'Text', 60, 60),
				'post3' => array(false, 'Text', 60, 60),
				'post4' => array(false, 'Text', 60, 60),
				'post5' => array(false, 'Text', 60, 60),
				'post6' => array(false, 'Text', 60, 60),
				'post7' => array(false, 'Text', 60, 60),
			),
			'itemsbutton' => array(
				'submit' => array(),
				'rewrite' => array(),
				'back' => array(),
			),
		),

		// form for customer banking data
		'BankForm' => array(

			'sections' => array(
				'paymentchoice',
				'bankdata',
			),
			'statictexts' => array(
			),
			'itemstext' => array(
				'bankAccountHolder' => array(true, 'Text', 27, 27),
				'bankCode' => array(true, 'Digit', 10, 10),
				'bankName' => array(true, 'Text', 40, 40),
				'bankAccount' => array(true, 'Digit', 30, 30),
				'bankBIC' => array(true, 'Text', 11, 11),
				'bankIBAN' => array(true, 'Text', 34, 34),
			),
			'itemsradio' => array(
				'paymentMethod' => array(true),
			),
			'itemsbutton' => array(
				'submit' => array(),
				'back' => array(),
			),
		),
	);


    /**
     * Main method of the customer data plugin: Prepares properties and instances, interprets submit buttons to control plugin behaviour
     *
     * @param   string      HTML-Content of the plugin to be displayed within the TYPO3 page
     * @param   array       Global configuration for this plugin (mostly done in Constant Editor/TS setup)
     * @return  string      HTML plugin content for output on the page (if not redirected before)
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2005-03-07
     */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj=1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!
        trace ($this->piVars,0,'piVars');
        trace ($this->conf,0,'conf pt_gsauserreg_pi1');

		// HOOK: allow multiple hooks to manipulate formdesc
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi1_hooks']['main_formdescHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi1_hooks']['main_formdescHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$this->formdesc = $hookObj->main_formdescHook($this, $this->formdesc);
			}
		}

		/* check if we require phone numbers */
		if ($this->conf['requirePhone']) {
			$this->formdesc['AddressForm']['itemstext']['phone1'][0] = true;
		}

		/* get template and instantiate form handler */
		$templateFilePath = $this->conf['templateFile'];
		$this->templateFile = $this->cObj->fileResource($templateFilePath);
		$this->formHandler = new tx_pttools_formTemplateHandler($this, $this->formdesc);

        /* load customer data */
		// if user is logged in, use that customer
        if ($GLOBALS['TSFE']->loginUser == 1) {
			// use database uid from current fe_user record
			$gsauid = intval($GLOBALS['TSFE']->fe_user->user['tx_ptgsauserreg_gsa_adresse_id']);
			if ($gsauid > 0) {	// normal case: we are already a customer
				// make sure there is no customer stored in session
				tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_NAME);
				trace('Loading Customer from DB');
				$customer = new tx_ptgsauserreg_customer($gsauid);
			}
			else {	// special case: user is not yet customer
				// first check if we have a working customer in session
            	$customer = tx_pttools_sessionStorageAdapter::getInstance()->read(self::SESSION_KEY_NAME);
				// if invalid or old leftover object, create new and fill from user
            	if (!(is_object($customer) && ($customer instanceof tx_ptgsauserreg_customer) && ($customer->get_gsauid() == 0))) { 
					$user = new tx_ptgsauserreg_user($GLOBALS['TSFE']->fe_user->user['uid']);
					$customer = new tx_ptgsauserreg_customer(0, $user->getAddressDataArray());
				}
			}
		}
		else {
			$gsauid = 0;	// no customer known yet
        	// try to get customer object from session
            trace('Looking for Customer in Session');
            $customer = tx_pttools_sessionStorageAdapter::getInstance()->read(self::SESSION_KEY_NAME);
            // create new customer if session customer instance is invalid
            if (!(is_object($customer) && ($customer instanceof tx_ptgsauserreg_customer))) { 
				$customer = new tx_ptgsauserreg_customer($gsauid);

                // initialise creditLimit from conf for new customer 
                if (floatval($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['defaultCreditLimit'])  > 0 ) {
                    $customer->set_gsa_creditLimit(floatval($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['defaultCreditLimit']));
                }
            	
                tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_RETURNVAR);
			}
		}
		trace($customer);

		if (isset($this->piVars['action'])) {
			switch ($this->piVars['action']) {
				case 'bank':
					$nextform = 'BankForm';
           			tx_pttools_sessionStorageAdapter::getInstance()->store(self::SESSION_KEY_RETURNVAR, $this->piVars['return_pivar_name']);
					break;
			}
		} else {
			$bankPage = $this->conf['bankPage'];
			if (($bankPage) && ($this->pi_getPageLink($bankPage) == $this->pi_getPageLink($GLOBALS['TSFE']->id))) {
				$nextform = 'BankForm';
			} else {
				$nextform = tx_pttools_sessionStorageAdapter::getInstance()->read(self::SESSION_KEY_ACTPAGE);
			}
		}
		if (! $nextform) {
			$nextform = $this->formlist[0];
		}
		$checkmsg = '';
		$foundsubmit = false;
		// check normal form submits
		foreach ($this->formlist as $formname) {
			if (isset($this->piVars['submit_'.$formname])) {
				trace('found submit from '.$formname);
				$foundsubmit = true;
				// process and verify form input
				$processor = 'process'.$formname;
				$checkmsg = $this->$processor($customer);
				if (! $checkmsg) {
					// no problem with form, postprocessing
					switch ($formname) {
						case 'AddressForm':
							if (($customer->get_gsauid() == 0) && ($customer->get_paymentMethod() == tx_ptgsauserreg_customer::PM_DEBIT)) {
								// initialise bankAccountHolder for new customer
								if (tx_pttools_div::getSiteCharsetEncoding() == 'utf-8') {
									$holderName = mb_substr($customer->getFullName(true), 0, 27);
								} else {
									$holderName = substr($customer->getFullName(true), 0, 27);
								}
                                $customer->set_bankAccountHolder($holderName);
							}
							if ($customer->get_postmanu() && !$customer->checkForShortAddressLabel()) { 
									$nextform = 'PostmanuForm';	
							}
							else {
								$customer->rewritePostFields();
								$nextform = 'BankForm';
							}
							break;

						case 'PostmanuForm':
							$nextform = 'BankForm';
							break;

						case 'BankForm':
							// clean up partial bank data
							if (($customer->get_bankAccount() == '') && ($customer->get_bankIBAN() == '') && ($customer->get_bankAccountHolder() != '')) {
								$customer->set_bankAccountHolder('');
							}
							unset($nextform);
							break;

						default:
							break;
					}
				}
				// skip BankForm if configured to do so
				if ($nextform == 'BankForm') {
					if (intval($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['editBanking']) == 0) {
						unset($nextform);
					}
				}
				break;
			}
		}
		// special submit cases
		if (isset($this->piVars['back_AddressForm'])
			|| isset($this->piVars['back_PostmanuForm'])
			|| isset($this->piVars['back_BankForm'])) {
			tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_NAME);
			tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_ACTPAGE);
			$backURL = $this->getsetBackURL();
			tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_BACKURL);
			tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_RETURNVAR);
			if ($backURL) {
				tx_pttools_div::localRedirect($backURL);
			}
			unset($nextform);
			$content = $this->pi_getLL('msg_cancelled', '[msg_cancelled]');
		}
		if (isset($this->piVars['rewrite_PostmanuForm'])) {
			trace('found rewrite from PostmanuForm');
			$foundsubmit = true;
			$customer->rewritePostFields();
		}
		trace($customer);
		if ($foundsubmit && (! $checkmsg)) {
			// only store if there is something
			if ($customer->get_gsauid() != 0) {
				// update record in DB
           		trace('Storing Customer IN DB');
				$customer->storeSelf();
				if (($GLOBALS['TSFE']->loginUser != 1) || (intval($GLOBALS['TSFE']->fe_user->user['tx_ptgsauserreg_gsa_adresse_id']) != $customer->get_gsauid)) {
					// store customer in session
           			trace('Storing Customer IN SESSION');
           			tx_pttools_sessionStorageAdapter::getInstance()->store(self::SESSION_KEY_NAME, $customer);
				}
			}
			else {
				// store customer in session
           		trace('Storing Customer IN SESSION');
           		tx_pttools_sessionStorageAdapter::getInstance()->store(self::SESSION_KEY_NAME, $customer);
			}
		}
		if ($nextform) {
			// store page for e.g. language switch
           	tx_pttools_sessionStorageAdapter::getInstance()->store(self::SESSION_KEY_ACTPAGE, $nextform);
			// redirect to bankPage if needed
			if ($nextform == 'BankForm') {
				$bankPage = $this->conf['bankPage'];
				if ($bankPage) {
					if ($this->pi_getPageLink($bankPage) != $this->pi_getPageLink($GLOBALS['TSFE']->id)) {
						// redirect to bankPage
						$backlink = $this->pi_getPageLink($bankPage);
						$backURL = $this->getsetBackURL();
						if (! strpos($backlink, '?')) {
							$backlink .= '?';
						}
						$backlink .= '&backURL='.urlencode($backURL);
						tx_pttools_div::localRedirect($backlink);
						$content = 'redirect failed';
					}
				}
			}
			// display next form
			$processor = 'display'.$nextform;
			$content = $checkmsg.$this->$processor($customer);
		}
		else {
           	tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_ACTPAGE);
			if (! $content) {
				$backURL = $this->getsetBackURL();
           		tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_BACKURL);
				// check if we have to return to external caller
				$returnvar = tx_pttools_sessionStorageAdapter::getInstance()->read(self::SESSION_KEY_RETURNVAR);
				if ($returnvar) {
					$backlink = $backURL;
					if (! strpos($backlink, '?')) {
						$backlink .= '?';
					}
					$backlink .= '&'.$returnvar.'=0';
					tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_RETURNVAR);
				}
				else {
					// redirect to user plugin page
					$backlink = $this->pi_getPageLink($this->conf['nextPage']);
					if (! strpos($backlink, '?')) {
						$backlink .= '?';
					}
					$backlink .= '&backURL='.urlencode($backURL);
				}
				tx_pttools_div::localRedirect($backlink);
				$content = 'redirect failed';
			}
           	tx_pttools_sessionStorageAdapter::getInstance()->delete(self::SESSION_KEY_BACKURL);
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * create list of known payment choices
     * @param   object      Customer 
     * @return  array       known payment choices   
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function paymentChoices($customer)	{
		trace('[CMD] '.__METHOD__);

		$known = $customer->knownPaymentChoices();
		$choices = array();
		foreach ($known as $value) {
			switch ($value) {
				case tx_ptgsauserreg_customer::PM_CCARD:
					$choices[tx_ptgsauserreg_customer::PM_CCARD] = $this->pi_getLL('pm_ccard', '[pm_ccard]');
					break;
				case tx_ptgsauserreg_customer::PM_DEBIT:
					$choices[tx_ptgsauserreg_customer::PM_DEBIT] = $this->pi_getLL('pm_debit', '[pm_debit]');
					break;
				case tx_ptgsauserreg_customer::PM_INVOICE:
					$choices[tx_ptgsauserreg_customer::PM_INVOICE] = $this->pi_getLL('pm_invoice', '[pm_invoice]');
					break;
				default:
					$choices[$value] = $value;
					break;
			}
		}

		trace($choices);
		return $choices;
	}

	/**
	 * create list of forbidden payment choices for this customer
     * @param   object      Customer 
     * @return  array       forbidden payment choices   
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function forbiddenPaymentChoices($customer)	{
		trace('[CMD] '.__METHOD__);

		$known = $customer->knownPaymentChoices();
		$allowed = $customer->allowedPaymentChoices();
		$choices = array();
		foreach ($known as $value) {
			if (! in_array($value, $allowed)) {
				$choices[] = $value;
			}
		}

		trace($choices);
		return $choices;
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
	 * display customer address data form
     * @param   object      Customer 
     * @return  string      HTML plugin content for output on the page  
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function displayAddressForm($customer)	{
		trace('[CMD] '.__METHOD__);

		$formname = 'AddressForm';
        $tmplForm = $this->cObj->getSubpart($this->templateFile, '###'.strtoupper($formname).'###');

		$choicesArray = array();
        $choicesArray['itemsselect']['country'] = tx_ptgsauserreg_countrySpecifics::getCountryList(true);
		$disableArray = array();
		$hideArray = array();
		$relaxArray = array();
			
		if(strlen(trim($this->conf['addressFormHideList'])) > 0) {
			$hideArray = explode(',', $this->conf['addressFormHideList']);				
			$hideArray = array_map('trim', $hideArray); // trim all values
		}
		
		// old method to hide birthday
		if ((intval($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['hideBirtdate']) > 0)) {
			$hideArray[] = 'birthdate';
		}
		
		// check if state is needed
		$country = $customer->get_country();
		if (! ($country && tx_ptgsauserreg_countrySpecifics::needsRegion($country))) {
			$relaxArray[] = 'state';
		}
		
		// switch between formular and displaymode (added 21.08.2009 / Daniel Lienert)
		if((int)$this->conf['showAddressValuesOnly'] == 1) {
			// show the form values only
			$formMarkerArray = $this->formHandler->prepareDisplaySubst($formname, $customer, $choicesArray, $hideArray);
			$formMarkerArray['###ITEMSUBMIT###'] = '';
			$formMarkerArray['###ITEMBACK###'] = '';
			
		} else {
			// show the formfields	
			$formMarkerArray = $this->formHandler->prepareConfSubst($formname, $customer, $choicesArray, $disableArray, $hideArray, $relaxArray);
		}
			
		$backURL = $this->getsetBackURL();
		$backURL = tx_pttools_div::htmlOutput($backURL);
		$formMarkerArray['###BACKURL###'] = $backURL;

		// HOOK: allow multiple hooks to manipulate formMarkerArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi1_hooks']['displayAddressFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi1_hooks']['displayAddressFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$formMarkerArray = $hookObj->displayAddressFormHook($this, $formMarkerArray);
			}
		}

        return $this->cObj->substituteMarkerArray($tmplForm, $formMarkerArray);
	}

	/**
	 * display customer postal data form (only if manual changes or done in the proprietary  ERP Software)
     * @param   object      Customer 
     * @return  string      empty if ok, else HTML code for MessageBox   
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function displayPostmanuForm($customer)	{
		trace('[CMD] '.__METHOD__);

		$formname = 'PostmanuForm';
		if ($customer->get_postmanu()) {
			$msgBoxObj = new tx_pttools_msgBox('warning', $this->pi_getLL('msg_postmanu', '[msg_postmanu]'));
		}
		else {
			$msgBoxObj = new tx_pttools_msgBox('info', $this->pi_getLL('msg_postauto', '[msg_postauto]'));
		}
		$content = $msgBoxObj->__toString();
        $tmplForm = $this->cObj->getSubpart($this->templateFile, '###'.strtoupper($formname).'###');

		$choicesArray = array();

		$formMarkerArray = $this->formHandler->prepareConfSubst($formname, $customer, $choicesArray);

		$backURL = $this->getsetBackURL();
		$backURL = tx_pttools_div::htmlOutput($backURL);
		$formMarkerArray['###BACKURL###'] = $backURL;

		// HOOK: allow multiple hooks to manipulate formMarkerArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi1_hooks']['displayPostmanuFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi1_hooks']['displayPostmanuFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$formMarkerArray = $hookObj->displayPostmanuFormHook($this, $formMarkerArray);
			}
		}

        $content .= $this->cObj->substituteMarkerArray($tmplForm, $formMarkerArray);

		return $content;
	}

	/**
	 * display customer banking data form
     * @param   object      Customer 
     * @return  string      HTML plugin content for output on the page  
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function displayBankForm($customer)	{
		trace('[CMD] '.__METHOD__);

		$formname = 'BankForm';
        $tmplForm = $this->cObj->getSubpart($this->templateFile, '###'.strtoupper($formname).'###');

		$choicesArray = array();
		$disableArray = array();
		$hideArray = array();
		$relaxArray = array();
		
		if(count(trim($this->conf['bankFormHideList'])) > 0) {
			$hideList = trim($this->conf['bankFormHideList']);
			$hideArray = explode(',', $hideList);
			$hideArray = array_map('trim', $hideArray); // trim all values
		}
		
		$choicesArray['itemsradio']['paymentMethod'] = $this->paymentChoices($customer);
		$choicesArray['disabledradio']['paymentMethod'] = $this->forbiddenPaymentChoices($customer);
		$showHint = false;
        if ($customer->get_paymentMethod() != tx_ptgsauserreg_customer::PM_DEBIT) {
			$relaxArray[] = 'bankAccountHolder';
			$relaxArray[] = 'bankCode';
			$relaxArray[] = 'bankName';
			$relaxArray[] = 'bankAccount';
			$relaxArray[] = 'bankBIC';
			$relaxArray[] = 'bankIBAN';
		} else {
			foreach ($customer->allowedPaymentChoices() as $choice) {
				if ($choice != tx_ptgsauserreg_customer::PM_DEBIT) {
					$showHint = true;
					break;
				}
			}
		}
		if ($customer->get_country() == 'DE') {
			$relaxArray[] = 'bankBIC';
			$relaxArray[] = 'bankIBAN';
		}
		else {
			$hideArray[] = 'bankCode';
			$hideArray[] = 'bankAccount';
		}
        
        // check for bankCode and account number  
		$bankHelp = false;
        if ($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['checkBanking'] == true && $customer->get_country() == 'DE') {
            // bank Name will be set in processform as result of checking
            $disableArray[] = 'bankName';
			if ($customer->get_bankName() == '') {
				$bankHelp = true;
				$customer->set_bankName($this->pi_getLL('fv_bankName', '[fv_bankName]'));
			}
        }

		$formMarkerArray = $this->formHandler->prepareConfSubst($formname, $customer, $choicesArray, $disableArray, $hideArray, $relaxArray);

		// remove bankname helptext from customer object if set
		if ($bankHelp) {
			$customer->set_bankName('');
		}

		$backURL = $this->getsetBackURL();
		$backURL = tx_pttools_div::htmlOutput($this->piVars['backURL']);
		$formMarkerArray['###BACKURL###'] = $backURL;

		// HOOK: allow multiple hooks to manipulate formMarkerArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi1_hooks']['displayBankFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi1_hooks']['displayBankFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$formMarkerArray = $hookObj->displayBankFormHook($this, $formMarkerArray);
			}
		}

		$content = '';
		if ($showHint) {
			$msgBoxObj = new tx_pttools_msgBox('info', $this->pi_getLL('st_requiredhint', '[st_requiredhint]'));
			$content .= $msgBoxObj->__toString();
		}
        $content .= $this->cObj->substituteMarkerArray($tmplForm, $formMarkerArray);
        return $content;
	}

	/**
	 * process customer address data from form
     * @param   object      Customer 
     * @return  string      empty if ok, else HTML code for MessageBox 
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function processAddressForm($customer)	{
		trace('[CMD] '.__METHOD__);


		$formname = 'AddressForm';
		$choicesArray = array();
        $choicesArray['itemsselect']['country'] = tx_ptgsauserreg_countrySpecifics::getCountryList(true);
		$disableArray = array();
		$hideArray = array();
		
		// hide a few fields on record creation
		if (! $customer->get_gsauid()) {
			if(count(trim($this->conf['addressFormHideList'])) > 0) {
				$hideList = trim($this->conf['addressFormHideList']);
				$hideArray = explode(',', $hideList);
				$hideArray = array_map('trim', $hideArray); // trim all values
			}
		}
		
		trace($hideArray);
		
		if ((intval($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['hideBirtdate']) > 0)) {
			$hideArray[] = 'birthdate';
		}
		$failArray = $this->formHandler->fillFormIntoObject($formname, $customer, $choicesArray, $disableArray, $hideArray);
		$msgArray = array();
		foreach ($failArray as $item) {
			$msgArray[] = $item.' failed';
		}
		// check if state is needed
		$relaxArray = array();
		$country = $customer->get_country();
		if (! ($country && tx_ptgsauserreg_countrySpecifics::needsRegion($country))) {
			$relaxArray[] = 'state';
		}
		// check EUVatId
		if ((intval($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['euVatIdCheck']) > 0) && ($customer->get_euVatId() != '')) {
			// load checking module
			require_once t3lib_extMgm::extPath('pt_euvatcheck').'res/class.tx_pteuvatcheck_vat.php';
			$vatObj = tx_pteuvatcheck_vat::getInstance();
            if ($vatObj->get_status() == 4) {
                // Configuration Problem with Constant Editor
                $msgArray[] = $this->pi_getLL('fl_euVatId', '[fl_euVatId]').': '.$vatObj->get_returnMessage();
            } else {
                // For qualified confirmation of customer Vat Id
                #$vatObj->checkVatIdRequest($customer->get_euVatId(),'3',$customer->getFullName(), $customer->get_city(),$customer->get_zip(),$customer->get_streetAndNo());
                
                // simple confirmation of Customer Vat-Id
                $vatObj->checkVatIdRequest($customer->get_euVatId(),'2');
    
    			switch ($vatObj->get_status()) {
    				case 0: // VAT Id invalid
    				case 3: // invalid Format
    					$msgArray[] = $this->pi_getLL('fl_euVatId', '[fl_euVatId]').': '.$vatObj->get_returnMessage();
    					break;
    				case 1: // valid Id
    					break;
    				case 2: // check currently not possible
    					$msgArray[] = $this->pi_getLL('msg_novatcheck', '[msg_novatcheck]');
    					break;
    			}
            }
            
		}

		// HOOK: allow multiple hooks to evaluate piVars and manipulate msgArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi1_hooks']['processAddressFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi1_hooks']['processAddressFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$msgArray = $hookObj->processAddressFormHook($this, $msgArray, $this->piVars);
			}
		}
		
		return $this->formHandler->checkObjectInForm($formname, $customer, $msgArray, $hideArray, $relaxArray);
	}

	/**
     * process customer postal data form (only if manual changes or done in the proprietary  ERP Software)
     * @param   object      Customer 
     * @return  string      empty if ok, else HTML code for MessageBox 
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function processPostmanuForm($customer)	{
		trace('[CMD] '.__METHOD__);

		$formname = 'PostmanuForm';
		$cmpclone = clone $customer;
		$cmpclone->rewritePostFields();
		$failArray = $this->formHandler->fillFormIntoObject($formname, $customer);
		// check if we still have a manual postal address
		$customer->set_postmanu(false);
		for ($i = 1; $i <= 7; $i++) {
			$getter = 'get_post'.$i;
			if ($customer->$getter() != $cmpclone->$getter()) {
				$customer->set_postmanu(true);
				break;
			}
		}
		$msgArray = array();
		foreach ($failArray as $item) {
			$msgArray[] = $item.' failed';
		}

		// HOOK: allow multiple hooks to evaluate piVars and manipulate msgArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi1_hooks']['processPostmanuFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi1_hooks']['processPostmanuFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$msgArray = $hookObj->processPostmanuFormHook($this, $msgArray, $this->piVars);
			}
		}

		return $this->formHandler->checkObjectInForm($formname, $customer, $msgArray);
	}

	/**
	 * process customer bank data from form
     * @param   object      Customer 
     * @return  string      empty if ok, else HTML code for MessageBox 
     * @author  Wolfgang Zenker <zenker@punkt.de>
	 */
	private function processBankForm($customer)	{
		trace('[CMD] '.__METHOD__);

		$formname = 'BankForm';
		$choicesArray = array();
		$disableArray = array();
		$hideArray = array();
		$relaxArray = array();
		
		if(count(trim($this->conf['bankFormHideList'])) > 0) {
			$hideList = trim($this->conf['bankFormHideList']);
			$hideArray = explode(',', $hideList);
			$hideArray = array_map('trim', $hideArray); // trim all values
		}
		
		$choicesArray['itemsradio']['paymentMethod'] = $this->paymentChoices($customer);
		$choicesArray['disabledradio']['paymentMethod'] = $this->forbiddenPaymentChoices($customer);
		if ($customer->get_country() != 'DE') {
			$hideArray[] = 'bankCode';
			$hideArray[] = 'bankAccount';
		}
		$failArray = $this->formHandler->fillFormIntoObject($formname, $customer, $choicesArray, $disableArray, $hideArray);
		$msgArray = array();
		foreach ($failArray as $item) {
			$msgArray[] = $item.' failed';
		}
		if ($customer->get_paymentMethod() != tx_ptgsauserreg_customer::PM_DEBIT) {
			$relaxArray[] = 'bankAccountHolder';
			$relaxArray[] = 'bankCode';
			$relaxArray[] = 'bankName';
			$relaxArray[] = 'bankAccount';
			$relaxArray[] = 'bankBIC';
			$relaxArray[] = 'bankIBAN';
		}
		else if ($customer->get_country() == 'DE') {
			$relaxArray[] = 'bankBIC';
			$relaxArray[] = 'bankIBAN';
		}
		if ($customer->get_country() == 'DE') {
			if (($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['checkBanking'] == true) && 
                (($customer->get_bankCode() != '') && ($customer->get_bankAccount() != '') )) {
                $msg = $this->checkBankAccount($customer);
                if ($msg != '') {
                    $msgArray[] = $msg;
                }
            } else {
                $len = strlen($customer->get_bankCode());
    			if ($len && ($len != 8)) {
    				$msgArray[] = $this->pi_getLL('msg_bankcode_inv', '[msg_bankcode_inv]');
    			}
            }
		   
        }

          
        if ($GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['checkBanking'] == true && $customer->get_country() == 'DE') {
            $relaxArray[] = 'bankName';
        }

		// HOOK: allow multiple hooks to evaluate piVars and manipulate msgArray
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi1_hooks']['processBankFormHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['pi1_hooks']['processBankFormHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				$msgArray = $hookObj->processBankFormHook($this, $msgArray, $this->piVars);
			}
		}

		return $this->formHandler->checkObjectInForm($formname, $customer, $msgArray, $hideArray, $relaxArray);
	}

    /**
     * check Bank Account of Customer with command ktoblzcheck on this host  
     * @param   object      Customer 
     * @return  string      empty if ok, else HTML code for MessageBox 
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-05-18
     */
    private function checkBankAccount($customer) {
        trace('[CMD] '.__METHOD__);
        $cmd = $GLOBALS['TSFE']->tmpl->setup['config.'][$this->extKey.'.']['pathCheckBanking'].
            'ktoblzcheck '.
            $customer->get_bankCode(). ' '.
            $customer->get_bankAccount();
        trace ($cmd,0,'$cmd');
        $resultArr = array();
        exec($cmd, $resultArr);
		// skip warnings issued by ktoblzucheck program
		while (substr($resultArr[0],0,strlen('Warning: ')) == 'Warning: ') {
			array_shift($resultArr);
		}
        trace ($resultArr,0,'$result in'.__METHOD__);
        if (substr($resultArr[2],0,strlen('Result is: ')) == 'Result is: ') {
            $returncode = intval(substr($resultArr[2],strlen('Result is: ('),1));
            if ($returncode != 3) {
                // set Bank Name in Customer Object
                $fields = explode('\'', $resultArr[0]);
                trace ($fields,0,'fields');
                $customer->set_bankName($fields[1]);
            } 
            switch ($returncode) {
                case 0:
                    // Bank verification ok
                    $errorMsg = '';
                    break;
                case 1:
                    //  Unknown, e.g. checksum not implemented or such
                    $errorMsg = '';
                    break;
                case 2;
                    // Account and/or bank not ok
                    $errorMsg = $this->pi_getLL('msg_bankaccount_inv','[msg_bankaccount_inv]');
                    break;
                case 3;
                    // Bank not found
                    $errorMsg = $this->pi_getLL('msg_bankcode_inv','[msg_bankcode_inv]');
                    break;
            }
            
        } else {
            // should never happen
            $errorMsg = $this->pi_getLL('msg_bankcheck_err','[msg_bankcheck_err]');
        }
        
        return $errorMsg;
    }
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/pi1/class.tx_ptgsauserreg_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/pi1/class.tx_ptgsauserreg_pi1.php']);
}

?>
