<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2007 Rainer Kuhn (kuhn@punkt.de)
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
 * Payment method class for shop orders of the 'pt_gsauserreg' extension
 *
 * $Id: class.tx_ptgsauserreg_paymentMethod.php,v 1.11 2007/05/22 11:06:07 ry37 Exp $
 *
 * @author	Rainer Kuhn <kuhn@punkt.de>
 * @since   2007-03-23
 */ 
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */



/**
 * Inclusion of extension specific resources
 */
 
/**
 * Inclusion of external resources
 */
require_once t3lib_extMgm::extPath('pt_tools').'res/objects/class.tx_pttools_exception.php'; // general exception class
require_once t3lib_extMgm::extPath('pt_tools').'res/staticlib/class.tx_pttools_debug.php'; // debugging class with trace() function



/**
 * Payment method class for GSA shop orders
 *
 * @author	    Rainer Kuhn <kuhn@punkt.de>
 * @since       2007-03-23
 * @package     TYPO3
 * @subpackage  tx_ptgsauserreg
 */
class tx_ptgsauserreg_paymentMethod {
    
    /** 
     * Properties: general
     */
    protected $method = ''; // (string) payment method ('bt' = bank transfer/on account, 'cc' = credit card, 'dd' = direct debit)
    
    /** 
     * Properties: for payment method 'cc' only
     */
    protected $epaymentSuccess = 0;     // (boolean) flag whether a credit card ePayment has been successfully processed (for payment method 'cc')
    protected $epaymentTransId = '';    // (string) credit card ePayment transaction ID (for payment method 'cc')
    protected $epaymentRefId = '';      // (string) credit card ePayment reference ID (for payment method 'cc')
    protected $epaymentShortId = '';    // (string) credit card ePayment short ID (for payment method 'cc')
    
    /** 
     * Properties: for payment method 'dd' only
     */
    protected $bankAccountHolder = '';  // (string) bank account holder (for payment method 'dd')
    protected $bankName = '';           // (string) bank name (for payment method 'dd')
    protected $bankAccountNo = '';      // (string) bank account number for inland transactions (for payment method 'dd')
    protected $bankCode = '';           // (string) bank code for inland transactions (for payment method 'dd')
    protected $bankBic = '';            // (string) bank BIC for international transactions (for payment method 'dd')
    protected $bankIban = '';           // (string) bank IBAN for international transactions (for payment method 'dd')
    protected $gsaDtaAccountIndex = 1;  // (integer) GSA DTA account index number (for payment method 'dd')
    
    
    
    /***************************************************************************
     *   CONSTRUCTOR
     **************************************************************************/
     
    /**
     * Class constructor
     *
     * @param   string      (optional) payment method ('bt' = bank transfer/on account [default], 'cc' = credit card, 'dd' = direct debit)
     * @return  void
     * @throws  tx_pttools_exception   if a non-allowed payment method string is passed as param
     * @author  Rainer Kuhn <kuhn@punkt.de>
     * @since   2007-03-23
     */
	public function __construct($method='bt') {
        
        $allowedMethods = array ('bt', 'cc', 'dd');
        
        if (!in_array($method, $allowedMethods)) {
            throw new tx_pttools_exception('Non-allowed payment method used', 3);
        }
        
        $this->method = (string)$method;
        
    }
    
    
    
    /***************************************************************************
     *   GENERAL METHODS
     **************************************************************************/
     
    
    
    /***************************************************************************
     *   PROPERTY GETTER/SETTER METHODS
     **************************************************************************/
     
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value: payment method ('bt' = bank transfer/on account, 'cc' = credit card, 'dd' = direct debit)
     * @since   2007-03-27
     */
    public function get_method() {
        return $this->method;
    }

