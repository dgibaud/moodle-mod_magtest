<?php

/**
* Controller for "maketest"
* 
* @package    mod-magtest
* @category   mod
* @author     Valery Fremaux <valery.fremaux@club-internet.fr>
* @contributors   Etienne Roze
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
* @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
* @see        maketest.php for view.
 *
* @usecase    save
* @usecase    reset
*/

if (!defined('MOODLE_INTERNAL')) {
    error('You cannot access directly to this page');
}

/******************************************* save answers ***********************/
if ($action == 'save'){
    $inputkeys = preg_grep("/^answer/", array_keys($_POST));
    foreach($inputkeys as $akey){
        if (preg_match("/^answer(\\d+)/", $akey, $matches)){
            $questionid = $matches[1];
            $useranswer->magtestid = $magtest->id;
            $useranswer->userid = $USER->id;
            $useranswer->answerid = required_param($akey, PARAM_INT);
            $useranswer->questionid = $questionid;
            $useranswer->timeanswered = time();
            if ($old = get_record('magtest_useranswer', 'userid', $USER->id, 'magtestid', $magtest->id, 'questionid', $questionid)){
                $useranswer->id = $old->id;
                update_record('magtest_useranswer', $useranswer);
            } else {
                insert_record('magtest_useranswer', $useranswer);
            }
        }
    }
}
/******************************************* reset ***********************/
if ($action == 'reset'){
    if ($magtest->allowreplay and has_capability('mod/magtest:multipleattempts', $context)){ // protect again here
        delete_records('magtest_useranswer', 'magtestid', $magtest->id, 'userid', $USER->id);
    }
}

?>