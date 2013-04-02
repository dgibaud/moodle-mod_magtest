<?php

/**
* @package mod-magtest
* @category mods
*
*/

require_once "filesystemlib.php";

/**
 * Get all the questions structure of a magtest.
 * Return an array of object questions ( and these answers)
 *
 * @param int $magtestid
 * @return array
 *
 */
function magtest_get_questions($magtestid){
    
    $questions = get_records('magtest_question', 'magtestid', $magtestid, 'sortorder');
    if ($questions) {
        $answers = get_records('magtest_answer', 'magtestid', $magtestid);    
        foreach ($answers as $answer ){
            $questions[$answer->questionid]->answers[$answer->id] = $answer; 
        } 
    }
    return $questions;
}

/**
 * Get all the question structure of a magtest question.
 *
 * @param int $magtestid
 * @return array
 *
 */
function magtest_get_question($qid){
    
    $question = get_record('magtest_question', 'id', $qid);
    if ($question) {
        $answers = get_records('magtest_answer', 'questionid', $question->id);    
        /*
        foreach ($answers as $answer){
            $question->answers[$answer->id] = $answer; 
        }*/
       $question->answers = $answers;
    }
    return $question;
}

/**
 * gets answers for a question in several modes
 *
 * @param int $magtestid
 * @param int $order 
 * @param boolean $shuffle true if you want change the order of answer
 * @return object
 *
 */
function magtest_get_answers(&$question, $order = 1, $shuffle = false){

    if (!$question) return;

    $question->answers = get_records('magtest_answer', 'questionid', $question->id);
    if ($shuffle) {
	    shuffle($question->answers);
    } else {
        $question->ok = false;
    }
}

/**
 * Get all the categories of a magtest.
 * Return an array of object categories 
 *
 * @param int $magtestid
 * @return array
 *
 */
function magtest_get_categories($magtestid){

    if ($categories = get_records('magtest_category', 'magtestid', $magtestid, 'sortorder')){
        return $categories;
    }
    return array();
}

/**
 * return the category object of an answer.
 *
 * @param object $answer
 * @return object
 *
 */
function magtest_get_answer_cat($answer) {  
    $categorie = get_record('magtest_category', 'id', $answer->categoryid);     
    return $categorie;
}

/**
 * Get all the answers in a magtest for one user.
 * Return an array of object useranswers 
 *
 * @param int $magtestid
 * @param array $forusers if set to null, will retrieve answers for all users
 * @return array of records
 *
 */
function magtest_get_useranswers($magtestid, $forusers = null) {

    if (is_array($forusers)){
        $userlist = implode("','", array_keys($forusers));
    } elseif (is_string($forusers)){
        // admits a single user or a comma separated list
        $userlist = str_replace(',', "','", $forusers);
    }
    $userclause = (!is_null($forusers)) ? " AND userid IN ('$userlist') " : '' ;
    $select = " magtestid = {$magtestid} {$userclause} ";
    if ($useranswers = get_records_select('magtest_useranswer', $select, 'questionid')){
        return $useranswers;
    }
    return array();
}

/**
* get the next unanswered page of questions for a given user
*
*/
function magtest_get_next_questionset(&$magtest, $userid=null){
    global $CFG, $USER;

    if (is_null($userid)) $userid = $USER->id;

    // get the last record that was answered
    $sql = "
        SELECT 
            MAX(sortorder) as maxorder
        FROM
            {$CFG->prefix}magtest_question mq,
            {$CFG->prefix}magtest_useranswer mua
        WHERE
            mq.magtestid = {$magtest->id} AND
            mq.id = mua.questionid AND
            userid = {$userid}
    ";
    $record = get_record_sql($sql);
    $maxorder = 0 + @$record->maxorder;

    $sql = "
        SELECT
            *
        FROM
            {$CFG->prefix}magtest_question
        WHERE
            magtestid = {$magtest->id} AND
            sortorder > {$maxorder}
        ORDER BY 
            sortorder
    ";
    if ($magtest->pagesize){
        $questionset = get_records_sql($sql, 0, $magtest->pagesize);
    } else {
        $questionset = get_records_sql($sql);
    }

    if ($questionset){
        foreach($questionset as $key => $question){
            $questionset[$key]->answers = get_records('magtest_answer', 'questionid', $question->id);
        }
    }
    
    return $questionset;
}