    /**
     * Sets the property value
     *
     * @param   string      property value       
     * @return  void
     * @since   2007-03-27
     */
    public function set_method($method) {
        $this->method = (string) $method;
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  boolean      property value
     * @since   2007-03-27
     */
    public function get_epaymentSuccess() {
        return $this->epaymentSuccess;
    }

    /**
     * Sets the property value
     *
     * @param   boolean      property value       
     * @return  void
     * @since   2007-03-27
     */
    public function set_epaymentSuccess($epaymentSuccess) {
        $this->epaymentSuccess = (boolean) $epaymentSuccess;
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-03-27
     */
    public function get_epaymentTransId() {
        return $this->epaymentTransId;
    }

    /**
     * Sets the property value
     *
     * @param   string      property value       
     * @return  void
     * @since   2007-03-27
     */
    public function set_epaymentTransId($epaymentTransId) {
        $this->epaymentTransId = (string) $epaymentTransId;
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-03-27
     */
    public function get_epaymentRefId() {
        return $this->epaymentRefId;
    }

    /**
     * Sets the property value
     *
     * @param   string      property value       
     * @return  void
     * @since   2007-03-27
     */
    public function set_epaymentRefId($epaymentRefId) {
        $this->epaymentRefId = (string) $epaymentRefId;
    }
    
    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-05-22
     */
    public function get_epaymentShortId() {
        return $this->epaymentShortId;
    }

    /**
     * Sets the property value
     *
     * @param   string      property value       
     * @return  void
     * @since   2007-05-22
     */
    public function set_epaymentShortId($epaymentShortId) {
        $this->epaymentShortId = (string) $epaymentShortId;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-03-27
     */
    public function get_bankAccountHolder() {
        return $this->bankAccountHolder;
    }

    /**
     * Sets the property value
     *
     * @param   string      property value       
     * @return  void
     * @since   2007-03-27
     */
    public function set_bankAccountHolder($bankAccountHolder) {
        $this->bankAccountHolder = (string) $bankAccountHolder;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-03-27
     */
    public function get_bankName() {
        return $this->bankName;
    }

    /**
     * Sets the property value
     *
     * @param   string      property value       
     * @return  void
     * @since   2007-03-27
     */
    public function set_bankName($bankName) {
        $this->bankName = (string) $bankName;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-03-27
     */
    public function get_bankAccountNo() {
        return $this->bankAccountNo;
    }

    /**
     * Sets the property value
     *
     * @param   string      property value       
     * @return  void
     * @since   2007-03-27
     */
    public function set_bankAccountNo($bankAccountNo) {
        $this->bankAccountNo = (string) $bankAccountNo;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-03-27
     */
    public function get_bankCode() {
        return $this->bankCode;
    }

    /**
     * Sets the property value
     *
     * @param   string      property value       
     * @return  void
     * @since   2007-03-27
     */
    public function set_bankCode($bankCode) {
        $this->bankCode = (string) $bankCode;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-03-27
     */
    public function get_bankBic() {
        return $this->bankBic;
    }

    /**
     * Sets the property value
     *
     * @param   string      property value       
     * @return  void
     * @since   2007-03-27
     */
    public function set_bankBic($bankBic) {
        $this->bankBic = (string) $bankBic;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  string      property value
     * @since   2007-03-27
     */
    public function get_bankIban() {
        return $this->bankIban;
    }

    /**
     * Sets the property value
     *
     * @param   string      property value       
     * @return  void
     * @since   2007-03-27
     */
    public function set_bankIban($bankIban) {
        $this->bankIban = (string) $bankIban;
    }

    /**
     * Returns the property value
     *
     * @param   void        
     * @return  integer     property value
     * @since   2007-03-27
     */
    public function get_gsaDtaAccountIndex() {
        return $this->gsaDtaAccountIndex;
    }

    /**
     * Sets the property value
     *
     * @param   integer     property value       
     * @return  void
     * @since   2007-03-27
     */
    public function set_gsaDtaAccountIndex($gsaDtaAccountIndex) {
        $this->gsaDtaAccountIndex = (integer) $gsaDtaAccountIndex;
    }

    
    
} // end class



/*******************************************************************************
 *   TYPO3 XCLASS INCLUSION (for class extension/overriding)
 ******************************************************************************/
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_paymentMethod.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pt_gsauserreg/res/class.tx_ptgsauserreg_paymentMethod.php']);
}

?>
