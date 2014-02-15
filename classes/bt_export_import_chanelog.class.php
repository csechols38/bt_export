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
		switch($method){
			case 'updated':
				switch($component){
					case 'view_mode':
						$message = t('Updated View Mode @value', $value);
					break;
					case 'bundle':
						$message = t('Updated Bundle @value', $value);
					break;
					case 'field_instances':
						$message = t('Updated New Field Instance For @value', $value);
					break;
				}
			break;
			case 'created':
				switch($component){
					case 'field_group':
						$message = t('Created Field Group @value', $value);
					break;
					case 'view_mode':
						$message = t('Created View Mode @value', $value);
					break;
					case 'ds_layout_settings':
						$message = t('Created New Display Suite Layout @value', $value);
					break;
					case 'ds_field_settings':
						$message = t('Created New Display Suite Field Settings For Layout @value', $value);
					break;
					case 'field_new':
						$message = t('Created New Field and Instance@value', $value);
					break;
				}
			break;
			case 'failed':
				switch($component){
					case 'ds_layout_settings':
						$message = t('Failed to Create New Display Suite Layout @value', $value);
					break;
					case 'ds_field_settings':
						$message = t('Failed to Create New Display Suite Field Settings For Layout @value', $value);
					break;
				}
			break;
		}
		return $message;
	}
	
	
	public function chanelUpdateMessage($data = array()){
		if(!$data['bundle'] == $this->last_bundle){
			$this->chanelBundleWrapper($data['bundle']);
		}
		$this->chanelog[$data['bundle']][] = array(
			'#type' => 'markup',
			'#markup' => $this->chanelUpdateMethod($data),
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
		dpm($this->chanelog);
	}
}