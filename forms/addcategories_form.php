<?php

include_once $CFG->libdir.'/formslib.php';
include_once $CFG->dirroot.'/mod/magtest/locallib.php';

/**
* Form to add or update categories
*
*/

class Category_Form extends moodleform{
	
	var $cmd;
	var $magtest;
	var $howmany;
	
	function __construct(&$magtest, $cmd, $howmany, $action){
		$this->cmd = $cmd;
		$this->magtest = $magtest;
		$this->howmany = $howmany;
		parent::__construct($action);
	}
	
	function definition(){
		global $CFG,$catid,$DB,$id;
		
		$mform = $this->_form;

		$mform->addElement('header', 'header0', get_string($this->cmd.'categories', 'magtest'));

		$mform->addElement('hidden', 'catid');
		$mform->setType('catid', PARAM_INT);

		$mform->addElement('hidden', 'howmany');
		$mform->setType('howmany', PARAM_INT);
		$mform->setDefault('howmany', $this->howmany);

		$mform->addElement('hidden', 'what');
		$mform->setDefault('what', 'do'.$this->cmd);
		$mform->setType('what', PARAM_TEXT);
		
        if ($this->cmd == 'add'){
            $mform->addElement('hidden', 'cmd', 'add');
			$mform->setType('cmd', PARAM_TEXT);

		    $categories = magtest_get_categories($this->magtest->id);
		    $categoryids = array_keys($categories);
            for ($i = 0 ; $i < $this->howmany ; $i++){
                $num = $i+1;

	            $mform->addElement('static', 'header_'.$num, '<h2>'.get_string('category', 'magtest')." ".$num.'</h2>');
	
	            $mform->addElement('text', 'catname_'.$num, get_string('name'), '', array('size' => '120', 'maxlength' => '255'));
	            $mform->setType('catname_'.$num, PARAM_CLEANHTML);
	           
	            $symboloptions = magtest_get_symbols($magtest, $renderingpathbase);
	      
	            $mform->addElement('selectgroups','catsymbol_'.$num, get_string('symbol','mod_magtest'),$symboloptions);
	              
	            $mod_context = get_context_instance(CONTEXT_MODULE,$id); 
	            $catdesc_editor = $mform->addElement('editor', 'catdescription_'.$num, get_string('description'),null,array('maxfiles' => EDITOR_UNLIMITED_FILES,
	                        'noclean' => true, 'context' =>  $mod_context));
	                
	            $catresult_editor  = $mform->addElement('editor', 'catresult_'.$num, get_string('categoryresult', 'magtest'),null,array('maxfiles' => EDITOR_UNLIMITED_FILES,
	                        'noclean' => true, 'context' =>  $mod_context));
	            
	            if ($this->magtest->usemakegroups){
	                $mform->addElement('text', 'outputgroupname_'.$num, get_string('outputgroupname', 'magtest'), '', array('size' => '128', 'maxlength' => '255'));
	            	$mform->setType('outputgroupname_'.$num, PARAM_CLEANHTML);
	                $mform->addElement('text', 'outputgroupdesc_'.$num, get_string('outputgroupdesc', 'magtest'), '', array('size' => '255', 'maxlength' => '255'));
	            	$mform->setType('outputgroupdesc_'.$num, PARAM_CLEANHTML);
	            }
	        }
	    } else if ($this->cmd == 'update') {

            $mform->addElement('hidden', 'cmd', 'update');
            $mform->setType('cmd', PARAM_ALPHA);
           
            //LOAD CURRENT CAT
            $category = $DB->get_record('magtest_category', array('id' => $catid));
            if(empty($category)) {
                print_error('errorinvalidcategory', 'magtest');
            }
            
            $mform->addElement('hidden', 'catid', $category->id);
            $mform->setType('catid', PARAM_INT);
            
            $mform->addElement('static', 'header', '<h2>'.get_string('category', 'magtest').'</h2>');

            $mform->addElement('text', 'catname', get_string('name'), '', array('size' => '120', 'maxlength' => '255'));
            $mform->setDefault('catname', $category->name);
            $mform->setType('catname', PARAM_CLEANHTML);
                                    
            $symboloptions = magtest_get_symbols($magtest, $renderingpathbase);

            $selectgroup = $mform->addElement('selectgroups', 'symbol', get_string('symbol', 'mod_magtest'), $symboloptions);
            $selectgroup->setValue($category->symbol);
           
            $mod_context = get_context_instance(CONTEXT_MODULE, $id); 
       
            $catdesc_editor = $mform->addElement('editor', 'catdescription', get_string('description'), null, array('maxfiles' => EDITOR_UNLIMITED_FILES,
                        'noclean' => true, 'context' =>  $mod_context));
            $catdesc_editor->setValue(array('text' => $category->description));                      
                
            $catresult_editor  = $mform->addElement('editor', 'catresult', get_string('categoryresult', 'magtest'), null, array('maxfiles' => EDITOR_UNLIMITED_FILES,
                        'noclean' => true, 'context' =>  $mod_context));
            $catresult_editor->setValue(array('text' => $category->result));                      
            
            if ($this->magtest->usemakegroups){
                $mform->addElement('text', 'outputgroupname', get_string('outputgroupname', 'magtest'), '', array('size' => '128', 'maxlength' => '255'));
                $mform->setType('outputgroupname', PARAM_CLEANHTML);
                $mform->addElement('text', 'outputgroupdesc', get_string('outputgroupdesc', 'magtest'), '', array('size' => '255', 'maxlength' => '255'));
                $mform->setType('outputgroupdesc', PARAM_CLEANHTML);
            }  
        }
        
        $this->add_action_buttons();
	}
}
