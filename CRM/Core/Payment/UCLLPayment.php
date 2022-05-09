<?php

use CRM_ucll_payment_ExtensionUtil as E;

class CRM_Core_Payment_UCLLPayment extends CRM_Core_Payment {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * Mode of operation: live or test
   *
   * @var object
   * @static
   */
  static protected $_mode = NULL;

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return void
   */
  function __construct($mode, &$paymentProcessor) {
    $this->_paymentProcessor = $paymentProcessor;
  }

  /**
   * Singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return object
   * @static
   *
   */
  static function &singleton($mode, &$paymentProcessor) {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new CRM_Core_Payment_UCLLPayment($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig() {
    $error = [];
    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = E::ts('The "API client_id" is not set for this payment processor.', ['domain' => 'be.ucll.ucllpayment']);
    }
    if (empty($this->_paymentProcessor['password'])) {
      $error[] = E::ts('The "API client_secret" is not set for this payment processor.', ['domain' => 'be.ucll.ucllpayment']);
    }
    if (empty($this->_paymentProcessor['subject'])) {
      $error[] = E::ts('The "Merchant ID" is not set for this payment processor.', ['domain' => 'be.ucll.ucllpayment']);
    }
    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  /**
   * Sets appropriate parameters for checking out UCLL Payment
   *
   * @param array $params name value pair of contribution data
   *
   * @return void
   * @access public
   *
   * @throws \Exception
   */
  function doPayment(&$params, $component = 'contribute') {
    // Check transaction type.
    if ($component != 'contribute' && $component != 'event') {
      CRM_Core_Error::createError(ts('Component is invalid'));
    }
    // Start building our parameters.
    $contribution = $this->getUCLLApi('Contribution', $params['contributionID']);
    if (isset($contribution['contribution_campaign_id']) && !empty($contribution['contribution_campaign_id'])) {
      $campaign = $this->getUCLLApi('Campaign', $contribution['contribution_campaign_id']);
    }
    $UCLLPaymentCollectionParams['verificationId'] = (isset($contribution['invoice_id'])) ? $contribution['invoice_id'] : '';
    $UCLLPaymentCollectionParams['amount'] = $params['amount'];
    $UCLLPaymentCollectionParams['destination'] = $this->_paymentProcessor['user_name'];
    $UCLLPaymentCollectionParams['nameEn'] = $params['first_name'] . ' ' . $params['last_name'];
    $UCLLPaymentCollectionParams['nameNl'] = $UCLLPaymentCollectionParams['nameEn'];
    $UCLLPaymentCollectionParams['type'] = (isset($campaign['external_identifier'])) ? $campaign['external_identifier'] : $this->_paymentProcessor['subject'];
    $UCLLPaymentCollectionParams['webhookUrl'] = $this->getNotifyUrl();
    $UCLLPaymentCollectionParams['successUrl'] = $this->getReturnSuccessUrl($params['qfKey']);
    if ($component == 'event') {
      $UCLLPaymentCollectionParams['cancelUrl'] = $this->getCancelUrl($params['qfKey'], $params['participantID']);
    }
    else {
      $UCLLPaymentCollectionParams['cancelUrl'] = $this->getCancelUrl($params['qfKey'], NULL);
    }
    // Allow further manipulation of the parameters via custom hooks.
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $UCLLPaymentCollectionParams);
    // Create item and get hash from API. (app/pay/item)
    $hash = $this->createItem($UCLLPaymentCollectionParams);
    // Redirect the user to the payment url with hash. (/cart/hash)
    if (isset($hash['shoppingCartHash'])) {
      $redirect = $this->_paymentProcessor['url_site'] . '/cart/hash/' . $hash['shoppingCartHash'];
      $redirect = $redirect . '?return=' . urlencode($UCLLPaymentCollectionParams['successUrl']);
      CRM_Utils_System::redirect($redirect);
    }
    else {
      $message = E::ts('Something went wrong with the payment provider connection.', ['domain' => 'be.ucll.ucllpayment']);
      CRM_Core_Session::setStatus(print_r($message, TRUE), '', 'error');
      CRM_Utils_System::redirect($UCLLPaymentCollectionParams['cancelUrl']);
    }
  }

  /**
   * New callback function for payment notifications as of Civi 4.2
   */
  public function handlePaymentNotification() {
    require_once 'UCLLPaymentIPN.php';
    CRM_Core_Payment_UCLLPaymentIPN::main();
  }

  /**
   * Get oAuth token from API. (/oauth/token)
   *
   * @return array
   */
  protected function getOauthToken() {
    $curl = curl_init();
    $auth_data = [
      'grant_type' => 'client_credentials',
    ];
    curl_setopt($curl, CURLOPT_URL, $this->_paymentProcessor['url_api'] . '/oauth/token');
    curl_setopt($curl, CURLOPT_USERPWD, $this->_paymentProcessor['user_name'] . ":" . $this->_paymentProcessor['password']);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $auth_data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    if (!$result) {
      $message = E::ts("Connection failure", ['domain' => 'be.ucll.ucllpayment']);
      CRM_Core_Session::setStatus($message, '', 'alert');
    }
    curl_close($curl);
    return json_decode($result, TRUE);
  }

  /**
   * Create item and get hash from API. (/pay/item)
   *
   * @param array $params contribution data
   *
   * @return array
   */
  protected function createItem($params) {
    $curl = curl_init();
    $result = "";
    if (isset($this->getOauthToken()['access_token'])) {
      $headers[] = "Authorization: Bearer " . $this->getOauthToken()['access_token'];
      $headers[] = "Content-Type: application/json; charset=utf-8";
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_URL, $this->_paymentProcessor['url_api'] . '/app/pay/item');
      // curl_setopt($curl, CURLOPT_USERPWD, $this->_paymentProcessor['user_name'] . ":" . $this->_paymentProcessor['password']);
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      $result = curl_exec($curl);
      if (!$result) {
        $message = E::ts("Connection failure", ['domain' => 'be.ucll.ucllpayment']);
        CRM_Core_Session::setStatus($message, '', 'alert');
      }
      curl_close($curl);
    }
    return json_decode($result, TRUE);
  }

  /**
   * Get CiviCRM Entity API information.
   *
   * @param string $id entity type
   * @param integer $id entity id
   *
   * @return array
   */
  protected function getUCLLApi($type, $id) {
    $result = [];
    if (isset($id)) {
      try {
        $result = civicrm_api3($type, 'getsingle', [
          'id' => $id,
        ]);
      } catch (\CiviCRM_API3_Exception $e) {
        \Civi::log()->debug("UCLLPayment.php: " . $e->getMessage());
      }
    }
    return $result;
  }

}
