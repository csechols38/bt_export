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
		$all_view_modes = ctools_export_crud_load_all('ds_view_modes');
		foreach($view_modes as $name => $val){
			$this->view_modes[$name] = array();
			foreach($bundles as $delta => $bundle){
				$this->view_modes[$name][$bundle] = array();
				$this->view_modes[$name][$bundle]['layout_settings'] = ds_get_layout('node', $bundle, $name);
				$view_mode_settings = !empty($all_view_modes[$name]) ? $all_view_modes[$name] : '';
				if(!empty($view_mode_settings)){
					$this->view_modes[$name][$bundle]['view_mode_properties'] = $view_mode_settings;
				}
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
						//$this->AddViewMode($bundle, $view_mode_name);
						if(!empty($bundle_values['view_mode_properties']) && $view_mode_name != 'full'){
							$this->saveNodeViewMode($bundle_values['view_mode_properties'], $view_mode_name, $bundle);
							unset($bundle_values['view_mode_properties']);
						}else{
							$this->saveNodeViewMode(NULL, $view_mode_name, $bundle);
						}
						foreach($bundle_values as $ds_type => $ds_type_values){
							$id = 'node' . '|' . $bundle . '|' . $view_mode_name;
							$ds_settings = array(
								'id' => $id,
								'entity_type' => 'node',
								'bundle' => $bundle,
								'view_mode' => $view_mode_name,
								'settings' => array(),
							);
							switch($ds_type){
							case 'layout_settings':
								$ds_settings['layout'] = $ds_type_values['layout'];
								$ds_settings['settings'] = $ds_type_values['settings'];
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




	//function for importing custom fields
	public function importCustomDsFields($fields = array(), $bundle = ''){
		if(!empty($fields)){
			foreach($fields as $machine_name => $data){
				$field = new StdClass();
				$field->field = $machine_name;
				$field->label = $data['title'];
				$field->field_type = $data['field_type'];
				$field->properties = !empty($data['properties']) ? $data['properties'] : array();
				$field->entities = !empty($data['entities']) ? $data['entities'] : array('node' => 'node');
				$field->ui_limit = implode("\n", $data['ui_limit']);

				//delete current row
				db_delete('ds_fields')
				->condition('field', $field->field)
				->execute();
				//save the code field
				if($import = drupal_write_record('ds_fields', $field)){
					$this->chaneLog->chanelUpdateChanelog('chanelUpdateMessage', $bundle, 'custom_fields', $field->label, 'created');
					$this->results['custom_fields']++;
				}
			}
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
		// Clear entity info cache and trigger menu build on next request.
		cache_clear_all('entity_info', 'cache', TRUE);
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
		$result .= '<div>Created '.$results['custom_fields'].' new Display Suite custom fields.</div>';
		drupal_set_message($result);
	}
}