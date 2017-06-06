Page created by extension:

http://wpmaster/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Feventcertificate&cid=203&eid=2

Things to do:

1. create a button on the page to download the template as a pdf (https://github.com/civicrm/civicrm-core/blob/c1cc5247b974a534b24fb4e9315d82e276fecea6/CRM/Contact/Form/Task/PDFLetterCommon.php#L402) or just make it happen automatically by uncommenting code in run?
3. make event dates format appropriately: https://github.com/civicrm/civicrm-core/blob/master/api/v3/Event.php#L263
4. settings page to put text to display if person goes to visit the page that does not have a certificate, role type and participant status
