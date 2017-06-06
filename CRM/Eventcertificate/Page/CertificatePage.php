<?php

class CRM_Eventcertificate_Page_CertificatePage extends CRM_Core_Page {

  public function textToDisplay() {
    // Default text to display
    $textToDisplay = array(
      'pdf' => 0,
      'text' => "not enough information to process your request please contact XXX",
    );
    // Check for contact id and event id
    if (!empty($_GET["cid"]) && !empty($_GET["eid"])) {
      $contactId = $_GET["cid"];
      $eventId = $_GET["eid"];
      try {
        $participant = civicrm_api3('Participant', 'get', array(
          'sequential' => 1,
          'contact_id' => $contactId,
          'event_id' => $eventId,
          // participant status: registered
          'status_id' => 1,
          // Role: Attendee
          'role_id' => 1,
        ));
      }
      catch (CiviCRM_API3_Exception $e) {
        $error = $e->getMessage();
        CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.eventcertificate')));
      }
      // check that the contact is a registered attendee for the event if so make pdf
      if (!empty($participant['values'][0])) {
        $textToDisplay['pdf']  = 1;
        $textToDisplay['text'] = self::getHTMLwithTokens($contactId, $eventId);
      }
    }
    return $textToDisplay;
  }

  public function getHTMLwithTokens($contactId, $eventId) {
    try {
      // Get message template created by the extension
      $result = civicrm_api3('MessageTemplate', 'get', array(
        'msg_title' => "Event Certificate - Certificate",
        'is_reserved' => 0,
        'sequential' => 1,
      ));
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.eventcertificate')));
    }
    if (!empty($result['values'][0]['msg_html'])) {
      $html_message = $result['values'][0]['msg_html'];
      $messageToken = CRM_Utils_Token::getTokens($html_message);
      $returnProperties = array();
      $otherReturnProperties = '';
      if (isset($messageToken['contact'])) {
        foreach ($messageToken['contact'] as $key => $value) {
          $returnProperties[$value] = 1;
        }
      }
      $participantTokens = $eventTokens = $categories = array();
      $formValues = NULL;
      if (!empty($result['values'][0])) {
        $formValues = $result['values'][0];
      }
      $params = array(
        'contact_id' => $contactId,
        'event_id' => $eventId,
      );

      list($contact) = CRM_Utils_Token::getTokenDetails(
        $params,
        NULL,
        FALSE,
        FALSE,
        NULL,
        array(),
        'CRM_Event_BAO_Participant'
      );
      if (!empty($messageToken['event'])) {
        $eventTokens = self::getEventTokenInfo($eventId, $messageToken['event']);
      }
      if (!empty($messageToken['participant'])) {
        $participantTokens = self::getParticipantTokenInfo($eventId, $contactId, $messageToken['participant']);
      }
      foreach ($contact as $id => $contactTokens) {
        $contact[$id] = array_merge($contactTokens, $eventTokens, $participantTokens);
      }
      $html_message = CRM_Utils_Token::replaceContactTokens($html_message, $contact[$contactId], TRUE, $messageToken);
      $tokenHtml = CRM_Utils_Token::replaceComponentTokens($html_message, $contact[$contactId], $messageToken, TRUE, TRUE);
      return $tokenHtml;
    }
  }

  public function getParticipantTokenInfo($eventId, $contactId, $fields) {
    $participantParams = array(
      'sequential' => 1,
      'contact_id' => $contactId,
      'event_id' => $eventId,
    );
    if (!empty($_GET["pid"])) {
      $participantParams['id'] = $_GET["pid"];
    }
    foreach ($fields as $key => $field) {
      $participantParams['return'][] = $field;
    }
    try {
      $participantInfo = civicrm_api3('Participant', 'get', $participantParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.eventcertificate')));
    }
    $participantTokens = array();
    foreach ($participantInfo['values'][0] as $key => $value) {
      $participantTokens["participant." . $key] = $value;
    }
    return $participantTokens;
  }

  public function getEventTokenInfo($eventId, $fields) {
    $eventParams = array(
      'id' => $eventId,
    );
    foreach ($fields as $key => $field) {
      $eventParams['return'][] = $field;
    }
    try {
      $eventInfo = civicrm_api3('Event', 'getsingle', $eventParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(t('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.eventcertificate')));
    }
    $eventTokens = array();
    foreach ($eventInfo as $key => $value) {
      $eventTokens["event." . $key] = $value;
    }
    return $eventTokens;
  }

  public function run() {
    CRM_Utils_System::setTitle(ts('Certificate Page'));
    $textToDisplay = self::textToDisplay();
    $this->assign('currentTime', date('Y-m-d H:i:s'));
    $this->assign('messageHtml', $textToDisplay['text']);
    // will download the pdf when you go to this url
    if ($textToDisplay['pdf'] == 1) {
      CRM_Utils_PDF_Utils::html2pdf($textToDisplay['text'], "CiviEventCertificate.pdf", FALSE, $formValues);
    }
    parent::run();
  }

}
