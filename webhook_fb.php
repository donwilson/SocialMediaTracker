<?php
	require_once(__DIR__ ."/config.php");
	require_once(__DIR__ ."/lib/Facebook_Webhook_Entry.php");
	
	try {
		if(isset($_REQUEST['hub_mode']) && ("subscribe" === $_REQUEST['hub_mode']) && isset($_REQUEST['hub_challenge'])) {
			if(!empty($_REQUEST['hub_verify_token']) && (!defined('FB_WEBHOOK_VERIFY_TOKEN') || !FB_WEBHOOK_VERIFY_TOKEN || ($_REQUEST['hub_verify_token'] !== FB_WEBHOOK_VERIFY_TOKEN))) {
				throw new Exception("Failed to pass verification");
			}
			
			// reply with hub.challenge
			print $_REQUEST['hub_challenge'];
			
			die;
		}
		
		// request body
		$request_body = file_get_contents("php://input");
		
		
		// verify X-Hub-Signature header sent by FB
		if(defined('FB_APP_SECRET') && FB_APP_SECRET) {
			/*$request_headers = apache_request_headers();
			
			if(!isset($request_headers['X-Hub-Signature'])) {
				throw new Exception("Request header 'X-Hub-Signature' not defined");
			}*/
			
			list($req_algo, $req_hash) = explode("=", getenv("HTTP_X_HUB_SIGNATURE"), 2);
			
			if(!in_array($req_algo, hash_algos(), TRUE)) {
				throw new Exception("Requester's hash algorithm '". $req_algo ."' is not supported.");
			}
			
			if(!hash_equals(hash_hmac($req_algo, $request_body, FB_APP_SECRET), $req_hash)) {
				throw new Exception("Request header 'X-Hub-Signature' with value '". $req_hash ."' doesn't match verify token");
			}
			
			//unset($request_headers);
			unset($req_algo, $req_hash);
		}
		
		// do stuff
		$data = @json_decode($request_body, true);
		
		file_put_contents(TMP_DIR . microtime(true) ."-webhook_fb.php.txt", print_r([
			'request_body' => $request_body,
			'data' => $data,
		], true));
		
		if(!isset($data['object'])) {
			throw new Exception("'object' not defined in JSON payload");
		}
		
		$object = strtolower(trim($data['object']));
		
		if(empty($data['entry']) || !is_array($data['entry'])) {
			throw new Exception("'entry' not defined or empty in JSON payload");
		}
		
		$entry_handler = false;
		
		switch(strtolower(trim($data['object']))) {
			case 'user':
				$entry_handler = "Facebook_Webhook_User";
			break;
		}
		
		if(empty($entry_handler)) {
			throw new Exception("JSON payload not handled because object '". $object ."' not designed to be tracked with this webhook");
		}
		
		if(!class_exists($entry_handler)) {
			throw new Exception("Designated entry handler '". $entry_handler ."' class does not exist");
		}
		
		foreach($data['entry'] as $entry) {
			$handler = new $entry_handler($entry);
		}
	} catch(Exception $e) {
		file_put_contents(TMP_DIR . microtime(true) ."-webhook_fb.php-error.txt", print_r([
			'get' => $_GET,
			'post' => $_POST,
			'php_input' => file_get_contents("php://input"),
			'error' => $e->getMessage(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
		], true));
		
		die($e->getMessage());
	}