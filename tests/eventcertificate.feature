Feature: Visiting the eventcertificate page

Scenario: Visting the event certificate page as a event participant

When one visits https://naswnys.org/civicrm/?page=CiviCRM&q=civicrm%2Feventcertificate&cid={contact.contact_id}&eid=111&{contact.checksum}
And the cid is a civicrm contact Id
And that contact is registered for the event of id eid
And that participant record has a status of attended and a role of attendee
And there is a checksum in the url (cs=)
Then the page should show the message template called "event certificate - certificate"
And that message template should be popluated with the information for that contact and event
And a pdf of the message template should be downloaded.

Scenario: Visting the event certificate page as an Administrator

When one visits https://naswnys.org/civicrm/?page=CiviCRM&q=civicrm%2Feventcertificate&cid={contact.contact_id}&eid=111
And the cid is a civicrm contact Id
And that contact is registered for the event of id eid
And that participant record has a status of attended and a role of attendee
Then the page should show the message template called "event certificate - certificate"
And that message template should be popluated with the information for that contact and event
And a pdf of the message template should be downloaded.
