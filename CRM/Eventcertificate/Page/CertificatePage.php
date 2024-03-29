<?php

class CRM_Eventcertificate_Page_CertificatePage extends CRM_Core_Page {
  /**
   * lifted from CRM_Campaign_Form_Petition_Signature
   * @return integer contact Id
   */
  public static function getContactID() {
    $tempID = CRM_Utils_Request::retrieve('cid', 'Positive');
    // force to ignore the authenticated user
    if ($tempID === '0') {
      return $tempID;
    }
    //check if this is a checksum authentication
    $userChecksum = CRM_Utils_Request::retrieve('cs', 'String');
    if ($userChecksum) {
      //check for anonymous user.
      $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($tempID, $userChecksum);
      if ($validUser) {
        return $tempID;
      }
    }
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      return $tempID;
    }
    // check if the user is registered and we have a contact ID
    $session = CRM_Core_Session::singleton();
    return $session->get('userID');
  }

  public function textToDisplay() {
    // Default text to display
    $textToDisplay = array(
      'pdf' => 0,
      'text' => "Your attendance record for this event cannot be found. Please contact us for further assistance",
    );
    // Check for contact id and event id
    $contactId = self::getContactID();
    $eventId = CRM_Utils_Request::retrieve('eid', 'Positive');
    if (!empty($contactId) && !empty($eventId)) {
      try {
        $participant = civicrm_api3('Participant', 'get', array(
          'sequential' => 1,
          'contact_id' => $contactId,
          'event_id' => $eventId,
          // participant status: attended
          'status_id' => 2,
          // Role: Attendee
          'role_id' => 1,
        ));
      }
      catch (CiviCRM_API3_Exception $e) {
        $error = $e->getMessage();
        CRM_Core_Error::debug_log_message(ts('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.eventcertificate')));
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
      CRM_Core_Error::debug_log_message(ts('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.eventcertificate')));
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
      $tokens = array();
      CRM_Utils_Hook::tokens($tokens);
      $categories = array_keys($tokens);
      list($contact) = CRM_Utils_Token::getTokenDetails(
        $params,
        NULL,
        FALSE,
        FALSE,
        NULL,
        $messageToken,
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
      $tokenHtml = CRM_Utils_Token::replaceContactTokens($html_message, $contact[$contactId], TRUE, $messageToken);
      $tokenHtml = self::replaceComponentTokens($tokenHtml, $contact[$contactId], $messageToken, TRUE, TRUE);
      $tokenHtml = CRM_Utils_Token::replaceHookTokens($tokenHtml, $contact[$contactId], $categories, TRUE, FALSE);
      return $tokenHtml;
    }
  }

  /**
   * NOTE This function was removed from core so I am adding it to the extension
   * Find and replace tokens for each component.
   *
   * @param string $str
   *   The string to search.
   * @param array $contact
   *   Associative array of contact properties.
   * @param array $components
   *   A list of tokens that are known to exist in the email body.
   *
   * @param bool $escapeSmarty
   * @param bool $returnEmptyToken
   *
   * @return string
   *   The processed string
   *
   */
  public function replaceComponentTokens(&$str, $contact, $components, $escapeSmarty = FALSE, $returnEmptyToken = TRUE) {
    if (!is_array($components) || empty($contact)) {
      return $str;
    }

    foreach ($components as $name => $tokens) {
      if (!is_array($tokens) || empty($tokens)) {
        continue;
      }

      foreach ($tokens as $token) {
        if (CRM_Utils_Token::token_match($name, $token, $str) && isset($contact[$name . '.' . $token])) {
          CRM_Utils_Token::token_replace($name, $token, $contact[$name . '.' . $token], $str, $escapeSmarty);
        }
        elseif (!$returnEmptyToken) {
          //replacing empty token
          CRM_Utils_Token::token_replace($name, $token, "", $str, $escapeSmarty);
        }
      }
    }
    return $str;
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
      CRM_Core_Error::debug_log_message(ts('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.eventcertificate')));
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
      CRM_Core_Error::debug_log_message(ts('API Error: %1', array(1 => $error, 'domain' => 'com.aghstrategies.eventcertificate')));
    }
    $eventTokens = array();
    foreach ($eventInfo as $key => $value) {
      if ($key == 'start_date' || $key == 'end_date') {
        $eventTokens["event." . $key] = CRM_Utils_Date::customFormat($value);
      }
      else {
        $eventTokens["event." . $key] = $value;
      }
    }
    return $eventTokens;
  }

  public function run() {
    CRM_Utils_System::setTitle(ts('Certificate Page'));
    $textToDisplay = self::textToDisplay();
    // $this->assign('currentTime', date('Y-m-d H:i:s'));
    $this->assign('messageHtml', $textToDisplay['text']);
    // will download the pdf when you go to this url
    if ($textToDisplay['pdf'] == 1) {
      CRM_Utils_PDF_Utils::html2pdf($textToDisplay['text'], "CiviEventCertificate.pdf", FALSE, $formValues);
      CRM_Utils_System::civiExit();
    }
    parent::run();
  }

}
