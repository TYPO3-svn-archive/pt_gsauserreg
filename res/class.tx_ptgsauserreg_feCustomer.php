<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2005-2006 Rainer Kuhn <kuhn@punkt.de>, Wolfgang Zenker <zenker@punkt.de>
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
 * Frontend customer class for the 'pt_gsauserreg' extension. 
 * This class is a "wrapper" construct to integrate the new user management ext. pt_gsauserreg into pt_gsashop without changing all existing API of the former user management ext. pt_gsauseracc.
 *
 * $Id: class.tx_ptgsauserreg_feCustomer.php,v 1.26 2009/05/05 15:25:05 ry25 Exp $
 *
 * @author	Rainer Kuhn <kuhn@punkt.de>, Wolfgang Zenker <zenker@punkt.de>
 * @since   2006-09-18, based on tx_ptgsashop_feCustomer/tx_ptgsauserac_feCustomer since 2005-09-21/2005-11-11
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */
 
 

/**
 * Inclusion of extension specific resources
 */
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_user.php'; // TYPO3 FE user class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_customer.php'; // GSA customer class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_gsansch.php'; // combined GSA/TYPO3 address class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_gsanschCollection.php'; // combined GSA/TYPO3 address collection class
require_once t3lib_extMgm::extPath('pt_gsauserreg').'res/class.tx_ptgsauserreg_paymentMethod.php'; // combined GSA/TYPO3 payment method class

/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_div.php'; // general static library class
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class



