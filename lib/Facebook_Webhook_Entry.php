<?php
	abstract class Facebook_Webhook {
		protected $id = false;
		protected $entry;
		
		public function __construct($entry) {
			$this->entry = $entry;
			
			if(!empty($this->entry['id'])) {
				$this->id = $this->entry['id'];
			}
			
			$changes = [];
			
			if(!empty($this->entry['changes'])) {
				$changes = $this->entry['changes'];
			} elseif(!empty($this->entry['changed_fields'])) {
				$changes = $this->entry['changed_fields'];
			}
			
			if(!empty($changes)) {
				foreach($changes as $change) {
					$field = $change['field'];
					$verb = (isset($change['verb'])?$change['verb']:false);
					$value = (isset($change['value'])?$change['value']:false);
					
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
			file_put_contents(TMP_DIR ."Facebook_Webhook.fieldNotHandled.". microtime(true) .".txt", print_r([
				'field' => $field,
				'verb' => $verb,
				'value' => $value,
				'entry' => $this->entry,
			], true), FILE_APPEND);
		}
	}
	
	
	// Webhook User Object
	class Facebook_Webhook_User extends Facebook_Webhook {
		public function friends($verb=false, $value=false) {
			file_put_contents(TMP_DIR ."Facebook_Webhook_User.friends.". microtime(true) .".txt", print_r([
				'id' => $this->id,
				'verb' => $verb,
				'value' => $value,
				'entry' => $this->entry,
			], true), FILE_APPEND);
		}
	}
	
	