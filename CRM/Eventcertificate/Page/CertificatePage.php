<?php

class CRM_Eventcertificate_Page_CertificatePage extends CRM_Core_Page {
  public function getHTMLwithTokens() {
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
    $contactId = 203;
    // CRM_Contact_Form_Task_PDFLetterComon::formatMessage($html_message);
    // print_r($html_message); die();

    $messageToken = CRM_Utils_Token::getTokens($result['msg_html']);
    $returnProperties = array();
    if (isset($messageToken['contact'])) {
      foreach ($messageToken['contact'] as $key => $value) {
        $returnProperties[$value] = 1;
      }
    }
    $categories = array();
    $formValues = $result['values'][0];
    $params = array('contact_id' => 203);
    list($contact) = CRM_Utils_Token::getTokenDetails($params,
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
    $tokenHtml = self::getHTMLwithTokens();
    // CRM_Utils_PDF_Utils::html2pdf($html, "CiviLetter.pdf", FALSE, $formValues);
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('Certificate Page'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));
    $this->assign('messageHtml', $tokenHtml);

    parent::run();
  }

}
