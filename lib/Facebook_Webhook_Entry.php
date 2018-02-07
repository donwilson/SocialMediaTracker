<?php
	abstract class Facebook_Webhook {
		protected $id = false;
		protected $uid = false;
		protected $time = false;
		protected $entry;
		
		public function __construct($entry) {
			$this->entry = $entry;
			
			// https://developers.facebook.com/docs/graph-api/webhooks#callback
			if(!empty($this->entry['id'])) {
				$this->id = $this->entry['id'];
			}
			
			if(!empty($this->entry['uid'])) {
				$this->id = $this->entry['uid'];
			}
			
			if(!empty($this->entry['time'])) {
				$this->time = $this->entry['time'];
			}
			
			if(!empty($this->entry['changes'])) {
				foreach($this->entry['changes'] as $change) {
					$field = $change['field'];
					$verb = (isset($change['verb'])?$change['verb']:false);
					$value = (isset($change['value'])?$change['value']:false);
					
					if(method_exists($this, $field)) {
						$this->{$field}($verb, $value);
					} else {
						$this->fieldNotHandled($field, $verb, $value);
					}
				}
			} elseif(!empty($this->entry['changed_fields'])) {
				$changes = $this->entry['changed_fields'];
				
				foreach($this->entry['changed_fields'] as $field) {
					$verb = false;
					$value = false;
					
					if(method_exists($this, $field)) {
						$this->{$field}($verb, $value);
					} else {
						$this->fieldNotHandled($field, $verb, $value);
					}
				}
			}
		}
		
		protected function fieldNotHandled($field, $verb=false, $value=false) {
			// do nothing
			file_put_contents(TMP_DIR . microtime(true) ."-Facebook_Webhook.fieldNotHandled.txt", print_r([
				'field' => $field,
				'verb' => $verb,
				'value' => $value,
				//'entry' => $this->entry,
			], true), FILE_APPEND);
		}
	}
	
	
	// Webhook Entry Class for User
	// https://developers.facebook.com/docs/graph-api/webhooks/reference/user/
	class Facebook_Webhook_User extends Facebook_Webhook {
		public function friends($verb=false, $value=false) {
			$available_verbs = ["add", "block", "edit", "edited", "delete", "follow", "hide", "mute", "remove", "unblock", "unhide", "update"];
			
			$sentence = "Unknown sentence";
			
			if(false !== $verb) {
				if(in_array($verb, $available_verbs)) {
					$sentence = "[". $this->id ."] did '". $verb ."' to [". $value['uid'] ."]";
				} else {
					$sentence = "Unknown verb '". $verb ."' committed from [". $this->id ."] to [". $value['uid'] ."]";
				}
			}
			
			file_put_contents(TMP_DIR . microtime(true) ."-Facebook_Webhook_User.friends.txt", print_r([
				'sentence' => $sentence,
				'id' => $this->id,
				'verb' => $verb,
				'value' => $value,
				//'entry' => $this->entry,
			], true), FILE_APPEND);
		}
	}
	
	