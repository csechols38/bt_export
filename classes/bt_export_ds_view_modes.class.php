<?php

class BtExportDsViewModes extends BtImportContentType{

	public $view_modes;
	public $content_types;
	public $field_groups = array();
	const DS_LAYOUT_TABLE = 'ds_layout_settings';
	const DS_FIELD_SETTINGS_TABLE = 'ds_field_settings';


	public function __construct(){
		$this->chaneLog = new btExportChanelog();
	}

	public function listViewModes(){
		$entity_view_modes = array();
		$view_modes = array();
		$entity_info = entity_get_info('node');
		if(!empty($entity_info['view modes'])){
			$entity_view_modes = array_keys($entity_info['view modes']);
			foreach($entity_view_modes as $delta => $name){
				$view_modes[$name] = $name;
			}
		}
		return $view_modes;
	}


	public function exportViewModes($view_modes = array(), $bundles = array(), &$export){
		$this->view_modes = $view_modes;
		foreach($view_modes as $name => $val){
			$this->view_modes[$name] = array();
			foreach($bundles as $delta => $bundle){
				$this->view_modes[$name][$bundle] = array();
				$this->view_modes[$name][$bundle]['layout_settings'] = ds_get_layout('node', $bundle, $name);
				if($this->view_modes[$name][$bundle]['layout_settings']){
					$this->view_modes[$name][$bundle]['field_settings'] = ds_get_field_settings('node', $bundle, $name);
					$field_groups = field_group_info_groups('node', $bundle, $name);
					if(!empty($field_groups)){
						$this->view_modes[$name][$bundle]['field_groups'] = field_group_info_groups('node', $bundle, $name);
					}
				}else{
					unset($this->view_modes[$name][$bundle]);
				}
			}
		}
		$export->advanced_ds = $this->view_modes;
	}


	public function importDsNodeViewModes($view_modes = array()){
		if(!empty($view_modes)){
			foreach($view_modes as $view_mode_name => $values){
				foreach($values as $bundle => $bundle_values){
					if(node_type_load($bundle)){
						$this->AddViewMode($bundle, $view_mode_name);
						foreach($bundle_values as $ds_type => $ds_type_values){
							$id = 'node' . '|' . $bundle . '|' . $view_mode_name;
							$ds_settings = array(
								'id' => $id,
								'entity_type' => 'node',
								'bundle' => $bundle,
								'view_mode' => $view_mode_name,
							);
							switch($ds_type){
							case 'layout_settings':
								$ds_settings['layout'] = $ds_type_values['layout'];
								$ds_settings['settings'] = serialize($ds_type_values['settings']);
								$table = 'ds_layout_settings';
								break;
							case 'field_settings':
								$ds_settings['settings'] = $ds_type_values;
								$table = 'ds_field_settings';
								break;
							case 'field_groups':
								$this->field_groups[$view_mode_name][$bundle] = $ds_type_values;
								break;
							}
							$exists = $this->checkExistingDsLayout($id, $bundle, $table);
							if(empty($exists)){
								$insert = $this->importDsLayout($ds_settings, $table, $bundle);
							}else{
								$update = $this->importUpdateDsLayout($ds_settings, $table, $bundle);
							}
						}
					}
				}
			}
		}
		$this->ImportDsFieldGroups($this->field_groups);
	}




	public function AddViewMode($bundle, $view_mode){
		$bundle_settings = field_bundle_settings('node', $bundle);
		if(empty($bundle_settings['view_modes'][$view_mode]['custom_settings'])){
			$bundle_settings['view_modes'][$view_mode]['custom_settings'] = TRUE;
			$bundle_settings['view_modes']['default']['custom_settings'] = FALSE;
			// Save updated bundle settings.
			$save = field_bundle_settings('node', $bundle, $bundle_settings);
		}
	}



	private function ImportDsFieldGroups($field_groups){
		if(!empty($field_groups)){
			foreach($field_groups as $view_mode => $bundles){
				if(!empty($bundles)){
					foreach($bundles as $bundle_name => $groups){
						foreach($groups as $field_group_name => $values){
							if(!field_group_exists($field_group_name, 'node', $bundle_name, $view_mode)){
								//change export value to NULL because we are importing
								//@see field_group_group_save field_group.api.php
								$values->export_type = NULL;
								//save the field groups
								field_group_group_save($values);
								$this->results['field_groups']++;
								$this->chaneLog->chanelUpdateChanelog('chanelUpdateMessage', $bundle_name, 'field_groups', $field_group_name);
							}
						}
					}
				}
			}
		}
	}

	
	
	public function cleanUp(){
		$this->dsCleanUp();
		$return = $this->chaneLog->cleanUp();
		return $return;
	}

	protected function dsCleanUp(){
		$results = $this->results;
		$result = '';
		$result .= '<div>Updated '.$results['ds_updates'].' Display Suite layouts.</div>';
		$result .= '<div>Created '.$results['ds_settings'].' new Display Suite Layouts and Field Settings.</div>';
		$result .= '<div>Created '.$results['field_groups'].' new Display Suite Field Groups.</div>';
		$result .= '<div>Created '.$results['view_modes'].' new view modes.</div>';
		drupal_set_message($result);
	}
}