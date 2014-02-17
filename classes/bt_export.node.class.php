<?php


/**
 * Blue Tree import Content Type Class
 *
 */
class BtImportContentType{

	public $node;
	public $node_settings = array();
	public $import_types;
	public $bundle;
	public $imported = array();
	public $results = array(
		'content_type' => 0,
		'new_fields' => 0,
		'field_instances' => 0,
		'ds_updates' => 0,
		'ds_settings' => 0,
		'field_groups' => 0,
		'view_modes' => 0,
		'custom_fields' => 0,
	);

	//construct
	public function __construct($node){
		module_load_include('class.php', 'bt_export', 'classes/bt_export_ds_view_modes');
		$this->chaneLog = new btExportChanelog();
		$this->node = $node->content_type;
		$import_types = new StdClass();
		$this->import_types = &$import_types;
		$import_types->content_type = FALSE;
		$import_types->field_group = FALSE;
		$import_types->ds = FALSE;
		//import content type
		if(!empty($this->node->instance_properties) && !empty($this->node->field_properties)){
			$this->import_types->content_type = TRUE;
		}
		//import field groups
		if(!empty($this->node->field_groups)){
			$this->import_types->field_group = TRUE;
		}
		//import display suit
		if(!empty($this->node->ds)){
			$this->import_types->ds = TRUE;
		}
		$this->bundle = $this->node->machine_name;
	}

	//format the content type node to be imported
	public function buildNode($node, $create = FALSE){
		$this->node_settings = new StdClass();
		//node object
		$node = !empty($node) ? $node : $this->node;
		if(!empty($node) && !empty($node->settings)){
			foreach($node->settings as $key => $value){
				$this->node_settings->{$key} = $value;
			}
			//set the custom property to TRUE
			$this->node_settings->custom = TRUE;
			//if create is true we create the content type save save it
			if($create && !empty($this->node_settings)){
				//if the content type allready exists, skip the creating portion
				//load the content type
				$exists = node_type_load($this->node_settings->type);
				if(empty($exists)){
					//save the content type
					node_type_save($this->node_settings);
					$this->results['content_type']++;
					$this->chaneLog->chanelUpdateChanelog('chanelUpdateMessage', $this->node_settings->type, 'bundle', $this->node_settings->name, 'created');
				}else{
					$this->chaneLog->chanelUpdateChanelog('chanelUpdateMessage', $this->node_settings->type, 'bundle', $this->node_settings->name, 'upto_date');
				}
			}
		}
		return $this->node_settings;
	}


	//function to save the view mode in the database
	public function saveNodeViewMode($properties = array(), $view_mode, $bundle){
		if(!empty($properties)){
			// Delete previous view_mode configuration (if any)
			db_delete('ds_view_modes')
			->condition('view_mode', $properties->view_mode)
			->execute();
			$new_view_mode = new StdClass;
			$new_view_mode->view_mode = $properties->view_mode;
			$new_view_mode->label = $properties->label;
			$new_view_mode->entities = !empty($properties->entities) ? $properties->entities : NULL;
			//save the view mode to the database
			$save_view_mode = drupal_write_record('ds_view_modes', $new_view_mode);
			switch($save_view_mode){
			case 1:
				$this->chaneLog->chanelUpdateChanelog('chanelUpdateMessage', $bundle, 'view_mode', $properties->label, 'updated');
				break;
			default:
				$this->chaneLog->chanelUpdateChanelog('chanelUpdateMessage', $bundle, 'view_mode', $properties->label, 'failed');
				break;
			}
		}
		//update the bundle settings
		$bundle_settings = field_bundle_settings('node', $bundle);
		if(empty($bundle_settings['view_modes'][$view_mode]['custom_settings'])){
			$bundle_settings['view_modes'][$view_mode]['custom_settings'] = TRUE;
			// Save updated bundle settings.
			$save = field_bundle_settings('node', $bundle, $bundle_settings);
			/* $this->chaneLog->chanelUpdateChanelog('chanelUpdateMessage', $bundle, 'view_mode', $view_mode, 'updated'); */
			$this->results['view_modes']++;
		}
	}



