# This Extension:

Creates a URL which when passed a contact id and event id for a participant record with a role of attendee and status attended will create a pdf and download it.

## Permissions and Checksums

If one is logged in as an admin you can visit the url for any valid participant record and download the pdf. If there is no valid participant record it will display help text.

If one is not logged in as an admin one can only view certificates for the user they are logged in as (or following a checksum for).


The Event certificate created can be edited under message templates - system workflow - Event Certificate -certificate.

It is advisable to set up wkhtmltopdf when using this extension

Page created by extension: https://naswnys.org/civicrm/?page=CiviCRM&q=civicrm%2Feventcertificate&cid={contact.contact_id}&eid={event.event_id}&{contact.checksum}

Things we would like to add:

1. Have the certificate load on the page with a button to download it below instead of automatically downloading.
2. Create a settings page to put text to display if person goes to visit the page that does not have a certificate, and to choose the role type and participant status
