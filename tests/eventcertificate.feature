Feature: Visiting the eventcertificate page

Scenario: A Member with an Expired membership goes to renew

When one visits {http://wpmaster/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Feventcertificate&cid=44&eid=1}
And the cid is a civicrm contact Id
And that contact is logged registered for the event of id eid
And that participant record has a status of registreed and a role of attendee
Then the page should show the message template called ""
And that message template should be popluated with the information for that contact and event
And a pdf of the message template should be downloaded.
