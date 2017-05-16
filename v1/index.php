<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
/**
 * User Registration
 * url - /register
 * method - POST
 * params - email, password
 */
$app->post('/register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'password'));

            $response = array();

            // reading post params
            // $login = $app->request->post('login');
            $email = $app->request->post('email');
            $password = $app->request->post('password');

            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createUser($email, $password);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('email', 'password'));

            // reading post params
            $email = $app->request()->post('email');
            $password = $app->request()->post('password');
            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($email, $password)) {
                // get the user by email
                $user = $db->getUserByEmail($email);

                if ($user != NULL) {
                    $response["error"] = false;
                    $response['name'] = $user['name'];
                    $response['email'] = $user['email'];
                    $response['apiKey'] = $user['api_key'];
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Login failed. Incorrect credentials';
            }

            echoRespnse(200, $response);
        });

/*
 * ------------------------ METHODS WITH AUTHENTICATION ------------------------
 */

/**
 * Listing all events
 * method GET
 * url /getEvents
 */
$app->get('/getEventsList', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getAllEvents();

            $response["error"] = false;
            $response["events"] = array();

            // looping through result and preparing tasks array
            while ($event = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["event_id"] = $event["event_id"];
                $tmp["event_title"] = $event["event_title"];
                $tmp["event_description_short"] = $event["event_description_short"];
                $tmp["event_start_date"] = $event["event_start_date"];
                $tmp["participants"] = $event["participants"];
                $tmp["event_image"] = $event["event_image"];
                array_push($response["events"], $tmp);
            }

            echoRespnse(200, $response);
        });

/**
 * Listing all events of particual user
 * method GET
 * url /getEvents
 */
$app->get('/getAllUserEvents', 'authenticate', function() {
          global $user_id;
          $response = array();
          $db = new DbHandler();

          // fetching all user tasks
          $result = $db->getAllUserEvents($user_id);

          $response["error"] = false;
          $response["events"] = array();

          // looping through result and preparing tasks array
          while ($event = $result->fetch_assoc()) {
              $tmp = array();
              $tmp["event_id"] = $event["event_id"];
              $tmp["event_title"] = $event["event_title"];
              $tmp["event_description_short"] = $event["event_description_short"];
              $tmp["event_start_date"] = $event["event_start_date"];
              $tmp["participants"] = $event["participants"];
              $tmp["event_image"] = $event["event_image"];
              array_push($response["events"], $tmp);
          }

          echoRespnse(200, $response);
      });

/**
 * Generate QR
 * method GET
 * url /qr
 */
$app->get('/qr/:eventId', function($eventID) {
			$db = new DbHandler();
			return $db->getEventQR($eventID);
        });


/**
 * Listing single event
 * method GET
 * url /event/:id
 */
$app->get('/event/:id', 'authenticate', function($eventID) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetch task
            $result = $db->getEventDetails($eventID);

            if ($result != NULL) {
                $response["error"] = false;
                $response["event_image"] = $result["event_image"];
                $response["event_title"] = $result["event_title"];
                $response["event_description"] = $result["event_description"];
                $response["event_description_short"] = $result["event_description_short"];
                $response["Event ID:"] = $result["event_id"];
                $response["Event Latitude:"] = $result["event_latitude"];
                $response["Event Longitude"] = $result["event_longitude"];
                $response["Event Start Date"] = $result["event_start_date"];
                $response["Event End Date"] = $result["event_end_date"];
                $response["Event Additional Info"] = $result["event_additional_info"];
                $response["Event Tickets"] = $result["event_tickets"];
                $response["Event card payment"] = $result["event_card_payment"];
                $response["Event max participants"] = $result["event_max_participants"];
                $response["event_accepted"] = $result["event_accepted"];
                $response["Event Participants"] = $result["participants"];
                $response["QR Code"] = $result["qr_code"];
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "The requested resource doesn't exists";
                echoRespnse(404, $response);
            }
        });


/**
 * Sign user to event
 * method GET
 * params - eventID
 * url - /signUserToEvent/
 */
$app->post('/signUserToEvent', 'authenticate', function() use ($app)  {

            $response = array();
            global $user_id;

			verifyRequiredParams(array('eventID'));
            $eventID = $app->request->post('eventID');
            $db = new DbHandler();
            $status = $db->signUserToEvent($user_id, $eventID);

            if ($status != NULL && $status == 2) {
                $response["error"] = false;
                $response["message"] = "User already signed to this event";
                $response["eventID"] = $eventID;
                $response["responseCode"] = 2;
                echoRespnse(201, $response);
            } else if ($status != NULL && $status == 1){
                $response["error"] = false;
                $response["message"] = "User signed to event successfully";
                $response["eventID"] = $eventID;
                $response["responseCode"] = 1;
                echoRespnse(201, $response);

            } else {
                $response["error"] = true;
                $response["message"] = "Failed to sign user to event";
                $response["responseCode"] = 0;
                echoRespnse(200, $response);

            }
        });


/**
 * Sign user to event
 * method POST
 * params - name
 * url - /createEvent
 */
$app->post('/createEvent', 'authenticate', function() use ($app) {
            // check for required params

			verifyRequiredParams(array('par1', 'par2'));
            $par1 = $app->request->post('par1');
            $par2 = $app->request->post('par2');

            $response = array();
            global $user_id;

            $db = new DbHandler();
            $status = $db->createEvent($par1, $par2);


            if ($status != NULL) {
                $response["error"] = false;
                $response["message"] = "Event created successfully";
                $response["task_id"] = $user_event_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create Event. Please try again";
                echoRespnse(200, $response);
            }
        });

/**
 * Updating existing task
 * method PUT
 * params task, status
 * url - /tasks/:id
 */
$app->put('/tasks/:id', 'authenticate', function($task_id) use($app) {
            // check for required params
            verifyRequiredParams(array('task', 'status'));

            global $user_id;
            $task = $app->request->put('task');
            $status = $app->request->put('status');

            $db = new DbHandler();
            $response = array();

            // updating task
            $result = $db->updateTask($user_id, $task_id, $task, $status);
            if ($result) {
                // task updated successfully
                $response["error"] = false;
                $response["message"] = "Task updated successfully";
            } else {
                // task failed to update
                $response["error"] = true;
                $response["message"] = "Task failed to update. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Deleting task. Users can delete only their tasks
 * method DELETE
 * url /tasks
 */
$app->delete('/tasks/:id', 'authenticate', function($task_id) use($app) {
            global $user_id;

            $db = new DbHandler();
            $response = array();
            $result = $db->deleteTask($user_id, $task_id);
            if ($result) {
                // task deleted successfully
                $response["error"] = false;
                $response["message"] = "Task deleted succesfully";
            } else {
                // task failed to delete
                $response["error"] = true;
                $response["message"] = "Task failed to delete. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();
?>
