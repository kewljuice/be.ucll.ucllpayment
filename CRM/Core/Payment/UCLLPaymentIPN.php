<?php

class CRM_Core_Payment_UCLLPaymentIPN extends CRM_Core_Payment_BaseIPN {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * mode of operation: live or test
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
    parent::__construct();

    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
  }

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   *
   * @return object
   * @static
   */
  static function &singleton($mode, $component, &$paymentProcessor) {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Core_Payment_UCLLPaymentIPN($mode,
        $paymentProcessor);
    }
    return self::$_singleton;
  }

  /**
   * This method is handles the response that will be invoked
   */
  static function main() {
    // Fetch POST variables.
    $variables = json_decode(file_get_contents('php://input'), TRUE);
    if (isset($variables['verificationId']) && isset($variables['status'])) {
      $params = [
        'invoice_id' => $variables['verificationId'],
        'is_test' => ['IN' => [0, 1]],
      ];
      // Check if given verificationId matches a contribution.
      try {
        $check = civicrm_api3('Contribution', 'getSingle', $params);
      } catch (\CiviCRM_API3_Exception $e) {
        \Civi::log()->debug("UCLLPaymentIPN.php: " . $e->getMessage());
        print print_r($e->getMessage());
      }
      if (!$check['is_error'] && isset($check['contact_id'])) {
        $params = [];
        $params['id'] = $check['contribution_id'];
        // Add itemId as trxn_id.
        if (isset($variables['itemId'])) {
          $params['trxn_id'] = $variables['itemId'];
        }
        // Contribution status.
        switch ($variables['status']) {
          case 'success':
            if (!$check['contribution_status_id'] != 1) {
              $params['contribution_status_id'] = 1;
              try {
                civicrm_api3('Contribution', 'completeTransaction', $params);
              } catch (\CiviCRM_API3_Exception $e) {
                \Civi::log()->debug("UCLLPaymentIPN.php: " . $e->getMessage());
                print print_r($e->getMessage());
              }
            }
            break;
          default:
            if (!$check['contribution_status_id'] != 3) {
              $params['contribution_status_id'] = 3;
              try {
                civicrm_api3('Contribution', 'create', $params);
              } catch (\CiviCRM_API3_Exception $e) {
                \Civi::log()->debug("UCLLPaymentIPN.php: " . $e->getMessage());
                print print_r($e->getMessage());
              }
            }
        }
      }
    }
  }

}
