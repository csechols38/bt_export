<?php

//taxonomy import class
class BtTaxonomyImport{

	public $vocabularies;
	private $imported_children = 0;
	private $imported_vocabularies = 0;
	//constuct
	public function __construct($vocabularies){
		$this->vocabularies = $vocabularies;
	}

	//import the taxonomy vocabularies
	public function importTaxonomy($vocabularies = array()){
		$vocabularies = !empty($vocabularies) ? $vocabularies : $this->vocabularies;
		if(!empty($vocabularies)){
			foreach($vocabularies as $machine_name => $values){
				if(is_array($values)){
					foreach($values as $type => $data){
						switch($type){
						case 'parent':
							$exists = taxonomy_vocabulary_machine_name_load($machine_name);
							if(!$exists){
								$this->createTaxonomyVocabulary($data);
							}
							break;
						case 'children':
							if(is_array($values)){
								$parent_vocabulary = taxonomy_vocabulary_machine_name_load($machine_name);
								$vid = $parent_vocabulary->vid;
								foreach($data as $children){
									unset($children->tid);
									$children->vid = $vid;
									$children->parents = $vid;
									$this->createTaxonomyChildren($children);
								}
							}
							break;
						}
					}
				}
			}
		}
	}


	private function createTaxonomyVocabulary($vocab){
		//unset the origional vid since it might exist on this site
		unset($vocab->vid);
		//create the taxonomy vocabulary
		$create_vocabulary = taxonomy_vocabulary_save($vocab);
		if($create_vocabulary){
			$this->imported_vocabularies++;
		}
	}


	private function createTaxonomyChildren($children){
		$create_vocabulary = taxonomy_term_save($children);
		if($create_vocabulary){
			$this->imported_children++;
		}
	}

	public function cleanUp(){
		$results = '<div>';
		$results .= '<div>Created '. $this->imported_vocabularies .' new Taxonomy Vocabulary(s)</div>';
		$results .= '<div>Created '. $this->imported_children .' new Taxonomy Term(s)</div>';
		$results .= '</div>';
		drupal_set_message($results);
	}
}