Page created by extension:

http://wpmaster/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Feventcertificate

Things to do:

1. Pass event id and and contact id thru url
2. create a button on the page to download the template as a pdf (https://github.com/civicrm/civicrm-core/blob/c1cc5247b974a534b24fb4e9315d82e276fecea6/CRM/Contact/Form/Task/PDFLetterCommon.php#L402)
3. on install create a system workflow message template to be used by this extension
4. write logic for when you see certificate and message for if you dont
