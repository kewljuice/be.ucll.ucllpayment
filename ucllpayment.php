<?php

require_once 'ucllpayment.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function ucllpayment_civicrm_config(&$config) {
  _ucllpayment_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function ucllpayment_civicrm_xmlMenu(&$files) {
  _ucllpayment_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function ucllpayment_civicrm_install() {
  _ucllpayment_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function ucllpayment_civicrm_postInstall() {
  _ucllpayment_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function ucllpayment_civicrm_uninstall() {
  _ucllpayment_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function ucllpayment_civicrm_enable() {
  _ucllpayment_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function ucllpayment_civicrm_disable() {
  _ucllpayment_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function ucllpayment_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _ucllpayment_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function ucllpayment_civicrm_managed(&$entities) {
  $entities[] = [
    'module' => 'be.ucll.ucllpayment',
    'name' => 'UCLL Pay',
    'entity' => 'PaymentProcessorType',
    'params' => [
      'version' => 3,
      'name' => 'ucll_payment',
      'title' => 'UCLL Pay',
      'description' => 'UCLL payment option.',
      'class_name' => 'Payment_UCLLPayment',
      'user_name_label' => 'API client_id',
      'password_label' => 'API client_secret',
      'subject_label' => 'Merchant ID',
      'url_site_default' => 'https://pay.ucll.be',
      'url_api_default' => 'https://papi.ucll.be',
      'url_site_test_default' => 'https://pay.q.ucll.be',
      'url_api_test_default' => 'https://papi.q.ucll.be',
      'billing_mode' => 4,
      'is_recur' => 0,
      'payment_type' => 1,
    ],
  ];
  _ucllpayment_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function ucllpayment_civicrm_caseTypes(&$caseTypes) {
  _ucllpayment_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function ucllpayment_civicrm_angularModules(&$angularModules) {
  _ucllpayment_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function ucllpayment_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _ucllpayment_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function ucllpayment_civicrm_entityTypes(&$entityTypes) {
  _ucllpayment_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_themes().
 */
function ucllpayment_civicrm_themes(&$themes) {
  _ucllpayment_civix_civicrm_themes($themes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 *
function ucllpayment_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 *
function ucllpayment_civicrm_navigationMenu(&$menu) {
  _ucllpayment_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _ucllpayment_civix_navigationMenu($menu);
} // */
