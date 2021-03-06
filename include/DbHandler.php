<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
require_once '../include/QRlib.php';
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($email, $password, $login) {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO users(user_email, user_password, user_login, api_key, status) values(?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $email, $password_hash, $login, $api_key);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT user_password FROM users WHERE user_email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $stmt = $this->conn->prepare("SELECT user_id from users WHERE user_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT user_login, user_email, api_key, status FROM users WHERE user_email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($name, $email, $api_key, $status);
            $stmt->fetch();
            $user = array();
            $user["name"] = $name;
            $user["email"] = $email;
            $user["api_key"] = $api_key;
            $user["status"] = $status;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT user_id from users WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    /* ------------- `events` table method ------------------ */

    /**
     * Creating new event
     * @param String $user_id user id to whom task belongs to
     * @param String $event event text
     */
    public function createEvent($par1, $par2, $par3, $par4, $par5, $par6, $par7, $par8, $par9, $par10, $par11, $par12, $par13, $par14, $par15, $par16, $user_id) {
        $stmt = $this->conn->prepare("INSERT INTO events(event_title, event_description, event_latitude, event_longitude, event_start_date, event_end_date, event_additional_info, event_image, event_tickets, event_card_payment, event_max_participants, event_accepted, event_description_short, event_address, event_website, event_city, creator_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssssssssssss", $par1, $par2, $par3, $par4, $par5, $par6, $par7, $par8, $par9, $par10, $par11, $par12, $par13, $par14, $par15, $par16, $user_id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
			return 1;
        } else {
            // event failed to create
            return NULL;
        }
    }

	public function getEventQR($eventID){
		$tempDir = "qr_images/";
		$fileName = md5($eventID).'.png';

		$pngAbsoluteFilePath = $tempDir.$fileName;
		$urlRelativeFilePath = $pngAbsoluteFilePath;

		// generating
		QRcode::png($eventID, $pngAbsoluteFilePath);


		return $pngAbsoluteFilePath;
	}

    /**
     * Fetching single event
     * @param String $event_id of the event
     */
    public function getEventDetails($event_id) {

        $stmt = $this->conn->prepare("SELECT e.event_id, e.creator_id, e.event_title, e.event_description, e.event_latitude, e.event_longitude, e.event_start_date, e.event_end_date, e.event_additional_info, e.event_image, e.event_tickets, e.event_card_payment, e.event_max_participants, e.event_accepted, e.event_description_short, e.event_city, e.event_address, e.event_website, COUNT(ue.user_id) AS participants FROM events e LEFT JOIN users_events ue ON e.event_id = ue.event_id WHERE e.event_id = ? GROUP BY e.event_id");
        $stmt->bind_param("i", $event_id);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($event_id, $creator_id, $event_title, $event_description, $event_latitude, $event_longitude, $event_start_date, $event_end_date, $event_additional_info, $event_image, $event_tickets, $event_card_payment, $event_max_participants, $event_accepted, $event_description_short, $event_city, $event_address, $event_website, $participants);
            $stmt->fetch();
            $res["event_id"] = $event_id;
            $res["event_title"] = $event_title;
            $res["event_description"] = $event_description;
            $res["event_latitude"] = $event_latitude;
            $res["event_longitude"] = $event_longitude;
            $res["event_start_date"] = $event_start_date;
            $res["event_end_date"] = $event_end_date;
            $res["event_additional_info"] = $event_additional_info;
            $res["event_image"] = $event_image;
            $res["event_tickets"] = $event_tickets;
            $res["event_card_payment"] = $event_card_payment;
            $res["event_max_participants"] = $event_max_participants;
            $res["event_accepted"] = $event_accepted;
            $res["event_description_short"] = $event_description_short;
            $res["event_city"] = $event_city;
            $res["event_address"] = $event_address;
            $res["event_website"] = $event_website;
            $res["participants"] = $participants;
            $res["creator_id"] = $creator_id;
            $res["qr_code"] = "http://kartwal.ayz.pl/EventGuide_API/v1/".$this->getEventQR($event_id);

            $stmt->close();
            return $res;
        } else {
            return NULL;
        }



    }

    /**
     * Fetching all user events
     * @param String $user_id id of the user
     */
    public function getAllUserEvents($user_id) {
        $stmt = $this->conn->prepare("SELECT e.*, COUNT(ue.user_id) AS participants FROM events e LEFT JOIN users_events ue ON e.event_id = ue.event_id WHERE ue.user_id = ? GROUP BY e.event_id");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }

    /**
     * Fetching all user created events
     * @param String $user_id id of the user
     */
    public function getAllUserCreatedEvents($user_id) {
        $stmt = $this->conn->prepare("SELECT e.*, COUNT(ue.user_id) AS participants FROM events e LEFT JOIN users_events ue ON e.event_id = ue.event_id WHERE e.creator_id = ? GROUP BY e.event_id");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }

    /**
     * Fetching all events
     */
    public function getAllEvents() {
        $stmt = $this->conn->prepare("SELECT e.*, COUNT(ue.user_id) AS participants FROM events e LEFT JOIN users_events ue ON e.event_id = ue.event_id WHERE e.event_accepted = 1 GROUP BY e.event_id");
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }

    public function getAllUsers() {
      $stmt = $this->conn->prepare("SELECT * FROM users WHERE status = 1");
      $stmt->execute();
      $tasks = $stmt->get_result();
      $stmt->close();
      return $tasks;
    }

    /* ------------- `user_events` table method ------------------ */

    /**
     * Function to sign user to event
     * @param String $user_id id of the user
     * @param String $event_id id of the event
     */
    public function signUserToEvent($user_id, $event_id) {

        $stmt = $this->conn->prepare("SELECT id FROM users_events WHERE event_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $event_id, $user_id);
        $result = $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
		$stmt->close();
        if (false === $result) {
				die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
		if($num_rows == 0){
			$stmt2 = $this->conn->prepare("INSERT INTO users_events(user_id, event_id) values(?, ?)");
			$stmt2->bind_param("ii", $user_id, $event_id);
			$result = $stmt2->execute();
			$status = 1;
			if (false === $result) {
				$status = 0;
				die('execute() failed: ' . htmlspecialchars($stmt2->error));
			}
			$stmt2->close();
		}
		else{
			$status = 2;
		}

        return $status;
    }

}

?>