/**
 * Frontend customer class: combines a TYPO3 FE user with a GSA customer and his associated addresses
 * 
 * This class is a "wrapper" construct to integrate the new user management ext. pt_gsauserreg into pt_gsashop without changing all existing API of the former user management ext. pt_gsauseracc.
 *
 * @author	    Rainer Kuhn <kuhn@punkt.de>, Wolfgang Zenker <zenker@punkt.de>
 * @since       2006-09-18, based on tx_ptgsashop_feCustomer/tx_ptgsauserac_feCustomer since 2005-09-21/2005-11-11
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_feCustomer {
    
    /**
     * Properties
     */    
    protected $feUserId = 0;            // (integer) TYPO3 Frontend User ID [set for logged-in FE customers only]
    
    protected $feUserObj;          // (object) TYPO3 FE user object: object of type tx_ptgsauserreg_user
    protected $gsaCustomerObj;     // (object) GSA customer object: object of type tx_ptgsauserreg_customer
    protected $postalAddrCollObj;  // (object) GSA/TYPO3 postal address collection object: object of type tx_ptgsauserreg_gsanschCollection
    
    protected $gsaMasterAddressId = 0;  // (integer) GSA master address record ID [set for logged-in FE customers only]
    protected $isForeign = false;       // (boolean) flag wether the customer is a foreign customer [set from GSA DEBITOR.AUSLAND for logged-in FE customers only]
    protected $isEuForeign = false;     // (boolean) flag wether the customer is a foreign customer from EU [set from GSA DEBITOR.EGAUSLAND for logged-in FE customers only]
    protected $priceCategory = 1;       // (integer) price category legitimation of the customer [set from GSA KUNDE.PREISGR for logged-in FE customers only]
    protected $vatId = '';              // (string) the customer's vat id for EU customers [set from GSA DEBITOR.EGIDENTNR for logged-in FE customers only]
    
    
    
    
	/***************************************************************************
     *   CONSTRUCTOR & OBJECT HANDLING METHODS
     **************************************************************************/
     
	/**
     * Class constructor: Sets the customer object's properties if FE user is logged-in 
     *
     * @param   integer     (optional) FE user ID to create feCustomer from
     * @return	void   
     * @global  object      $GLOBALS['TSFE']->fe_user: tslib_feuserauth Object 
     * @author  Rainer Kuhn <kuhn@punkt.de>, Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-09-18
     */
	public function __construct($feUserId=0) {
        
        trace('***** Creating new '.__CLASS__.' object. *****');
        
        $this->feUserId = (integer)$feUserId;
        
        // only if no FE user ID is passed AND the customer is a *logged-in* TYPO3 FE user: set TYPO3 FE user UID
        if ($feUserId == 0 && $GLOBALS['TSFE']->loginUser == 1) {
            $this->feUserId = (integer)$GLOBALS['TSFE']->fe_user->user['uid'];
        }
        
        // if $this->feUserId is 0 (=non-logged FE user) empty object are loaded (but they may be used for meaningful/correct method returns)
        
        // HOOK for feUserObj retrieval
        if ($hookObj = &tx_pttools_div::hookRequest('pt_gsauserreg', 'feCustomer_hooks', 'constructor_feUserObjHook')) {
            $this->feUserObj = $hookObj->constructor_feUserObjHook($this); // use hook method if hook has been found
        // default action (no hook found)
        } else {
            $this->feUserObj = new tx_ptgsauserreg_user($this->feUserId); 
        }
        
        // HOOK for gsaCustomerObj retrieval
        if ($hookObj = &tx_pttools_div::hookRequest('pt_gsauserreg', 'feCustomer_hooks', 'constructor_gsaCustomerObjHook')) {
            $this->gsaCustomerObj = $hookObj->constructor_gsaCustomerObjHook($this); // use hook method if hook has been found
        // default action (no hook found)
        } else {
            $this->gsaCustomerObj = new tx_ptgsauserreg_customer($this->feUserObj->get_gsauid());  
        }
        
        // HOOK for postalAddrCollObj retrieval
        if ($hookObj = &tx_pttools_div::hookRequest('pt_gsauserreg', 'feCustomer_hooks', 'constructor_postalAddrCollObjHook')) {
            $this->postalAddrCollObj = $hookObj->constructor_postalAddrCollObjHook($this); // use hook method if hook has been found
        // default action (no hook found)
        } else {
            $this->postalAddrCollObj = new tx_ptgsauserreg_gsanschCollection($this->feUserObj->get_gsauid(), 
                                                                             $this->feUserObj->get_defShipAddr(), 
                                                                             $this->feUserObj->get_defBillAddr());
        }
        
        // HOOK for priceCategory retrieval
        if ($hookObj = &tx_pttools_div::hookRequest('pt_gsauserreg', 'feCustomer_hooks', 'constructor_priceCategoryHook')) {
            $this->priceCategory = (integer)$hookObj->constructor_priceCategoryHook($this); // use hook method if hook has been found
        // default action (no hook found)
        } else {
            $this->priceCategory = (integer)$this->gsaCustomerObj->get_priceGroup();
        }
        
                                                                         
        $this->gsaMasterAddressId = (integer)$this->feUserObj->get_gsauid();
        $this->isForeign = (boolean)$this->gsaCustomerObj->get_isForeigner();
        $this->isEuForeign = (boolean)$this->gsaCustomerObj->get_isEUForeigner();
        $this->vatId = (string)$this->gsaCustomerObj->get_euVatId();
        
        trace($this);
            
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
    
    /**
     * Returns the customer's default billing address; if no valid default has been set by the user: tries to return the first address found for the customer; if there are no addresses for the customer: retrieves an address proposal from the user's master data, inserts this as new address record, sets it as default and returns this new address
     *
     * @param   void        
     * @return  object     object of type tx_ptgsauserreg_gsansch
     * @author  Rainer Kuhn <kuhn@punkt.de>, Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-09-18
     */
    public function getDefaultBillingAddress() {

          $defBillingAddress = $this->postalAddrCollObj->getDefBillingAddress();
          return $defBillingAddress;
          
    }
    
    /**
     * Returns the customer's default shipping address; if no valid default has been set by the user: tries to return the first address found for the customer; if there are no addresses for the customer: retrieves an address proposal from the user's master data, inserts this as new address record, sets it as default and returns this new address
     *
     * @param   void        
     * @return  object     object of type tx_ptgsauserreg_gsansch
     * @author  Rainer Kuhn <kuhn@punkt.de>, Wolfgang Zenker <zenker@punkt.de>
     * @since   2006-09-18
     */
    public function getDefaultShippingAddress() {

          $defShippingAddress = $this->postalAddrCollObj->getDefShippingAddress();
          return $defShippingAddress;
        
    }
    
    /**
     * Returns a postal address object specified by array key of the feCustomer's postal address collection's items array
     *
     * @param   integer     key/index of the required address in the postal address collection
     * @return  object      postal address object specified by array key, object of type tx_ptgsauserreg_gsansch
     * @global  
     * @throws  tx_pttools_exception   if no address found for specified array key 
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-18
     */
    public function getAddress($key) { 
 
        if (!$this->postalAddrCollObj->getIterator()->offsetExists((int)$key)) {
            throw new tx_pttools_exception('Address not found user address collection', 3, 'No address could be found in the feCustomer\'s postal address collection for the specified array key.');
        }
            
        return $this->postalAddrCollObj->getIterator()->offsetGet((int)$key);
        
    }
    
    /**
     * Returns the uid of a valid shipping/billing address for the feCustomer.
	 * If the $idToValidate is valid it will be used, otherwise the default
	 * shipping/billing address for this feCustomer
     *
     * @param   integer     address id to validate
     * @param   boolean     (optional) address ID to validate is used as shipping address (default: true; false = is used as billing address)
     * @return  integer     valid address id, possibly changed from idToValidate
     * @author  Wolfgang Zenker <zenker@punkt.de>
     * @since   2008-02-01
     */
    public function getValidatedAddressId($idToValidate, $isShippingAddress=true) { 
        $validatedId = $idToValidate;
		$addrObj = $this->postalAddrCollObj->getItemById($idToValidate);
		if ((! is_object($addrObj)) || $addrObj->get_deprecated()) {
			$validatedId = $isShippingAddress ? $this->postalAddrCollObj->get_defship() : $this->postalAddrCollObj->get_defbill();
		}
        
        return $validatedId;
    }
    
    /**
     * Returns a bool flag wether the customer is legitimated to use net prices
     *
     * @param   void
     * @return  boolean     true if the customer is legitimated to use net prices, false otherwise
     * @global  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-18
     */
    public function getNetPriceLegitimation() {
        
        // HOOK for alternative retrieval of this method's return value
        if ($hookObj = &tx_pttools_div::hookRequest('pt_gsauserreg', 'feCustomer_hooks', 'getNetPriceLegitimationHook')) {
            $isNetPriceCust = (boolean)$hookObj->getNetPriceLegitimationHook($this); // use hook method if hook has been found
        // default return value: no hook found
        } else {
            $isNetPriceCust = (boolean)$this->gsaCustomerObj->getNetPriceLegitimation();
        }
        
        return $isNetPriceCust;
        
    }
     
    /**
     * Returns a bool flag wether the customer is legitimated to use tax free orders
     *
     * @param   void
     * @return  boolean     true if the customer is legitimated to use tax free orders, false otherwise
     * @global  void
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-18
     */
    public function getTaxFreeLegitimation() {
        
        // HOOK for alternative retrieval of this method's return value
        if ($hookObj = &tx_pttools_div::hookRequest('pt_gsauserreg', 'feCustomer_hooks', 'getTaxFreeLegitimationHook')) {
            $isTaxFreeCust = (boolean)$hookObj->getTaxFreeLegitimationHook($this); // use hook method if hook has been found
        // default return value: no hook found
        } else {
            $isTaxFreeCust = (boolean)$this->gsaCustomerObj->getTaxFreeLegitimation();
        }
            
        return $isTaxFreeCust;
        
    }
    
    /**
     * Returns a flag wether the customer is marked as gross price customer [set from GSA KUNDE.PRBRUTTO for logged-in FE customers only]
     *
     * @param   void        
     * @return  boolean     flag wether the customer is marked as gross price customer
     * @global  
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-18
     */
    public function getIsNationalGrossPriceCust() {
        
        // HOOK for alternative retrieval of this method's return value
        if ($hookObj = &tx_pttools_div::hookRequest('pt_gsauserreg', 'feCustomer_hooks', 'getIsNationalGrossPriceCustHook')) {
            $isNationalGrossPriceCust = (boolean)$hookObj->getIsNationalGrossPriceCustHook($this); // use hook method if hook has been found
        // default return value: no hook found
        } else {
            $isNationalGrossPriceCust = (boolean)$this->gsaCustomerObj->isNationalGrossPriceCust();
        }
        
        return $isNationalGrossPriceCust;
        
    }
    
    /**
     * Returns an array with all allowed payment methods for this customer
     *
     * @param   void        
     * @return  array	list of allowed payment methods
     * @author  Wolfgang Zenker
     * @since   2007-03-27
     */
    public function getAllowedPaymentMethods() {
        
        return $this->gsaCustomerObj->allowedPaymentChoices();
        
    }
    
    /**
     * Returns a payment object for this customer
     *
     * @param   integer	(optional) id of payment option
     * @return  object	ptgsauserreg_paymentMethod object
     * @author  Wolfgang Zenker
     * @since   2007-03-27
     */
    public function getPaymentObject($payOptId = 0) {
        
		// payOptId is currently ignored
		$paymentObj = new tx_ptgsauserreg_paymentMethod();
		switch ($this->gsaCustomerObj->get_paymentMethod()) {
			case tx_ptgsauserreg_customer::PM_CCARD:
				$paymentObj->set_method('cc');
				break;
			case tx_ptgsauserreg_customer::PM_INVOICE:
				$paymentObj->set_method('bt');
				break;
			case tx_ptgsauserreg_customer::PM_DEBIT:
				$paymentObj->set_method('dd');
				$paymentObj->set_bankAccountHolder($this->gsaCustomerObj->get_bankAccountHolder());
				$paymentObj->set_bankName($this->gsaCustomerObj->get_bankName());
				$paymentObj->set_bankAccountNo($this->gsaCustomerObj->get_bankAccount());
				$paymentObj->set_bankCode($this->gsaCustomerObj->get_bankCode());
				$paymentObj->set_bankBic($this->gsaCustomerObj->get_bankBIC());
				$paymentObj->set_bankIban($this->gsaCustomerObj->get_bankIBAN());
				$paymentObj->set_gsaDtaAccountIndex(1);
				break;
			default:
				// $paymentObj->set_method($this->gsaCustomerObj->get_paymentMethod());
				// for the time being default is credit card
				$paymentObj->set_method('cc');
				break;
		}
        return $paymentObj;
        
    }
    
    /**
     * Checks if all required user data are aviable
     * 
     * @return true if all values are aviable, else an array of the needed fields
     * @author Daniel Lienert <lienert@punkt.de>
     * @since 2009-05-05
     */
    public function getIsUserDataComplete() {
    	
    	// get the required fields
    	$conf = tx_pttools_div::typoscriptRegistry('plugin.tx_ptgsauserreg_pi1.');   	
    	$reqArray = t3lib_div::trimExplode(',', $conf['addressFormRequiredList'], true);
		trace($reqArray);
    	
    	
    	$missArray = array();
    	
    	foreach ($reqArray as $reqField) {
    		$getter = 'get_' . $reqField;
    		
    		// getter of this field must exists
    		tx_pttools_assert::isTrue(method_exists($this->feUserObj, $getter));
    		
    		if(trim($this->feUserObj->$getter()) == '') {
    			$missArray[] = $reqField;
    		}
    	}
    	
    	return count($missArray) == 0 ? true : $missArray;
    }
    
    /**
     * Returns the customer's display name
     *
     * @param   void        
     * @return  string      the customer's display name
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-05-10
     */
    public function getDisplayName() {
        
        $displayName = $this->feUserObj->getFullName(1);
        return $displayName;
          
    }
    
    /**
     * Returns a flag wether the feCustomer has a valid GSA customer database record (GSA-DB: ADRESSE.NUMMER).
     * Needed to differentiate default TYPO3 FE users (logged in FE users without a backing GSA customer data record) from GSA enabled FE users.
     *
     * @param   void        
     * @return  string      flag wether the feCustomer has a valid GSA customer database record (GSA-DB: ADRESSE.NUMMER)
     * @author  Rainer Kuhn <kuhn@punkt.de> (based on code from Wolfgang Zenker <zenker@punkt.de>)
     * @since   2008-01-31
     */
    public function getIsGsaAddressEnabled() {
        
        $isGsaAddressEnabled = false;
        
        if ($this->feUserObj->get_gsauid() > 0) {
            $isGsaAddressEnabled = true;
        }
        
        return $isGsaAddressEnabled;
        
    }
    
    /**
     * Returns a flag wether the feCustomer is marked as GSA online customer (GSA-DB: ADRESSE.ONLINEKUNDE).
     * Needed to differentiate "passive" (deactivated) from "active" GSA enabled FE users.
     *
     * @param   void        
     * @return  boolean     flag wether the feCustomer is marked as GSA online customer
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2008-01-31
     */
    public function getIsGsaOnlineCustomer() {
        
        $isGsaOnlineCustomer = (boolean)$this->gsaCustomerObj->get_isOnlinekunde();
        
        return $isGsaOnlineCustomer;
        
    }
    
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
     
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer     property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-07
     */
    public function get_feUserId() {
        
        return $this->feUserId;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  tx_ptgsauserreg_user      property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-19
     */
    public function get_feUserObj() {
        
        return $this->feUserObj;
        
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  tx_ptgsauserreg_customer      property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-19
     */
    public function get_gsaCustomerObj() {
        
        return $this->gsaCustomerObj;
        
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  tx_ptgsauserreg_gsanschCollection      property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-09-19
     */
    public function get_postalAddrCollObj() {
        
        return $this->postalAddrCollObj;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer     property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-07
     */
    public function get_gsaMasterAddressId() {
        
        return $this->gsaMasterAddressId;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean     property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-24
     */
    public function get_isForeign() {
        
        return $this->isForeign;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean     property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-24
     */
    public function get_isEuForeign() {
        
        return $this->isEuForeign;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer     property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-24
     */
    public function get_priceCategory() {
        
        return $this->priceCategory;
        
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string     property value
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2006-08-07
     */
    public function get_vatId() {
        
        return $this->vatId;
        
    }
    
    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_feCustomer.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_feCustomer.php']);
}

?>
