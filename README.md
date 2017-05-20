![eventGuideLogo](http://kartwal.ayz.pl/EventGuideLogo.png)

# EventGuide - API

This is repo for EventGuide Application Server API

## API METHODS - public methods that are requested by app

1. /register - method for creating user
1. /login - method for login
1. /getEventsList - method for downloading event list
1. /getAllUsers - method for downloading user list
1. /getAllUserEvents - method for downloading particular user event
1. /getAllUserCreatedEvents - method for downloading events created by particular user
1. /qr/:eventId - getting qr for event
1. /event/:id - getting details about selected event
1. /signUserToEvent - method for adding user to particular event
1. /createEvent - method for creating event



## API METHODS TO CALL

Additional info - for most methods user_id is fetched from session, no need to pass it as a value

*** API methods to call ***



### User Registration
method - POST
params - email, password, login
/register


### User Login
method - POST
params - email, password
/login


### Listing all events
method GET
getEventsList

### Get all users
method GET
/getAllUsers

### Listing all events of particual user
method GET
params - user_id
/getAllUserEvents


### Generate QR for event
method GET
params - event_id
/qr

### Listing single event
method GET
params - event_id
/event

### Sign user to event
method POST
params - eventID
signUserToEvent


### Create event
method POST
params - 'event_title', 'event_description', 'event_latitude', 'event_longitude', 'event_start_date', 'event_end_date', 'event_additional_info', 'event_image', 'event_tickets', 'event_card_payment', 'event_max_participants', 'event_accepted', 'event_description_short', 'event_address', 'event_website', 'event_city', 'user_id'
/createEvent


### Listing all events created by particual user
method GET
url /getAllUserCreatedEvents





## Built With

* [slim](https://www.slimframework.com) - Slim is a PHP micro framework that helps you quickly write simple yet powerful web applications and APIs. 


## Authors

* *Kamil Walas* - *Project manager* - [kartwal](https://github.com/kartwal/)
* *Adrianna Gałka* - *Co-worker* - [adaa0704](https://github.com/adaa0704)




