<?php

/**
 * @author mail@dezio.de
 * @copyright 2014
 * 
 * Changelog:
 * 
 * 11.11.2014
 * 
 * > Rev0
 * - File created.
 * - Interface created.
 * - Interface implemented with tutorial writing.
 * 
 * > Rev1
 * - Changed stdClass answer object to ApiMessage (class created).
 * - Automatically detect if packet is error (true on type="Error").
 * - Refactored some variables to consts.
 * - Added try/catch with $api->sendError($ex->__toString()) for client-side error handling.
 * - The CheckLegal-Callback gets now the input array (command, sid, arguments..) instead of sid only.
 * 
 * > Rev2
 * - Added api_test.php
 */



ERROR_REPORTING(E_ALL);

class Session {
	/**
	 * Session::encapsulateSession()
	 * 
	 * @param mixed $sid
	 * @return
	 */
	public static function encapsulateSession($sid) {
		if (empty($sid)) return;
		$sFile = "sessions/" . $sid . ".txt";
		if (file_exists($sFile)) {
			return (object)json_decode(file_get_contents($sFile));
		} // if end
		else {
			$arr = array();
			self::save($arr, $sid);
			return (object)$arr;
		} // else end
	}

	/**
	 * Session::save()
	 * 
	 * @param mixed $arr
	 * @param mixed $sid
	 * @return
	 */
	public static function save($arr, $sid) {
		if (empty($sid)) return;
		$sFile = "sessions/" . $sid . ".txt";
		file_put_contents($sFile, json_encode($arr));
	}
}

interface IApiManager {
	function registerApiCommand($command, $isLegalCallback, $callback);
	function callApiCommand($command, $messageInput);
	function sendMessage($message);
	function sendError($errMessage);
	function sendArray($array);
	function handleRequest($inputArray);
}

class ApiMessage {
	var $Error;
	var $Type;
	var $Message;

	public function __construct($type = null, $message = null) {
		$this->Error = $type == ApiManager::TYPE_ERROR ? ApiManager::IS_ERROR : ApiManager::IS_NO_ERROR;
		$this->Type = $type;
		$this->Message = $message;
	}

	public function toJson() {
		return json_encode($this);
	}
}

class ApiManager implements IApiManager {
	// Constants
	const CB_ARRAY_KEY = "callback";
	const LEGALCHECK_CB_ARRAY_KEY = "legal";

	const ERR_PERM_DENIED = "Permission denied.";
	const ERR_CMD_NOT_FOUND = "Command not found: %s";
	const ERR_REGISTER_FAIL = "Couldn't register Api-Command: %s already exists.";

	const TYPE_ERROR = "Error";
	const TYPE_MESSAGE = "Message";
	const TYPE_ARRAY = "Array";

	const IS_NO_ERROR = false;
	const IS_ERROR = true;


	/**
	 * $apiCommands
	 * Contains all ApiCommand elements.
	 * One array element contains an array with the keys
	 * CB_ARRAY_KEY for command callback,
	 * LEGALCHECK_CB_ARRAY_KEY for command 
	 * 
	 * @access private
	 */
	private $apiCommands = array();

	/**
	 * ApiManager::isValidSession()
	 * 
	 * @param mixed $sessionId
	 * @return
	 */
	public function isValidSession($sessionId) {
		return true;
		// Todo: Check if the session is valid
	}

	/**
	 * ApiManager::registerApiCommand() registers an API-Command
	 * 
	 * @param string $command
	 * @param callback $isLegalCallCallback
	 * @param callback $callback
	 * @return
	 */
	public function registerApiCommand($command, $isLegalCallCallback, $callback) {
		// Check for existance
		if (key_exists($command, $this->apiCommands)) {
			throw new Exception(sprintf(self::ERR_REGISTER_FAIL, $command));
		} // if end
        
		// Register/Save
		$this->apiCommands[$command] = array(self::CB_ARRAY_KEY => $callback, self::LEGALCHECK_CB_ARRAY_KEY => $isLegalCallCallback);
	}

	/**
	 * ApiManager::callApiCommand()
	 * 
	 * @param mixed $command
	 * @param mixed $objMessage
	 * @return
	 */
	public function callApiCommand($command, $objMessage) {
		// Check if the command is registered.
		if (!is_string($command) || !key_exists($command, $this->apiCommands)) {
			$this->sendError(sprintf(self::ERR_CMD_NOT_FOUND, $command));
		} // if end
		else {
			// gather callback information.
			$callback    = $this->apiCommands[$command][self::CB_ARRAY_KEY];
			$legal       = $this->apiCommands[$command][self::LEGALCHECK_CB_ARRAY_KEY];
			// Check if legal access.
			$isLegal     = call_user_func_array($legal, array($objMessage));
			if ($isLegal) {
				// call the command callback, parameter: $objMessage
				call_user_func_array($callback, array($objMessage));
				// For future implementation: Session::save($objMessage->session, $objMessage->sid);
			} // if end
			else 
                $this->sendError(self::ERR_PERM_DENIED);
		} // else end
		die();
	}

	/**
	 * ApiManager::sendMessage()
	 * 
	 * @param mixed $message
	 * @return
	 */
	public function sendMessage($message) {
		$this->send(self::TYPE_MESSAGE, $message);
	}

	/**
	 * ApiManager::sendArray()
	 * 
	 * @param mixed $array
	 * @return void
	 */
	public function sendArray($array) {
		$this->send(self::TYPE_ARRAY, $array);
	}

	/**
	 * ApiManager::sendError()
	 * 
	 * @param mixed $errMessage
	 * @return
	 */
	public function sendError($errMessage) {
		$this->send(self::TYPE_ERROR, $errMessage);
	}

	/**
	 * ApiManager::send()
	 * 
	 * @param bool $isError
	 * @param string $type
	 * @param mixed $message
	 * @return void
	 */
	private function send($type, $message) {
		$obj = new ApiMessage($type, $message);
		echo $obj->toJson();
	}

	/**
	 * ApiManager::handleRequest() 
	 * handles the request and calls the command.
	 * 
	 * @param mixed $inputArray
	 * @return
	 */
	public function handleRequest($inputArray) {
		$inputArray = (object)$inputArray;
        if(!isset($inputArray->command) || !is_string($inputArray->command)) {
            throw new Exception(sprintf(self::ERR_CMD_NOT_FOUND, ""));
        } // if end
		// Get command
		$command = $inputArray->command;
		// Get session id
		$sessionId = isset($inputArray->sid) ? $inputArray->sid : "";
		// Put session id to $inputArray
		// to be sure it's minimum set to empty
		$inputArray->sid = $sessionId;
		// For future session binding:
		// $inputArray->session = Session::encapsulateSession($sessionId);
		$this->callApiCommand($command, $inputArray);
	}
}

// EXAMPLE:
$api = new ApiManager;

$alwaysTrue = function ($inputObject) {
    return true;
};

$alwaysFalse = function ($inputObject) {
    return false;
};

$api->registerApiCommand("ping", $alwaysTrue, function ($inputObject) use ($api) { 
    $api->sendMessage("pong!"); 
});

try {
    $api->handleRequest($_GET);
} catch(Exception $ex) {
    $api->sendError($ex->__toString());
}
?>