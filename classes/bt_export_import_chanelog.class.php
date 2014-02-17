<?php


class btExportChanelog{
	
	public $last_bundle;
	public $chanelog;
	public $fields = array();
	public $instances = array();
	public $bundles = array();
	public $field_groups = array();
	public $ds_layout_settings = array();
	public $ds_field_settings = array();
	public $view_modes = array();
	
	public function chanelUpdateChanelog($function = '', $bundle = '', $component = '', $value = '', $method = 'created'){
		$data = array();
		$data['bundle'] = $bundle;
		$data['method'] = $method;
		$data['component'] = $component;
		$data['value'] = $value;
		if($function){
			call_user_func_array(array($this, $function), array($data));
		}
	}
	
	
	private function chanelUpdateMethod($data){
		$method = $data['method'];
		$component = $data['component'];
		$value = array('@value' => $data['value']);
		$message = '';
		$status = '';
		switch($method){
			case 'updated':
				$status = 'messages status';
				switch($component){
					case 'view_mode':
						$message = t('Updated or created View Mode <strong>@value</strong>', $value);
					break;
					case 'field_instances':
						$message = t('Updated new field instance for <strong>@value</strong>', $value);
					break;
					case 'ds_layout_settings':
						$message = t('Updated Display Suite layout <strong>@value</strong>', $value);
					break;
					case 'ds_field_settings':
						$message = t('Updated Display Suite field settings for layout <strong>@value</strong>', $value);
					break;
					case 'bundle':
						$message = t('Updated Bundle <strong>@value</strong>', $value);
					break;
				}
			break;
			case 'created':
				$status = 'messages status';
				switch($component){
					case 'field_groups':
						$message = t('Created Field Group <strong>@value</strong>', $value);
					break;
					case 'view_mode':
						$message = t('Created view mode <strong>@value</strong>', $value);
					break;
					case 'ds_layout_settings':
						$message = t('Created new Display Suite layout <strong>@value</strong>', $value);
					break;
					case 'ds_field_settings':
						$message = t('Created new Display Suite field settings for layout <strong>@value</strong>', $value);
					break;
					case 'field_new':
						$message = t('Created new field and instance <strong>@value</strong>', $value);
					break;
					case 'bundle':
						$message = t('Created Bundle <strong>@value</strong>', $value);
					break;
				}
			break;
			case 'failed':
				$status = 'messages error';
				switch($component){
					case 'ds_layout_settings':
						$message = t('Failed to create new Display Suite layout <strong>@value</strong>', $value);
					break;
					case 'ds_field_settings':
						$message = t('Failed to Create new Display Suite field settings for layout <strong>@value</strong>', $value);
					break;
					case 'view_mode':
						$message = t('Failed creating view mode <strong>@value</strong>', $value);
					break;
				}
			break;
			case 'upto_date':
				$status = 'messages status';
				switch($component){
					case 'ds_layout_settings':
						$message = t('Display Suite layout <strong>@value</strong> already up to date', $value);
					break;
					case 'ds_field_settings':
						$message = t('Display Suite field settings for <strong>@value</strong> already up to date', $value);
					break;
					case 'view_mode':
						$message = t('View mode <strong>@value</strong> already up to date', $value);
					break;
					case 'bundle':
						$message = t('Bundle <strong>@value</strong> already exists', $value);
					break;
				}
			break;
		}
		return array('message' => $message, 'status' => $status);
	}
	
	
	public function chanelUpdateMessage($data = array()){
		if($data['bundle'] != $this->last_bundle && empty($this->chanelog[$data['bundle']])){
			$this->chanelBundleWrapper($data['bundle']);
			$this->last_bundle = $data['bundle'];
		}
		$message = $this->chanelUpdateMethod($data);
		$this->chanelog[$data['bundle']][] = array(
			'#type' => 'markup',
			'#markup' => $message['message'],
			'#prefix' => '<div class="bt-export-results '. $message['status'] .' ">',
			'#suffix' => '</div>',
		);
	}
	
	public function chanelBundleWrapper($bundle){
		$this->chanelog[$bundle] = array(
			'#type' => 'fieldset',
			'#title' => strtoupper($bundle),
			'#collapsed' => TRUE,
			'#collapsible' => TRUE,
		);
	}
	
	public function cleanUp(){
		return $this->chanelog;
	}
}