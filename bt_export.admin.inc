<?php


//our import / export form
function _bt_export_form($form, &$form_state){
	$form = array();
	 drupal_add_library('system', 'drupal.collapse');
	 module_load_include('class.php', 'bt_export', 'classes/bt_export_ds_view_modes');
	 
	 
	 //chow the result chanel log
		if(!empty($form_state['chanel_log'])){
			$form['chanel_log'] = array(
				'#type' => 'fieldset',
				'#collapsible' => TRUE,
				'#collapsed' => TRUE,
				'#title' => t('Chanel Log'),
			);
			$form['chanel_log']['prev_results'] = array(
				'#type' => 'markup',
				'#markup' => drupal_render($form_state['chanel_log']),
			);
		}
	
	//import section
	//import vertcle tab
	$form['wrapper'] = array(
		'#type' => 'vertical_tabs',
	);
	$form['imports_wrapper'] = array(
		'#type' => 'fieldset',
		'#title' => t('Import'),
		'#collapsed' => TRUE,
		'#collapsible' => TRUE,
		'#group' => 'wrapper',
	);
	$form['imports_wrapper']['title'] = array(
		'#title' => t('Content Type Name'),
		'#type' => 'textfield',
		//'#required' => TRUE,
	);
	$form['imports_wrapper']['machine_name'] = array(
		'#title' => t('Content Type Machine Name'),
		'#type' => 'textfield',
		//'#required' => TRUE,
	);
	$form['imports_wrapper']['import'] = array(
		'#title' => t('Code'),
		'#type' => 'text_format',
		'#cols' => 60,
		'#rows' => 20,
		'#group' => 'wrapper',
		'#default_value' => '',
		'#format' => 'plain_text',
		//'#required' => TRUE,
	);
	$form['imports_wrapper']['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Import'),
	);
	//end import section



	//export section

	//export verticle tab
	$form['export_wrapper'] = array(
		'#type' => 'fieldset',
		'#title' => t('Export'),
		'#collapsed' => TRUE,
		'#collapsible' => TRUE,
		'#group' => 'wrapper',
	);
	$form['export_wrapper']['content_types'] = array(
		'#type' => 'fieldset',
		'#title' => t('Content Types'),
		'#collapsed' => TRUE,
		'#collapsible' => TRUE,
	);

	//taxonomy export
	$form['export_wrapper']['taxonomy_terms'] = array(
		'#type' => 'fieldset',
		'#title' => t('Taxonomy'),
		'#collapsed' => TRUE,
		'#collapsible' => TRUE,
	);
	$taxonomy_vocabularies = bt_export_taxonomy_handler();
	$form['export_wrapper']['taxonomy_terms']['taxonomy_terms'] = array(
		'#title' => t('Select Taxonomy Vocabularies'),
		'#type' => 'checkboxes',
		'#options' => $taxonomy_vocabularies,
		'#default_value' => array(),
		'#multiple' => TRUE,
	);

	//end taxonomy
	
	
	
	//advanced display suit
	//get all the entity view modes
	$entity_info = new BtExportDsViewModes();
	$view_modes = $entity_info->listViewModes();
	//taxonomy export
	$form['export_wrapper']['ds_view_modes_fieldsets'] = array(
		'#type' => 'fieldset',
		'#title' => t('Advanced Display Suite'),
		'#collapsed' => TRUE,
		'#collapsible' => TRUE,
	);
	$form['export_wrapper']['ds_view_modes_fieldsets']['ds_view_modes'] = array(
		'#title' => t('View Mode'),
		'#type' => 'checkboxes',
		'#options' => $view_modes,
		'#multiple' => TRUE,
		'#description' => t('Export all ds settings for each content type using the select view mode.'),
	);
	
	

	//get all the content types
	$content_types = node_type_get_types();
	$content_types = array_keys($content_types);
	$select_content_types = array(
		'_none' => '-Select Content Type-',
	);
	foreach($content_types as $delta => $value){
		$select_content_types[$value] = $value;
	}
	//select content type
	$form['export_wrapper']['content_types']['export_content_type'] = array(
		'#type' => 'select',
		'#title' => t('Select Content Type'),
		'#options' => $select_content_types,
	);
	//export compenent options depending on which module are available
	$export_components = _bt_export_export_components();
	//export component types
	$form['export_wrapper']['content_types']['export_types'] = array(
		'#title' => t('Export components'),
		'#type' => 'checkboxes',
		'#options' => $export_components,
		'#multiple' => TRUE,
	);
	
	$form['export_wrapper']['content_types']['ds_custom_fields'] = array(
		'#type' => 'checkboxes',
		'#title' => t('Display Suite Custom Fields'),
		'#options' => array(
			'yes' => t('Yes'),
		),
		'#states' => array(
				'visible' => array(   // action to take.
					':input[name="export_types[ds]"]' => array('checked' => TRUE),
				),
			),
	);
	
	
	//our field group options per module
	$field_group_options = array();
	if(!empty($export_components['field_group'])){
		$field_group_options['form_field_groups'] = t('Form Field Groups');
		if(!empty($export_components['ds'])){
			$field_group_options['ds_field_groups'] = t('Display Suite Field Groups');
		}
	}
	//if we have field group options
	//create our field group checkboxes
	if(!empty($field_group_options)){
		$form['export_wrapper']['content_types']['export_field_groups_type'] = array(
			'#title' => t('Field Group Types'),
			'#type' => 'checkboxes',
			'#options' => $field_group_options,
			'#multiple' => TRUE,
			'#states' => array(
				'visible' => array(   // action to take.
					':input[name="export_types[field_group]"]' => array('checked' => TRUE),
				),
			),
		);
	}
	$form['export_wrapper']['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Export'),
	);
	if(!empty($form_state['values']['export_content_type'])){
		$form['export_wrapper']['code_placeholder'] = array(
			'#type' => 'text_format',
			'#cols' => 60,
			'#rows' => 20,
			'#title' => t('Export Code'),
			'#default_value' => !empty($form_state['code']) ? $form_state['code'] : NULL,
			'#prefix' => '<div id="bt-export-select">',
			'#suffix' => '</div>',
			'#format' => 'plain_text',
		);
	}
	//end export section
	$form['#validate'] = array('_bt_export_validate');
	$form['#submit'] = array('_bt_export_submit');

	return $form;
}