	//function for importing the display suite view modes
	public function importDsViewModes($bundle = NULL, $info = NULL){
		$bundle = !empty($bundle) ? $bundle : $this->bundle;
		$info = !empty($info) ? $info : $this->node->ds->view_modes;
		if(is_object($info)){
			foreach($info as $view_mode => $settings){
				//save the view mode types
				//create the video mode if it doesnt exists
				if(!empty($settings->view_mode_properties) && $view_mode != 'full' && $view_mode != 'search_result'){
					$this->saveNodeViewMode($settings->view_mode_properties, $view_mode, $bundle);
					unset($settings->view_mode_properties);
				}else{
					$this->saveNodeViewMode(NULL, $view_mode, $bundle);
					unset($settings->view_mode_properties);
				}
				foreach($settings as $setting_type => $setting_values){
					if(!empty($settings)){
						$table = '';
						$id = 'node' . '|' . $bundle . '|' . $view_mode;
						$ds_settings = array(
							'id' => $id,
							'entity_type' => 'node',
							'bundle' => $bundle,
							'view_mode' => $view_mode,
							'settings' => array(),
						);
						switch($setting_type){
						case 'ds_layout_settings':
							$ds_settings['settings'] = $setting_values['settings'];
							$ds_settings['layout'] = $setting_values['layout'];
							$table = 'ds_layout_settings';
							break;
						case 'ds_field_settings':
							$ds_settings['settings'] = $settings->ds_field_settings;
							$table = 'ds_field_settings';
							break;
						case 'custom_fields':
							break;
						}
						$exists = $this->checkExistingDsLayout($id, $bundle, $table);
						if(!empty($ds_settings['settings'])){
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
	}


	//fucntion to update ds layout settings
	public function importUpdateDsLayout($field_values, $table, $bundle){
		$field_values['settings'] = serialize($field_values['settings']);
		$update = db_update($table)
		->fields($field_values)
		->condition('id', $field_values['id'], '=')
		->condition('bundle', $field_values['bundle'], '=')
		->execute();
		if($update){
			$result = 'updated';
			$this->results['ds_updates']++;
		}else{
			$result = 'upto_date';
		}
		switch($table){
		case 'ds_layout_settings':
			$this->chaneLog->chanelUpdateChanelog('chanelUpdateMessage', $bundle, 'ds_layout_settings', $field_values['id'], $result);
			break;
		case 'ds_field_settings':
			$this->chaneLog->chanelUpdateChanelog('chanelUpdateMessage', $bundle, 'ds_field_settings', $field_values['id'], $result);
			break;
		}
	}

	//function for inserting layout and field settings into database
	public function importDsLayout($field_values, $table, $bundle){
		$insert = drupal_write_record($table, $field_values);
		if($insert){
			$result = 'created';
			$this->results['ds_settings']++;
		}else{
			$result = 'failed';
		}
		switch($table){
		case 'ds_layout_settings':
			$this->chaneLog->chanelUpdateChanelog('chanelUpdateMessage', $bundle, 'ds_layout_settings', $field_values['id'], $result);
			break;
		case 'ds_field_settings':
			$this->chaneLog->chanelUpdateChanelog('chanelUpdateMessage', $bundle, 'ds_field_settings', $field_values['id'], $result);
			break;
		}
		return $insert;
	}


	//function for checking if a ds layout eists in the database
	public function checkExistingDsLayout($id, $bundle, $table){
		$result = db_select($table, 'ds')
		->fields('ds')
		->condition('id', $id, '=')
		->condition('bundle', $bundle, '=')
		->execute()
		->fetchAssoc();
		return $result;
	}



	//import field groups
	public function importFieldGroups($node = NULL){
		//node object
		$node = !empty($node) ? $node : $this->node;
		//save field groups if available
		if(!empty($node->field_groups)){
			//loop through the field groups types
			foreach($node->field_groups as $type => $settings){
				switch($type){
				case 'form':
					//loop through the form field groups
					foreach($settings as $form_field_group_name => $form_field_group_values){
						//if the field group doesnt exists
						if(!field_group_exists($form_field_group_name, 'node', $this->bundle, 'form')){
							//change export value to NULL because we are importing
							//@see field_group_group_save field_group.api.php
							$form_field_group_values->export_type = NULL;
							//save the field groups
							field_group_group_save($form_field_group_values);
							$this->results['field_groups']++;
							$this->chaneLog->chanelUpdateChanelog('chanelUpdateMessage', $this->node_settings->type, 'field_groups', 'For Form '. $form_field_group_name, 'created');
						}
					}
					break;
				case 'ds':
					if(is_object($settings)){
						//loop trhough the ds field group view modes
						foreach($settings as $view_mode => $field_group_settings){
							//loop trhough the ds field groups per viewmode
							foreach($field_group_settings as $ds_field_group_name => $ds_field_group_values){
								if(!field_group_exists($ds_field_group_name, 'node', $this->bundle, $view_mode)){
									//change export value to NULL because we are importing
									//@see field_group_group_save field_group.api.php
									$ds_field_group_values->export_type = NULL;
									//save the field groups
									field_group_group_save($ds_field_group_values);
									$this->results['field_groups']++;
									$this->chaneLog->chanelUpdateChanelog('chanelUpdateMessage', $this->node_settings->type, 'field_groups', 'For DS '. $ds_field_group_name, 'created');
								}
							}
						}
					}
					break;
				}
			}
		}
	}



	//function for importing the content type fields.
	//we either create new fields and instances if they dont exists
	//or just field instances for the fields that allready exist
	public function importNodeFields($properties, $settings){
		//make sure properties and settings haev values
		if(isset($properties) && isset($settings)){
			//the node machine name
			$node_type = $settings->type;
			//loop through the fields
			foreach($properties as $field_name => $field_values){
				//set the field_bundle type to our bundle
				$field_instance = &$this->node->instance_properties[$field_name];
				//set the bundle attribute to the desired machine name
				$field_instance['bundle'] = $node_type;
				//if the field exists
				if(field_info_field($field_name)){
					//if the field_instance_doesn't exist
					if(!field_info_instance('node', $field_name, $node_type)){
						//create the field instance
						field_create_instance($field_instance);
						$this->results['field_instances']++;
						$this->chaneLog->chanelUpdateChanelog('chanelUpdateMessage', $this->node_settings->type, 'field_instances', $field_name, 'updated');
					}
					//if the field doesnt exist create it
				} else {
					//format our field to be created
					$field = array(
						'field_name' => $field_values['field_name'],
						'type' => $field_values['type'],
						'entity_type' => 'node',
						'settings' => $field_values['settings'],
						'module' => $field_values['module'],
						'cardinality' => $field_values['cardinality'],
						'active' => 1,
					);
					$create_field = field_create_field($field);
					$create_instance = field_create_instance($field_instance);
					$this->results['new_fields']++;
					$this->chaneLog->chanelUpdateChanelog('chanelUpdateMessage', $this->node_settings->type, 'field_new', $field_name, 'created');
				}
			}
		}
	}

	//clean up function to return results
	public function cleanUp(){
		// Clear entity info cache and trigger menu build on next request.
		cache_clear_all('entity_info', 'cache', TRUE);
		$return = $this->chaneLog->cleanUp();
		$results = $this->results;
		$result = '<div>';
		$path = l($this->node_settings->name, '../../admin/structure/types/manage/'. $this->node_settings->type .'', array());
		$result .= '<div>Created '.$results['content_type'].' new Content Types(s). Click '. $path .' to view it.</div>';
		$result .= '<div>Created '.$results['new_fields'].' new Fields.</div>';
		$result .= '<div>Updated '.$results['field_instances'].' Field Instances.</div>';
		$result .= '<div>Updated '.$results['view_modes'].' new view mode instances.</div>';
		$result .= '<div>Updated '.$results['ds_updates'].' Display Suite layouts.</div>';
		$result .= '<div>Created '.$results['ds_settings'].' new Display Suite Layouts and Field Settings.</div>';
		$result .= '<div>Created '.$results['field_groups'].' new Field Groups.</div>';
		$result .= '<div>Created '.$results['custom_fields'].' new Display Suite custom fields.</div>';
		$result .= '</div>';
		drupal_set_message($result);
		return $return;
	}

}