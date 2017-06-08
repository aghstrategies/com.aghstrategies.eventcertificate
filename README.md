# This Extension:

Creates a URL which when passed a contact id and event id for a participant record with a role of attendee and status registered will create a pdf and download it.

If there is no valid participant record it will display help text.

The Event certificate created can be edited under message templates - system workflow - Event Certificate -certificate.

It is advisable to set up wkhtmltopdf when using this extension

Page created by extension: http://wpmaster/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Feventcertificate&cid=203&eid=2

Things we would like to add:

1. Have the certificate load on the page with a button to download it below instead of automatically downloading.
2. Create a settings page to put text to display if person goes to visit the page that does not have a certificate, and to choose the role type and participant status
