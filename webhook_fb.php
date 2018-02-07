<?php
	require_once(__DIR__ ."/config.php");
	require_once(__DIR__ ."/inc/Facebook_Webhook_Entry.php");
	
	try {
		if(isset($_REQUEST['hub_mode']) && ("subscribe" === $_REQUEST['hub_mode']) && isset($_REQUEST['hub_challenge'])) {
			if(!empty($_REQUEST['hub_verify_token']) && (!defined('FB_WEBHOOK_VERIFY_TOKEN') || !FB_WEBHOOK_VERIFY_TOKEN || ($_REQUEST['hub_verify_token'] !== FB_WEBHOOK_VERIFY_TOKEN))) {
				throw new Exception("Failed to pass verification");
			}
			
			// reply with hub.challenge
			print $_REQUEST['hub_challenge'];
			
			die;
		}
		
		// verify X-Hub-Signature header sent by FB
		if(defined('FB_WEBHOOK_VERIFY_TOKEN') && FB_WEBHOOK_VERIFY_TOKEN) {
			$request_headers = apache_request_headers();
			
			if(!isset($request_headers['X-Hub-Signature'])) {
				throw new Exception("Request header 'X-Hub-Signature' not defined");
			}
			
			if($request_headers['X-Hub-Signature'] != "sha1=". sha1(FB_WEBHOOK_VERIFY_TOKEN)) {
				throw new Exception("Request header 'X-Hub-Signature' with value '". $request_headers['X-Hub-Signature'] ."' doesn't match verify token");
			}
			
			unset($request_headers);
		}
		
		// do stuff
		$request_body = file_get_contents("php://input");
		$data = json_decode($request_body, true);
		
		file_put_contents(TMP_DIR ."webhook_fb.php.". microtime(true) .".txt", print_r([
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
		file_put_contents(TMP_DIR ."webhook_fb.php-error.". microtime(true) .".txt", print_r([
			'get' => $_GET,
			'post' => $_POST,
			'php_input' => file_get_contents("php://input"),
			'error' => $e->getMessage(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
		], true));
		
		die($e->getMessage());
	}