<?php

class CRM_Eventcertificate_Page_CertificatePage extends CRM_Core_Page {

  public function textToDisplay() {
    // Default text
    $textToDisplay = array(
      'pdf' => 0,
      'text' => "not enough information to process your request",
    );
    // Check for contact id and event id
    if (!empty($_GET["cid"]) && !empty($_GET["eid"])) {
      $contactId = $_GET["cid"];
      $eventId = $_GET["eid"];
      try {
        $participant = civicrm_api3('Participant', 'get', array(
          'sequential' => 1,
          'contact_id' => $_GET["cid"],
          'event_id' => $_GET["eid"],
          'status_id' => "Registered",
          'role_id' => "Attendee",
        ));
      }
      catch (CiviCRM_API3_Exception $e) {
        $error = $e->getMessage();
        CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.eventcertificate')));
      }
      // check that the contact is a registered attendee for the event
      if (!empty($participant['values'][0])) {
        $textToDisplay['pdf']  = 1;
        $textToDisplay['text'] = self::getHTMLwithTokens($contactId);
      }
    }
    return $textToDisplay;
  }

  public function getHTMLwithTokens($contactId) {
    try {
      $result = civicrm_api3('MessageTemplate', 'getsingle', array(
        'sequential' => 1,
        'id' => 65,
      ));
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.eventcertificate')));
    }
    $html_message = $result['msg_html'];

    $messageToken = CRM_Utils_Token::getTokens($result['msg_html']);
    $returnProperties = array();
    if (isset($messageToken['contact'])) {
      foreach ($messageToken['contact'] as $key => $value) {
        $returnProperties[$value] = 1;
      }
    }
    $categories = array();
    $formValues = $result['values'][0];
    $params = array('contact_id' => $contactId);
    list($contact) = CRM_Utils_Token::getTokenDetails(
      $params,
      $returnProperties,
      TRUE,
      TRUE,
      NULL,
      $messageToken,
      'CRM_Contact_Form_Task_PDFLetterCommon'
    );
    $tokenHtml = CRM_Utils_Token::replaceContactTokens($html_message, $contact[$contactId], TRUE, $messageToken);
    return $tokenHtml;
  }

  public function run() {
    CRM_Utils_System::setTitle(ts('Certificate Page'));
    $textToDisplay = self::textToDisplay();
    $this->assign('currentTime', date('Y-m-d H:i:s'));
    $this->assign('messageHtml', $textToDisplay['text']);
    if ($textToDisplay['pdf'] == 1) {
      CRM_Utils_PDF_Utils::html2pdf($textToDisplay['text'], "CiviEventCertificate.pdf", FALSE, $formValues);
    }
    parent::run();
  }

}
