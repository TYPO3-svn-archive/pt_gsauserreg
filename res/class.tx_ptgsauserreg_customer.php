<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Wolfgang Zenker (zenker@punkt.de)
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
 * Class for customer objects in the pt_hosting framework
 *
 * $Id: class.tx_ptgsauserreg_customer.php,v 1.55 2009/11/24 13:25:45 ry25 Exp $
 *
 * @author	Wolfgang Zenker <zenker@punkt.de>
 * @since   2006-03-30
 */
  
 /**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 */
  
/**
 * Inclusion of punkt.de libraries
 */
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_customerAccessor.php';  // extension specific database accessor class for customer data
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_countrySpecifics.php';  // helper class for country specific stuff
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_lib.php';
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/abstract/class.tx_pttools_address.php'; // general address helper class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_gsaSpecialsAccessor.php'; // GSA specific stuff

/**
 *  Class for customer objects
 *
 * @access      public
 * @author	    Wolfgang Zenker <zenker@punkt.de>
 * @since       2006-03-30
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_customer extends tx_pttools_address {
    
	/**
	 * Constants
	 */

	const EXTKEY = 'pt_gsauserreg';
										// well known payment methods from ERP
	const PM_CCARD = 'Kreditkarte';
	const PM_INVOICE = 'Rechnung';
	const PM_DEBIT = 'DTA-Buchung';
										// well known dunning methods from ERP
	const DM_NORMAL = 'Mahnlauf';
	const DM_NONE = 'Keine Mahnung';

										// Initial values for some properties
	const DEB_TAGNETTO = 5;
	const DEB_MAHNTAGE = 5;

    /**
     * Properties
     */

    protected $gsauid = 0;        // ID of master database record (GSA-DB: ADRESSE.NUMMER)
    protected $euVatId = '';      // for EU companies: european VAT Id
    protected $isCorporate = 0;   // customer is a company (else person)
	protected $isOnlinekunde = 1; // flag ONLINEKUNDE in GSA-DB
    protected $priceGroup = 1;	// customer gets price from this group
    protected $birthdate = '';    // birthday (persons only)
    protected $lob = '';          // line of business (company only)
    
    // additional address fields, normally set from standard address
    protected $postmanu = 0;      // manual postal address in next fields
                                // normally the postal address is automatically
                                // generated from standard address field and the
                                // following fields are redundant
    protected $post1 = '';        // postal address line 1 to 7
    protected $post2 = '';
    protected $post3 = '';
    protected $post4 = '';
    protected $post5 = '';
    protected $post6 = '';
    protected $post7 = '';

    // payment data fields
    protected $paymentMethod = ''; // default payment method of customer
    protected $bankAccountHolder = ''; // Holder of bank account
    protected $bankName = ''; // Name of bank  
    protected $bankCode = ''; // national bank code
    protected $bankAccount = ''; // account number
    protected $bankBIC = ''; // international bank code
    protected $bankIBAN = ''; // international account number
    protected $ccType = ''; // credit card type
    protected $ccNumber = ''; // credit card number
    protected $ccExpiry = ''; // credit card expiry date
    protected $ccHolder = ''; // credit card name of card holder
     
	// ERP specific properties
	protected $gsa_tagnetto = self::DEB_TAGNETTO;	// days until invoice is payable
    protected $gsa_creditLimit;     // (double) credit Limit for Customer
    protected $gsa_outstandingAmount;     // (double) outstanding Amount for Customer
	protected $gsa_kundgr = 'Online-Kunde';	// informal: customer group
	protected $gsa_prbrutto = 1;	// customer gets Gross Price
	protected $gsa_versart = '';	// delivery mode
	protected $gsa_kundnr;			// customer no. GSA-DB ADRESSE.KUNDNR

	// ERP specific properties related to dunning
	protected $gsa_dunningMethod = self::DM_NORMAL;	// DEBITOR.MAHNART
	protected $gsa_dunningDays = self::DEB_MAHNTAGE;	// DEBITOR.MAHNTAGE days for customer to pay after dunning
	protected $gsa_dunningCharge1;	// (double) DEBITOR.MAHNGEB1 dunning charge for first reminder
	protected $gsa_dunningCharge2;	// (double) DEBITOR.MAHNGEB2 dunning charge for 2nd reminder
	protected $gsa_dunningCharge3;	// (double) DEBITOR.MAHNGEB3 dunning charge for 3rd reminder
	protected $gsa_dunningLastDate;	// (datestring) DEBITOR.LMAHNUNG

	// derived properties
	protected $isForeigner = 0;	// is foreign customer
	protected $isEUForeigner = 0;	// is foreign customer from EU country
    
	
	
	/***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
    
	/**
     * Class constructor - fills object's properties with param array data
     *
     * @param   integer     (optional) ID of the GSA-DB address record (ADRESSE.NUMMER). Set to 0 if you want to use the 2nd param.
     * @param   array       Array containing address data to set as address object's properties; array keys have to be named exactly like the proprerties of this class and it's parent class (see tx_ptgsauseracc_address::setAddressFromGivenArray() for used properties). This param has no effect if the 1st param is set to something other than 0.
     * @return	void   
     * @throws  tx_pttools_exception   if the first param is not numeric  
     * @see     tx_ptgsauseracc_customer::setCustomerFromGivenArray()
     * @author  Rainer Kuhn, Wolfgang Zenker <t3extensions@punkt.de>
     * @since   2006-04-10
     */
	public function __construct($customerId=0, $customerDataArr=array()) {
    
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        if (!is_numeric($customerId)) {
            throw new tx_pttools_exception('Parameter error', 3, 'First parameter for '.__CLASS__.' constructor is not numeric');
        }
        
        // if a customer record ID is given, retrieve customer array from database accessor (and overwrite 2nd param)
        if ($customerId > 0) {
            $customerDataArr = tx_ptgsauserreg_customerAccessor::getInstance()->selectCustomerData($customerId);
        }
        
        $this->setCustomerFromGivenArray($customerDataArr);
		$this->isCorporate = !($this->isForeigner || $this->gsa_prbrutto);
		if ($this->postEmpty()) {
			$this->rewritePostFields();
		}
           
        trace($this);
        
    }
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Sets the customer properties using data given by param array
     *
     * @param   array     Array containing customer data to set as customer object's properties; array keys have to be named exactly like the proprerties of this class and it's parent class.
     * @return  void        
     * @author  Rainer Kuhn, Wolfgang Zenker <t3extensions@punkt.de>
     * @since   2005-12-23
     */
    protected function setCustomerFromGivenArray($customerDataArr) {
        
		foreach (get_class_vars( __CLASS__ ) as $propertyname => $pvalue) {
			if (isset($customerDataArr[$propertyname])) {
				$setter = 'set_'.$propertyname;
				$this->$setter($customerDataArr[$propertyname]);
			}
		}
    }
    
    /**
     * returns array with data from all properties
     *
     * @param   void
     * @return  array	array with data from all properties        
     * @author  Rainer Kuhn, Wolfgang Zenker <t3extensions@punkt.de>
     * @since   2005-12-23
     */
    protected function getDataArray() {

		$dataArray = array();

		foreach (get_class_vars( __CLASS__ ) as $propertyname => $pvalue) {
			$getter = 'get_'.$propertyname;
			$dataArray[$propertyname] = $this->$getter();
		}

		return $dataArray;
	}
        
    /**
     * Stores current customer data in GSA-DB
	 * if gsauid is non-zero, these records are updated;
	 * otherwise new records are created
	 *
     * @param   void        
     * @return  void
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-04-20
     */
    public function storeSelf() {

		$dataArray = $this->getDataArray();
        $this->gsauid = tx_ptgsauserreg_customerAccessor::getInstance()->storeCustomerData($dataArray);
        
	}
        
    /**
     * Returns the full name of customer
     * If customer is a company, use company property,
     * otherwise concatenate firstname and lastname
     *
	 * overwrites function from parent class
	 *
     * @param   boolean     flag wether the full a natural persons name should be returned in reverse order ('Lastname, Firstname')
     * @return  string      full name
     * @global  
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-04-10
     */
    public function getFullName($inverseOrder=0) {
        
        if ($this->company) {
            $fullName = $this->company;
		} else {
        	$fullName = parent::getFullName($inverseOrder);
		}
        return $fullName;
        
    }
    
    /**
     * Returns the address label composed from all "post" fields
     *
     * @param   string      (optional) line delimiter (Default: '<br />') 
     * @param   boolean     (optional) use XSS prevention for "post" field content (Default: 1); set to 0 only for plain text usage!
     * @return  string      address label
     * @author	Fabrizio Branca <branca@punkt.de>
     * @since 	2007-10-11
     */
    public function getAddressLabel($delimiter='<br />', $useXssPrevention=1){
        
        $address = array();
        
        for ($i=1; $i<=7; $i++) {
            $getter = 'get_post'.$i;
            $tmp = ($useXssPrevention == 0 ? $this->$getter() : tx_pttools_div::htmlOutput($this->$getter()));
            if ($tmp) { 
                $address[] = $tmp;
            }
        }
        
        return implode($delimiter, $address);   
        
    }
    
    /**
     * Customer is a national customer and gets gross price
	 *
     * @param   void        
     * @return  bool
     * @global  
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-09-19
     */
    public function isNationalGrossPriceCust() {
        
		$result = true;
        
		if ($this->isForeigner || ! $this->gsa_prbrutto) {
			$result = false;
		}
        return $result;
    }
    
    /**
     * Customer is legitimized to see net price
	 *
     * @param   void        
     * @return  bool
     * @global  
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-09-19
     */
    public function getNetPriceLegitimation() {
        
		$result = ! $this->isNationalGrossPriceCust();
        
        return $result;
    }
    
    /**
     * Customer is not charged VAT
	 *
     * @param   void        
     * @return  bool
     * @global  
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-09-19
     */
    public function getTaxFreeLegitimation() {
        
		$result = false;
        
		if (! tx_ptgsauserreg_lib::getGsaUserregConfig('alwaysVAT')) {
			if ($this->isEUForeigner) {
				$result = ! empty($this->euVatId);
			}
			else {
				$result = $this->isForeigner;
			}
		}

        return $result;
    }

    /**
     * checks if post1-7 properties are empty
	 *
     * @param   void        
     * @return  boolean
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @global  
     * @since   2008-10-02
     */
    protected function postEmpty() {
        
		$result = true;

		for ($i = 1; $i <= 7; $i++) {
			$fname = 'post'.$i;
			if ($this->$fname != '') {
				$result = false;
				break;
			}
		}
		return $result;
    }
 
    /**
     * Overwrites the post1-7 properties with address
     * constructed from other fields and clears postmanu
	 *
     * @param   void        
     * @return  void
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @global  
     * @since   2006-04-21
     */
    public function rewritePostFields() {
        
    	$tsConfig = tx_pttools_div::typoscriptRegistry('plugin.tx_ptgsauserreg_pi1.');
		
    	if((int) $tsConfig['useShortAddressLabelFormat']) {
    		$postArray = $this->getShortAddressLabel();
    		$this->postmanu = true;     		
    	} else {
    		$this->postmanu = false;
    		$postArray = tx_ptgsauserreg_gsaSpecialsAccessor::getInstance()->getPostFields($this);
    	}
		
		for ($i = 1; $i <= 7; $i++) {
			$fname = 'post'.$i;
			$this->$fname = $postArray[$fname];
		}
    }
 	
    /**
     * get the Address label array in short notation
     * 
     * @return array og post fields
     * @author Daniel Lienert <lienert@punkt.de>
     * @since 24.11.2009
     */
    public function getShortAddressLabel() {
    	$postArray = tx_ptgsauserreg_gsaSpecialsAccessor::getInstance()->getPostFields($this);
    	
    	// if this option is set, remove the salutation line (post1)
		unset($postArray['post1']);
    	
    	foreach($postArray as $postKey => $postValue) {
    		if(substr($postKey,0,4) == 'post') {
				$newKey = 'post' . ((int) substr($postKey,4) - 1);
    			$shortPostArray[$newKey] = $postValue;
    		} else {
    			$shortPostArray[$postKey] = $postValue;
    		}
    	}
    	
    	return $shortPostArray;
    }
    
    /**
     * check if the postLabel is in short address notation
     * 
     * @return boolean
     * @author Daniel Lienert <lienert@punkt.de>
     * @since 24.11.2009
     */
    public function checkForShortAddressLabel() {
    	$postArray = $this->getShortAddressLabel();
    	
    	for ($i = 1; $i <= 7; $i++) {
			$fname = 'post'.$i;
			if($this->$fname != $postArray[$fname]) {
				return false;
			}
		}
		
		return true;
    }
    
	/**
	 * create list of allowed payment choices for this customer
	 *
     * @param   void        
     * @return  array	list of allowed payment methods
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-03-27
	 */
	public function allowedPaymentChoices()	{
		trace('[METHOD] '.__METHOD__);

		$choices = array();
		// customers from some countries can use direct debit
		$debitCountries = tx_ptgsauserreg_lib::getGsaUserregConfig('debitCountries');
		if (t3lib_div::inList($debitCountries, $this->get_country())) {
			$choices[] = self::PM_DEBIT;
		}
		// All customers are allowed to use credit card, if configured
		if (tx_ptgsauserreg_lib::getGsaUserregConfig('ccAllow')) {
			$choices[] = self::PM_CCARD;
		}
		// Allow invoice for all if configured
		if (tx_ptgsauserreg_lib::getGsaUserregConfig('invoiceAlways')) {
			$choices[] = self::PM_INVOICE;
		}
		else {
			if (tx_ptgsauserreg_lib::getGsaUserregConfig('invoiceBusiness')) {
				// business customers can pay per invoice
				if ($this->get_isCorporate() && ! $this->get_isForeigner()) {
					$choices[] = self::PM_INVOICE;
				}
			}
			if (tx_ptgsauserreg_lib::getGsaUserregConfig('invoiceEuvatid')) {
				// customers with EuVatId can pay per Invoice
				if ($this->get_euVatId()) {
					$choices[] = self::PM_INVOICE;
				}
			}
		}
		// If customer has an unknown choice, offer to keep it
		switch ($this->get_paymentMethod()) {
			case self::PM_CCARD:
			case self::PM_DEBIT:
			case self::PM_INVOICE:
			case '':
				break;
			default:
				$choices[] = $this->get_paymentMethod();
				break;
		}

		trace($choices);
		return $choices;
	}

	/**
	 * create list of known payment choices
	 *
     * @param   void        
     * @return  array	list of known payment methods
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-05-02
	 */
	public function knownPaymentChoices()	{
		trace('[METHOD] '.__METHOD__);

		$choices = array(
			self::PM_DEBIT,
			self::PM_INVOICE,
		);
		// if creditCards are supported, add these to known payment methods
		if (tx_ptgsauserreg_lib::getGsaUserregConfig('ccAllow')) {
			$choices[] = self::PM_CCARD;
		}
		// If customer has an unknown choice, add that as well
		if (! (in_array($this->get_paymentMethod(), $choices))) {
				$choices[] = $this->get_paymentMethod();
		}

		trace($choices);
		return $choices;
	}


    /**
     * get outstanding amount from GSA and set in property
     *
     * @param   void        
     * @return  double   outstanding amount from GSA
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-06-14
     */
    public function getOutstandingAmount()   {
        trace('[METHOD] '.__METHOD__);

        $this->set_gsa_outstandingAmount(tx_ptgsauserreg_customerAccessor::getInstance()->selectOutstandingAmount($this->get_gsauid()));
        return $this->get_gsa_outstandingAmount();
    }


    /**
     * get cumulated transaction volume from GSA
     *
     * @param   void        
     * @return  double   cumulated transaction volume from GSA
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-06-14
     */
    public function getTransactionVolume()   {
        trace('[METHOD] '.__METHOD__);

        return tx_ptgsauserreg_customerAccessor::getInstance()->getTransactionVolume($this->get_gsauid());
    }


    /**
     * set creditLimit in customerObject and GSA 
     *
     * @param   double   creditLimit from GSA        
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-06-20
     */
    public function setCreditLimit($creditLimit)   {
        trace('[METHOD] '.__METHOD__);

        $this->set_gsa_creditLimit((double) $creditLimit);
        $this->storeSelf();
    }


    /**
     * register today as date of last contact in database
     *
     * @param   void
     * @return  void
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-06-26
     */
    public function registerLastContact() {
        trace('[METHOD] '.__METHOD__);

		tx_ptgsauserreg_customerAccessor::getInstance()->registerLastContact($this->get_gsauid());
    }


    /**
     * register volume of financial transaction
     *
     * @param   double	transaction volume
     * @return  void
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2007-06-26
     */
    public function registerTransactionVolume($amount) {
        trace('[METHOD] '.__METHOD__);

		tx_ptgsauserreg_customerAccessor::getInstance()->registerTransactionVolume($this->get_gsauid(), (double) $amount);
    }


    /**
     * register payment of financial transaction
     *
     * @param   double  transaction volume
     * @param   double  discount 
     * @return  void
     * @author  Dorit Rottner <rottner@punkt.de>
     * @since   2007-08-29
     */
    public function registerTransactionPayment($amount, $discount) {
        trace('[METHOD] '.__METHOD__);

        tx_ptgsauserreg_customerAccessor::getInstance()->registerTransactionPayment($this->get_gsauid(), (double) $amount, (double) $discount);
    }


    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
     
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  int      property value
     * @since   2006-04-10
     */
    public function get_gsauid() {
        return $this->gsauid;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_euVatId() {
        return $this->euVatId;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  bool      property value
     * @since   2006-04-10
     */
    public function get_isCorporate() {
        return $this->isCorporate;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  bool      property value
     * @since   2008-01-29
     */
    public function get_isOnlinekunde() {
        return $this->isOnlinekunde;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  int      property value
     * @since   2006-08-09
     */
    public function get_priceGroup() {
        return $this->priceGroup;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_birthdate() {
        return $this->birthdate;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_lob() {
        return $this->lob;
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  bool      property value
     * @since   2006-04-10
     */
    public function get_postmanu() {
        return $this->postmanu;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_post1() {
        return $this->post1;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_post2() {
        return $this->post2;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_post3() {
        return $this->post3;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_post4() {
        return $this->post4;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_post5() {
        return $this->post5;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_post6() {
        return $this->post6;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_post7() {
        return $this->post7;
    }


    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_paymentMethod() {
        return $this->paymentMethod;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_bankAccountHolder() {
        return $this->bankAccountHolder;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_bankName() {
        return $this->bankName;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_bankCode() {
		// remove any blanks that might be in the field
		$bankCode = preg_replace('/ /', '', $this->bankCode);
        return $bankCode;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_bankAccount() {
		// remove any blanks that might be in the field
		$bankAccount = preg_replace('/ /', '', $this->bankAccount);
        return $bankAccount;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_bankBIC() {
        return $this->bankBIC;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_bankIBAN() {
        return $this->bankIBAN;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_ccType() {
        return $this->ccType;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_ccNumber() {
        return $this->ccNumber;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_ccExpiry() {
        return $this->ccExpiry;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2006-04-10
     */
    public function get_ccHolder() {
        return $this->ccHolder;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  bool      property value
     * @since   2006-04-21
     */
    public function get_isForeigner() {
        return $this->isForeigner;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  bool      property value
     * @since   2006-04-21
     */
    public function get_isEUForeigner() {
        return $this->isEUForeigner;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  int		property value
     * @since   2006-09-19
     */
    public function get_gsa_tagnetto() {
        return $this->gsa_tagnetto;
    }

    /**
     * Returns the property value
     *
     * @param   void
     * @return  double  property value
     * @since   2007-06-12
    */
    public function get_gsa_creditLimit() {
        return $this->gsa_creditLimit;
    }


    /**
     * Returns the property value
     *
     * @param   void
     * @return  double  property value
     * @since   2007-06-13
    */
    public function get_gsa_outstandingAmount() {
        return $this->gsa_outstandingAmount;
    }


    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string	property value
     * @since   2006-09-19
     */
    public function get_gsa_kundgr() {
        return $this->gsa_kundgr;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  bool	property value
     * @since   2006-09-19
     */
    public function get_gsa_prbrutto() {
        return $this->gsa_prbrutto;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string	property value
     * @since   2006-09-19
     */
    public function get_gsa_versart() {
        return $this->gsa_versart;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string	property value
     * @since   2007-06-01
     */
    public function get_gsa_kundnr() {
        return $this->gsa_kundnr;
    }

    /**
     * Set the property value
     *
     * @param   int        
     * @return  void
     * @since   2006-04-10
     */
    public function set_gsauid($gsauid) {
        $this->gsauid = intval($gsauid);
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_euVatId($euVatId) {
        $this->euVatId = (string) $euVatId;
    }

    /**
     * Set the property value
     *
     * @param   bool        
     * @return  void
     * @since   2006-04-10
     */
    public function set_isCorporate($isCorporate) {
		$this->isCorporate = $isCorporate ? true : false;
    }

    /**
     * Set the property value
     *
     * @param   bool        
     * @return  void
     * @since   2008-01-29
     */
    public function set_isOnlinekunde($isOnlinekunde) {
		$this->isOnlinekunde = $isOnlinekunde ? true : false;
    }

    /**
     * Set the property value
     *
     * @param   int        
     * @return  void
     * @since   2006-08-09
     */
    public function set_priceGroup($priceGroup) {
        $this->priceGroup = intval($priceGroup);
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_birthdate($birthdate) {
        $this->birthdate = (string) $birthdate;
    }

    /**
     * Set the property value
     *
     * @param   string        
     * @return  void
     * @since   2006-04-10
     */
    public function set_lob($lob) {
        $this->lob = (string) $lob;
    }
    
    /**
     * Set the property value
     *
     * @param   bool        
     * @return  void
     * @since   2006-04-10
     */
    public function set_postmanu($postmanu) {
        $this->postmanu = intval($postmanu);
    }

    /**
     * Set the property value
     *
     * @param   string        
     * @return  void
     * @since   2006-04-10
     */
    public function set_post1($post1) {
        $this->post1 = (string) $post1;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_post2($post2) {
        $this->post2 = (string) $post2;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_post3($post3) {
        $this->post3 = (string) $post3;
    }

    /**
     * Set the property value
     *
     * @param   string        
     * @return  void
     * @since   2006-04-10
     */
    public function set_post4($post4) {
        $this->post4 = (string) $post4;
    }

    /**
     * Set the property value
     *
     * @param   string        
     * @return  void
     * @since   2006-04-10
     */
    public function set_post5($post5) {
        $this->post5 = (string) $post5;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_post6($post6) {
        $this->post6 = (string) $post6;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_post7($post7) {
        $this->post7 = (string) $post7;
    }


    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_paymentMethod($paymentMethod) {
        $this->paymentMethod = (string) $paymentMethod;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_bankAccountHolder($bankAccountHolder) {
        $this->bankAccountHolder = (string) $bankAccountHolder;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_bankName($bankName) {
        $this->bankName = (string) $bankName;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_bankCode($bankCode) {
		$bankCode = preg_replace('/ /', '', $bankCode);
        $this->bankCode = (string) $bankCode;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_bankAccount($bankAccount) {
		$bankAccount = preg_replace('/ /', '', $bankAccount);
        $this->bankAccount = (string) $bankAccount;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_bankBIC($bankBIC) {
        $this->bankBIC = (string) $bankBIC;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_bankIBAN($bankIBAN) {
        $this->bankIBAN = (string) $bankIBAN;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_ccType($ccType) {
        $this->ccType = (string) $ccType;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_ccNumber($ccNumber) {
        $this->ccNumber = (string) $ccNumber;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_ccExpiry($ccExpiry) {
        $this->ccExpiry = (string) $ccExpiry;
    }

    /**
     * Set the property value
     *
     * @param   string
     * @return  void
     * @since   2006-04-10
     */
    public function set_ccHolder($ccHolder) {
        $this->ccHolder = $ccHolder;
    }

    /**
     * Set the property value
     *
     * @param   bool        
     * @return  void
     * @since   2006-04-21
     */
    public function set_isForeigner($isForeigner) {
		// only set as long as country is empty
		if (isempty($this->country)) {
        	$this->isForeigner = $isForeigner ? true : false;
		}
    }

    /**
     * Set the property value
     *
     * @param   bool        
     * @return  void
     * @since   2006-04-21
     */
    public function set_isEUForeigner($isEUForeigner) {
		// only set as long as country is empty
		if (isempty($this->country)) {
        	$this->isEUForeigner = $isEUForeigner ? true : false;
		}
    }

    /**
     * Set the property value
     *
     * @param   int        
     * @return  void
     * @since   2006-09-19
     */
    public function set_gsa_tagnetto($gsa_tagnetto) {
       	$this->gsa_tagnetto = intval($gsa_tagnetto);
    }

    /**
     * Set the property value
     *
     * @param   double
     * @return  void
     * @since   2007-06-12
    */
    public function set_gsa_creditLimit($gsa_creditLimit) {
        $this->gsa_creditLimit = (double)$gsa_creditLimit;
    }


    /**
     * Set the property value
     *
     * @param   double
     * @return  void
     * @since   2007-06-12
    */
    public function set_gsa_outstandingAmount($gsa_outstandingAmount) {
        $this->gsa_outstandingAmount = (double)$gsa_outstandingAmount;
    }


    /**
     * Set the property value
     *
     * @param   string        
     * @return  void
     * @since   2006-09-19
     */
    public function set_gsa_kundgr($gsa_kundgr) {
       	$this->gsa_kundgr = $gsa_kundgr;
    }

    /**
     * Set the property value
     *
     * @param   bool        
     * @return  void
     * @since   2006-09-19
     */
    public function set_gsa_prbrutto($gsa_prbrutto) {
       	$this->gsa_prbrutto = $gsa_prbrutto ? true : false;
    }

    /**
     * Set the property value
     *
     * @param   string        
     * @return  void
     * @since   2006-09-19
     */
    public function set_gsa_versart($gsa_versart) {
       	$this->gsa_versart = $gsa_versart;
    }

    /**
     * Set the property value
     *
     * @param   string        
     * @return  void
     * @since   2007-06-01
     */
    public function set_gsa_kundnr($gsa_kundnr) {
       	$this->gsa_kundnr = $gsa_kundnr;
    }

    /**
     * Set the property value
	 *
	 * extends parent method
     *
     * @param   string
     * @return  void
     * @since   2006-04-21
     */
    public function set_country($country) {

		$firsttime = $this->country == '';
		if (tx_ptgsauserreg_countrySpecifics::isForeignCountry($country)) {
			$this->isForeigner = 1;
			$this->isEUForeigner = tx_ptgsauserreg_countrySpecifics::isEuMember($country);
			if (! $this->paymentMethod) {
				$this->paymentMethod = self::PM_CCARD;
			}
			$this->gsa_prbrutto = false;
		}
		else {
			$this->isForeigner = 0;
			$this->isEUForeigner = 0;
			if (! $this->paymentMethod) {
				$this->paymentMethod = self::PM_DEBIT;
			}
			if (! $firsttime) {
				$this->gsa_prbrutto = ! $this->isCorporate;
			}
		}
		parent::set_country($country);
    }

	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	string	property value
	 * @since	2008-09-22
	*/
	public function get_gsa_dunningMethod() {
		return $this->gsa_dunningMethod;
	}

	/**
	 * Set the property value
	 *
	 * @param	string
	 * @return	void
	 * @since	2008-09-22
	*/
	public function set_gsa_dunningMethod($gsa_dunningMethod) {
		$this->gsa_dunningMethod = (string) $gsa_dunningMethod;
	}


	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	integer	property value
	 * @since	2008-09-22
	*/
	public function get_gsa_dunningDays() {
		return $this->gsa_dunningDays;
	}

	/**
	 * Set the property value
	 *
	 * @param	integer
	 * @return	void
	 * @since	2008-09-22
	*/
	public function set_gsa_dunningDays($gsa_dunningDays) {
		$this->gsa_dunningDays = intval($gsa_dunningDays);
	}


	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	double	property value
	 * @since	2008-09-22
	*/
	public function get_gsa_dunningCharge1() {
		return $this->gsa_dunningCharge1;
	}

	/**
	 * Set the property value
	 *
	 * @param	double
	 * @return	void
	 * @since	2008-09-22
	*/
	public function set_gsa_dunningCharge1($gsa_dunningCharge1) {
		$this->gsa_dunningCharge1 = (double)$gsa_dunningCharge1;
	}


	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	double	property value
	 * @since	2008-09-22
	*/
	public function get_gsa_dunningCharge2() {
		return $this->gsa_dunningCharge2;
	}

	/**
	 * Set the property value
	 *
	 * @param	double
	 * @return	void
	 * @since	2008-09-22
	*/
	public function set_gsa_dunningCharge2($gsa_dunningCharge2) {
		$this->gsa_dunningCharge2 = (double)$gsa_dunningCharge2;
	}


	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	double	property value
	 * @since	2008-09-22
	*/
	public function get_gsa_dunningCharge3() {
		return $this->gsa_dunningCharge3;
	}

	/**
	 * Set the property value
	 *
	 * @param	double
	 * @return	void
	 * @since	2008-09-22
	*/
	public function set_gsa_dunningCharge3($gsa_dunningCharge3) {
		$this->gsa_dunningCharge3 = (double)$gsa_dunningCharge3;
	}


	/**
	 * Returns the property value
	 *
	 * @param	void
	 * @return	string	property value
	 * @since	2008-09-22
	*/
	public function get_gsa_dunningLastDate() {
		return $this->gsa_dunningLastDate;
	}

	/**
	 * Set the property value
	 *
	 * @param	string
	 * @return	void
	 * @since	2008-09-22
	*/
	public function set_gsa_dunningLastDate($gsa_dunningLastDate) {
		$this->gsa_dunningLastDate = (string) $gsa_dunningLastDate;
	}
	
	/**
     * Magic method for function call
     * Use the hook inside to add various getter and setter to the customer object
     * 
	 * @param $method		Name of the method to be called
	 * @param $arguments	Arguments passed to the function
     * @return unknown_type
     * @author Daniel Lienert <lienert@punkt.de>
     * @since 21.08.2009
     */
    public function __call($method, $arguments)	{
		
    	// restrict to getter and setter methods
		if (!in_array(substr($method, 0, 4),array('get_', 'set_'))) {
			throw new tx_pttools_exceptionInternal(
					$method . ' is not allowed here - use getter and setter only.',
					$method . ' is not allowed here - use getter and setter only.'
			);
		}
    	
	    // HOOK: allow multiple hooks to simulate getter and setter
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['customer_hooks']['simulateGetterSetterHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['pt_gsauserreg']['customer_hooks']['simulateGetterSetterHook'] as $className) {
				$hookObj = &t3lib_div::getUserObj($className);
				
				if(method_exists($hookObj, $method)) {
					return $hookObj->$method($this, $arguments);
				} else {
					throw new tx_pttools_exceptionInternal('No method defined in the hook to handle the method ' . $method, 'No method defined in the hook to handle the method ' . $method);
				}
				
			}
		} else {
			throw new tx_pttools_exceptionInternal('No hook defined to handle the method ' . $method, 'No hook defined to handle the method ' . $method);
		} 	
    }


}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_customer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_customer.php']);
}

?>