/**
* get a list of symbol image pathes
* @param reference $magtest
* @param reference $renderingpathbase the path base to be set to access image set
* @uses custom filesystemlib.php common extra library for file system high level access
*/
function magtest_get_symbols(&$magtest, &$renderingpathbase){
    global $CFG;
    
    if (!is_dir($CFG->dataroot.'/'.$magtest->course.'/moddata/magtest/'.$magtest->id.'/pix/symbols')){
        $symbolpath = '/mod/magtest/pix/symbols';
        $symbolroot = $CFG->dirroot;
        $renderingpathbase = $CFG->wwwroot.'/mod/magtest/pix/symbols/';
    } else {
        $symbolpath = $magtest->course.'/moddata/magtest/'.$magtest->id.'/pix/symbols';
        $symbolroot = $CFG->dataroot;
        if (!$CFG->slasharguments){
            $renderingpathbase = "file.php?id=/{$symbolpath}/";
        } else {
            $renderingpathbase = "file.php/{$symbolpath}/";
        }
    }
    
    $symbolclasses = filesystem_scan_dir($symbolpath, FS_IGNORE_HIDDEN, FS_ONLY_DIRS, $symbolroot);
    for($i = 0 ; $i < count($symbolclasses) ; $i++){
        $symbolclass = $symbolclasses[$i];
    	if ($symbolclass == 'CVS' || preg_match('/^\./', $symbolclass)) continue;
        $symbols = filesystem_scan_dir($symbolpath.'/'.$symbolclass, FS_IGNORE_HIDDEN, FS_NO_DIRS, $symbolroot);                
        foreach($symbols as $symbol){
            $symboloptions[$symbolclass][$symbolclass.'/'.$symbol] = $symbol;
        }
    }
    
    return $symboloptions;
}

/**
* get a list of symbol image pathes
* @param reference $magtest
* @param reference $renderingpathbase the path base to be set to access image set
* @uses custom filesystemlib.php common extra library for file system high level access
*/
function magtest_get_symbols_baseurl(&$magtest){
    global $CFG;
    
    if (!is_dir($CFG->dataroot.'/'.$magtest->course.'/moddata/magtest/'.$magtest->id.'/pix/symbols')){
        $renderingpathbase = $CFG->wwwroot.'/mod/magtest/pix/symbols/';
    } else {
        $symbolpath = $magtest->course.'/moddata/magtest/'.$magtest->id.'/pix/symbols';
        if (!$CFG->slasharguments){
            $renderingpathbase = "file.php?id=/{$symbolpath}/";
        } else {
            $renderingpathbase = "file.php/{$symbolpath}/";
        }
    }
    
    return $renderingpathbase;
}

/**
* Compiles all submitted answers and organise a data structure for reporting
* @param object $magtest (by ref) the current Magtest instance
* @param array $users the users for whom results are compiled
* @param reference $categories the full set of categories with result data
* @param reference $max_cat a structure that records the "winning" category for all users
*/
function magtest_compile_results(&$magtest, &$users, &$categories, &$max_cat){
    global $COURSE;

    $usersanswers = magtest_get_useranswers($magtest->id, $users);
    
    if (! $usersanswers ) {
        notify(get_string('nouseranswer','magtest'));
        print_footer($COURSE);
        exit;
     }
    
    $categories = magtest_get_categories($magtest->id);
    $questions = magtest_get_questions($magtest->id);
    $count_cat = array();    
    foreach($usersanswers as $useranswer) {      
        $cat = $categories[$questions[$useranswer->questionid]->answers[$useranswer->answerid]->categoryid];    
        // aggregate scores
        if ($magtest->weighted){
            $weight = $questions[$useranswer->questionid]->answers[$useranswer->answerid]->weight;
            $count_cat[$useranswer->userid][$cat->id] = 0 + @$count_cat[$useranswer->userid][$cat->id] + $weight ;
        } else {
            $count_cat[$useranswer->userid][$cat->id] = 0 + @$count_cat[$useranswer->userid][$cat->id] + 1 ;
        }
    }
        
    /// get max for each user and organize them in categories
    
    foreach($users as $user){
        $max_cat[$user->id]->score = 0;
        $max_cat[$user->id]->catid = 0;
        foreach($categories as $cat){
            if (@$count_cat[$user->id][$cat->id] > $max_cat[$user->id]->score){
                $max_cat[$user->id]->score = $count_cat[$user->id][$cat->id];
                $categories[$cat->id]->users[] = $user->id;
            }
        }
    }            
}

/**
*
* @param object $magtest
* @param array $users
*/
function magtest_get_unsubmitted_users(&$magtest, &$users){
    global $CFG;

    if (empty($users)) return false;

    $userlist = implode("','", array_keys($users));

    // searches everyone that is in submitted subset that HAS NOT
    // records in user answers
    $sql = "
        SELECT
            u.id,
            u.firstname,
            u.lastname,
            u.email,
            u.emailstop,
            u.picture,
            u.mnethostid
        FROM 
            {$CFG->prefix}user u
        WHERE 
            u.id IN ('$userlist') AND
            u.id NOT IN ( 
                SELECT DISTINCT
                    userid 
                FROM
                    {$CFG->prefix}magtest_useranswer
                WHERE
                    magtestid = {$magtest->id})
    ";
    
    if ($missings = get_records_sql($sql)){
        return $missings;
    }
    return array();
}

/**
* tests if the configuration of the magtest is "playable"
* @param reference $magtest
* @return true if the configuration binds to a playable test
*/
function magtest_test_configuration(&$magtest){
    
    // checks we have categories in test
    $catcount = count_records('magtest_category', 'magtestid', $magtest->id);    
    if ($catcount == 0) return false;

    // checks we have questions in test
    $questioncount = count_records('magtest_question', 'magtestid', $magtest->id);    
    if ($questioncount == 0) return false;

    return true;    
}
?>