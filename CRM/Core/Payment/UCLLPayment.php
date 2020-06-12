<?php

use CRM_Ucllpayment_ExtensionUtil as E;

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
    $config = CRM_Core_Config::singleton();
    $error = [];
    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = E::ts('The "API client_id" is not set in the Administer CiviCRM Payment Processor.', ['domain' => 'be.ucll.ucllpayment']);
    }
    if (empty($this->_paymentProcessor['password'])) {
      $error[] = E::ts('The "API client_secret" is not set in the Administer CiviCRM Payment Processor.', ['domain' => 'be.ucll.ucllpayment']);
    }
    if (empty($this->_paymentProcessor['subject'])) {
      $error[] = E::ts('The "Merchant ID" is not set in the Administer CiviCRM Payment Processor.', ['domain' => 'be.ucll.ucllpayment']);
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
  function doTransferCheckout(&$params, $component) {

    // Check transaction type.
    if ($component != 'contribute' && $component != 'event') {
      CRM_Core_Error::fatal(ts('Component is invalid'));
    }

    // Log parameters.
    $message = print_r($params, TRUE);
    // CRM_Core_Session::setStatus($message, '', 'info');

    // Start building our parameters.
    // @todo get secure id? use it as reference to finalize payment in webhook.
    $UCLLPaymentCollectionParams['id'] = $params['contributionID'];
    $UCLLPaymentCollectionParams['amount'] = $params['amount'];
    $UCLLPaymentCollectionParams['destination'] = $this->_paymentProcessor['user_name'];
    $UCLLPaymentCollectionParams['nameEn'] = $params['first_name'] . ' ' . $params['last_name'];
    $UCLLPaymentCollectionParams['nameNl'] = $UCLLPaymentCollectionParams['nameEn'];
    $UCLLPaymentCollectionParams['type'] = $this->_paymentProcessor['subject'];
    $UCLLPaymentCollectionParams['subtype'] = $component;
    $UCLLPaymentCollectionParams['webhookUrl'] = $this->getNotifyUrl();
    $UCLLPaymentCollectionParams['succesUrl'] = $this->getReturnSuccessUrl($params['qfKey']);
    if ($component == 'event') {
      $UCLLPaymentCollectionParams['cancelUrl'] = $this->getCancelUrl($params['qfKey'], $params['participantID']);
    }
    else {
      $UCLLPaymentCollectionParams['cancelUrl'] = $this->getCancelUrl($params['qfKey'], NULL);
    }

    // Allow further manipulation of the parameters via custom hooks.
    CRM_Utils_Hook::alterPaymentProcessorParams($this, $params, $UCLLPaymentCollectionParams);
    // CRM_Core_Session::setStatus(print_r($UCLLPaymentCollectionParams, TRUE), '', 'info');

    // Create item and get hash from API. (/pay/item)
    $hash = $this->createItem($UCLLPaymentCollectionParams);
    // CRM_Core_Session::setStatus(print_r($hash, TRUE), '', 'info');

    // @todo Redirect the user to the payment url with hash. (/cart/hash)
    CRM_Utils_System::redirect($this->_paymentProcessor['url_site'] . '/cart/hash/' . $hash['shoppingCartHash']);

    // @todo check if this is still needed?
    //exit();
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
    $headers[] = "Authorization: Bearer " . $this->getOauthToken()['access_token'];;
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
    return json_decode($result, TRUE);
  }

}
