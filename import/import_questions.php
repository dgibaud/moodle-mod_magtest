<?php

/**
* @package magtest
* @author Valery Fremaux / 1.8
* @date 30/01/2014
*
* This page shows view for importing questions. 
* Inputs can be imported uploading a text file with one question per line
* empty lines are ignored, so are lines starting with !, / or #
*/

    require_once("../../../config.php");

	require_once $CFG->dirroot.'/mod/magtest/forms/import_questions_form.php';
    require_once($CFG->dirroot."/mod/magtest/lib.php");
    require_once($CFG->dirroot."/mod/magtest/locallib.php");

    $id = required_param('id', PARAM_INT);           // Course Module ID
    $action = optional_param('what', '', PARAM_TEXT); 

	$url = new moodle_url($CFG->wwwroot.'/mod/magtest/import/import_questions.php', array('id' => $id));
    $PAGE->set_url($url);

    if (! $cm = get_coursemodule_from_id('magtest', $id)) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
    if (!$magtest = $DB->get_record('magtest', array('id' => $cm->instance))) {
        print_error('invalidcoursemodule');
    }

    $magtest->cmid = $cm->id;

/// security 

    $context = context_module::instance($cm->id);

    require_course_login($course->id, false, $cm);
    require_capability('mod/magtest:manage', $context);
    
/// Forms and controllers

	$form = new ImportQuestionsForm();
	
	$categories = magtest_get_categories($magtest->id);
	
	$out = '';

	if ($form->is_cancelled()){
		redirect($CFG->wwwroot.'/mod/magtest/view.php?id='.$id);
	}
	if ($data = $form->get_data()){
		// TODO : process the file 
		$fs = get_file_storage();

		$draftitemid = $data->inputs;
		$usercontext = context_user::instance($USER->id);
		if (!$fs->is_area_empty($usercontext->id, 'user', 'draft', $draftitemid)){
			$submittedfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid);
			$submittedfile = array_pop($submittedfiles);
			$content = $submittedfile->get_content();
			$lines = explode("\n", $content);
			
			if (!empty($data->clearalldata)){
				$DB->delete_records('magtest_question', array('magtestid' => $magtest->id));
				$DB->delete_records('magtest_useranswer', array('magtestid' => $magtest->id));
				$DB->delete_records('magtest_answer', array('magtestid' => $magtest->id));
				$lastorder = 1;
			} else {
				$lastorder = $DB->get_field('magtest_question', 'MAX(sortorder)', array('magtestid' => $magtest->id)); 
				$lastorder++;
			}

			$i = 0;			
			foreach($lines as $l){
				$i++;
				if (empty($l)) continue;
				if (preg_match('/^[!#(]/', $l)) {
					continue; // throw some comments out
				}

				$linedata = explode(';', $l);
				
				if ($magtest->singlechoice){					
					if (count($linedata) != count($categories) + 1){
						$out = "Bad line count... ignore line $i<br/>";
						continue;
					}
				} else {
					if (count($linedata) != (count($categories) * 3) + 1){
						$out = "Bad line count... ignore line $i<br/>";
						continue;
					}
				}

				$question = new StdClass();
				$question->magtestid = $magtest->id;
				$question->questiontext = $linedata[0];
				$question->questiontextformat = FORMAT_HTML;
				$question->sortorder = $lastorder + $i;
				$question->id = $DB->insert_record('magtest_question', $question);

				if ($magtest->singlechoice){					
					$j = 1;					
					foreach($categories as $cat){
						$answer = new StdClass();
						$answer->magtestid = $magtest->id;
						$answer->categoryid = $cat->id;
						$answer->questionid = $question->id;
						$answer->answertext = '';
						$answer->answertextformat = 0;
						$answer->weight = $linedata[$j];
						$answer->helpertext = '';
						$answer->helpertextformat = 0;
						$answer->id = $DB->insert_record('magtest_answer', $answer);
						$j++;
					}
				} else {
					$j = 1;					
					foreach($categories as $cat){
						$answer = new StdClass();
						$answer->magtestid = $magtest->id;
						$answer->categoryid = $cat->id;
						$answer->questionid = $question->id;
						$answer->answertext = $linedata[$j];
						$answer->answertextformat = FORMAT_HTML;
						$answer->weight = $linedata[$j + 1];
						$answer->helpertext = $linedata[$j + 2];
						$answer->helpertextformat = FORMAT_HTML;
						$answer->id = $DB->insert_record('magtest_answer', $answer);
						$j += 3;
					}
				}
			}
		}
		
		redirect($CFG->wwwroot.'/mod/magtest/view.php?id='.$id);
	}

/// Prepare header

    $strmagtest = get_string('pluginname', 'magtest');

    $PAGE->set_context($context);
    $PAGE->set_title($course->shortname.': '.format_string($magtest->name));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->navbar->add($strmagtest);
    $PAGE->navbar->add(get_string('importquestions', 'magtest'));
    $PAGE->set_button($OUTPUT->update_module_button($cm->id, 'magtest'));

/// Print the page header

    echo $OUTPUT->header();

	echo $OUTPUT->heading_with_help(get_string('importquestions', 'magtest'), 'importformat', 'magtest');
	
	echo $OUTPUT->box_start();

	$data = new StdClass();
	$data->id = $cm->id;
	$form->set_data($data);
	$form->display();

	echo $OUTPUT->box_end();

	echo $OUTPUT->footer();
