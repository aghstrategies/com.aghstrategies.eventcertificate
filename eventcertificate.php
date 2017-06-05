<?php

require_once 'eventcertificate.civix.php';

function eventcertificate_checkIfExsisting($entity, $params) {
  $recordFound = FALSE;
  try {
    $result = civicrm_api3($entity, 'get', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    $error = $e->getMessage();
    CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.eventcertificate')));
  }
  if ($result['count'] > 0 && !empty($result['values'][0]['id'])) {
    $recordFound = $result['values'][0]['id'];
  }
  return $recordFound;
}

function eventcertificate_civicrm_buildForm($formName, &$form) {
  // print_r($formName); die();
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function eventcertificate_civicrm_config(&$config) {
  _eventcertificate_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function eventcertificate_civicrm_xmlMenu(&$files) {
  _eventcertificate_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function eventcertificate_civicrm_install() {
  // Check if there is a PDF Format for Event Certificates already
  $PDFFormatExisting = eventcertificate_checkIfExsisting('OptionValue', array('label' => "Event Certificate", 'name' => 'Event Certificate'));
  if ($PDFFormatExisting > 0) {
    $PDFFormatToUse = $PDFFormatExisting;
  }
  else {
    // Create PDF Format for the Event Certificate to use
    try {
      $NewPDFFormat = civicrm_api3('OptionGroup', 'getsingle', array(
        'sequential' => 1,
        'return' => array("id"),
        'name' => "pdf_format",
        'api.OptionValue.create' => array(
          'option_group_id' => "\$value.id",
          'label' => "Event Certificate",
          'value' => "{\"paper_size\":\"letter\",\"stationery\":null,\"orientation\":\"landscape\",\"metric\":\"in\",\"margin_top\":0,\"margin_bottom\":0,\"margin_left\":0,\"margin_right\":0}",
          'name' => 'Event Certificate',
          'weight' => 1,
          'description' => 'PDF Format ID generated by com.aghstrategies.eventcertificate',
          'is_active' => 1,
        ),
      ));
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.eventcertificate')));
    }
    $PDFFormatToUse = $NewPDFFormat['api.OptionValue.create']['id'];
  }
  $optionGroupEC = eventcertificate_checkIfExsisting('OptionGroup', array('name' => 'msg_tpl_workflow_event_certificate'));
  $optionValue = eventcertificate_checkIfExsisting('OptionValue', array('name' => 'event_certificate_cert'));
  if ($optionGroupEC == NULL && $optionValue == NULL) {
    // Create Option Group for Message Template for Event Certificate
    try {
      $optionGroup = civicrm_api3('OptionGroup', 'create', array(
        'sequential' => 1,
        'name' => "msg_tpl_workflow_event_certificate",
        'title' => "Message Template Workflow for Event Certificates",
        'description' => "Generated by com.aghstragegies.eventcertificate",
        'is_reserved' => 1,
        'is_active' => 1,
        'api.OptionValue.create' => array(
          'option_group_id' => "msg_tpl_workflow_event_certificate",
          'label' => "Event Certificate Creation",
          'value' => 1,
          'name' => "event_certificate_cert",
          'is_default' => 0,
          'weight' => 1,
          'is_optgroup' => 0,
          'is_reserved' => 0,
          'is_active' => 1,
          'description' => "generated by com.aghstrategies.eventcertificate",
        ),
      ));
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.eventcertificate')));
    }
    if (!empty($optionGroup['values'][0]['api.OptionValue.create']['values'][0]['id'])) {
      $optionValue = $optionGroup['values'][0]['api.OptionValue.create']['values'][0]['id'];
    }
  }
  $messageTemplate = eventcertificate_checkIfExsisting('MessageTemplate', array('msg_title' => 'Event Certificate - Certificate'));
  if ($messageTemplate == NULL) {
    $html = '
    <div style="padding:40px;">
      <h1 style="text-align:center"><img alt="" src="http://wpmaster/wp-content/uploads/civicrm/persist/contribute/images/NASWLogo.png" style="width: 389px; height: 129px;" /></h1>
      <h1 style="text-align: center;"><strong>Certificate of Attendance</strong></h1>
      <p style="text-align: center;">This certificate certifies that the individual named below has successfully completed participation in this program and is hereby awarded the contact hours/CEU&rsquo;s stated herein</p>
      <p style="text-align: left;"><strong>Program Name:</strong> {event.title}</p>
      <p><strong>Presenter(s): </strong>Presenter Name</p>
      <p><strong>Program Date(s):&nbsp; </strong>Program Date</p>
      <p><strong>Instruction Method:</strong> Method</p>
      <p><strong>Total Contact Hours/CEUs Awarded:</strong> # CE Contact Hours</p>
      <p><big><strong>Participant Name:</strong> {contact.display_name}</big></p>
      <p><big><strong>License:</strong> ###</big></p>
      <p><strong>PROVIDER:</strong> National Association of Social Workers - New York State Chapter</p>
      <p><em>NASW &ndash; New York State Chapter is recognized by the New York State Education Department&rsquo;s State Board for Mental Health Practitioners as an approved provider of continuing education for licensed social workers: Provider ID 0014; </em></p>
      <img alt="" src="http://wpmaster/wp-content/uploads/civicrm/persist/contribute/images/cestamp.png" style="float: right; width: 194px; height: 163px;" />
    </div>
  ';
    $msgTemplateParams = array(
      'msg_title' => "Event Certificate - Certificate",
      'msg_subject' => "{ts}Event Certificate{/ts}\\n",
      'msg_text' => "Text Version of Certificate TBD",
      'msg_html' => $html,
      'is_active' => 1,
      'workflow_id' => $optionValue,
      'is_sms' => 0,
      'is_default' => 1,
      'is_reserved' => 0,
      'pdf_format_id' => $PDFFormatToUse,
    );
    // for reserved message templates have to make two
    try {
      $messageTemplate1 = civicrm_api3('MessageTemplate', 'create', $msgTemplateParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.eventcertificate')));
    }
    $msgTemplateParams['is_default'] = 0;
    $msgTemplateParams['is_reserved'] = 1;
    try {
      $messageTemplate2 = civicrm_api3('MessageTemplate', 'create', $msgTemplateParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.eventcertificate')));
    }
  }
  _eventcertificate_civix_civicrm_install();

}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function eventcertificate_civicrm_postInstall() {
  _eventcertificate_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function eventcertificate_civicrm_uninstall() {
  _eventcertificate_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function eventcertificate_civicrm_enable() {
  _eventcertificate_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function eventcertificate_civicrm_disable() {
  _eventcertificate_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function eventcertificate_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _eventcertificate_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function eventcertificate_civicrm_managed(&$entities) {
  _eventcertificate_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function eventcertificate_civicrm_caseTypes(&$caseTypes) {
  _eventcertificate_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function eventcertificate_civicrm_angularModules(&$angularModules) {
  _eventcertificate_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function eventcertificate_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _eventcertificate_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function eventcertificate_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function eventcertificate_civicrm_navigationMenu(&$menu) {
  _eventcertificate_civix_insert_navigation_menu($menu, NULL, array(
    'label' => ts('The Page', array('domain' => 'com.aghstrategies.eventcertificate')),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _eventcertificate_civix_navigationMenu($menu);
} // */
