<?php
    /**
    * Prints results of the test for the user
    * 
    * @package    mod-magtest
    * @category   mod
    * @author     Valery Fremaux <valery.fremaux@club-internet.fr>
    * @contributors   Etienne Roze
    * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
    * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
    */

    /**
    * Include and requires
    */
    require_once($CFG->libdir.'/tablelib.php');

    if (!defined('MOODLE_INTERNAL')) {
        error('You cannot access directly to this page');
    }

    // setup group state regarding the user
    $groupmode = groupmode($course, $cm);
    $changegroupid = optional_param('group', -1, PARAM_INT);              
    $currentgroupid = 0 + get_and_set_current_group($course, $groupmode, $changegroupid);

    if (has_capability('moodle/site:accessallgroups', $context)){
        $groups = get_groups($COURSE->id);
    } else {
        $groups = get_groups($COURSE->id, $USER->id);
    }
    $baseurl = $CFG->wwwroot."/mod/magtest/view.php?id={$cm->id}&amp;view=results";
    if ($groups){
        groups_print_course_menu($COURSE, $baseurl);
    }
    
    
/// get users of the current group who can do the test

    // note that usemakegroups is not compatible with course groups as it is used to generate
    // moodle groups in a course and needs having no groups at start.
    if ($groupmode == NOGROUPS || $magtest->usemakegroups){
        $users = get_users_by_capability($context, 'mod/magtest:doit', 'u.id,firstname,lastname,picture,email', 'lastname');
    } else {
        $users = get_users_by_capability($context, 'mod/magtest:doit', 'u.id,firstname,lastname,picture,email', 'lastname', '', '', $currentgroupid);
    }

    $usersanswers = magtest_get_useranswers($magtest->id, $users);
    
    if (! $usersanswers ) {
        notify(get_string('nouseranswer','magtest'));
        exit;
     }
    
    $categories = magtest_get_categories($magtest->id);
    $questions = magtest_get_questions($magtest->id);
    $count_cat = array();
    
    $nb_total = 0;
    
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
    
/// get max for each user

    foreach($users as $user){
        $max_cat[$user->id]->score = 0;
        $max_cat[$user->id]->catid = 0;
        foreach($categories as $cat){
            if (@$count_cat[$user->id][$cat->id] > $max_cat[$user->id]->score){
                $max_cat[$user->id]->score = $count_cat[$user->id][$cat->id];
                $max_cat[$user->id]->catid = $cat->id;
            }
        }
    }    
    
/// make table head

    echo '<center>';          
    $table->head[] = '<b>'.get_string('users').'</b>';
    $table->head[] = '<b>'.get_string('results', 'magtest').'</b>';
    $table->size = array('30%', '70%');
    $table->width = '80%';
    
    foreach($users as $userid => $user) {
        $userlink = "<a href=\"{$CFG->wwwroot}/user/view.php?id={$userid}\">".fullname($user).'</a>';
        $username = print_user_picture($user, $course->id, true, true, true).' '.$userlink;
        $scoreboard = '<table width="100%" class=\"magtest-scoretable\">';
        foreach($categories as $category) {
            if ($max_cat[$user->id]->catid == $category->id){
                $pf = '<span class="winner">';
                $sf = '</span>';
            } else {
                $pf = '';
                $sf = '';
            }
            $score = @$count_cat[$user->id][$category->id];
            $symbolurl = magtest_get_symbols_baseurl($magtest).$category->symbol;
            $symbolimg = "<img src=\"$symbolurl\" /> ";
            $scoreboard .= "<tr><td>{$pf}{$symbolimg} {$category->name}{$sf}</td><td align=\"right\">{$pf}{$score}{$sf}</td></tr>";
        }
        $scoreboard .= '</table>';

        $table->data[] = array($username, $scoreboard);
    }    
    print_table($table);
    
    $missings = magtest_get_unsubmitted_users($magtest, $users);

    unset($table);

    if (!empty($missings)){
        print_heading(get_string('unanswered', 'magtest'));
    
        $table->head[] = '<b>'.get_string('users').'</b>';
        $table->align = array('left');
        $table->size = array('100%');
        $table->width = '80%';
        
        foreach($missings as $userid => $user) {
            $userlink = "<a href=\"{$CFG->wwwroot}/user/view.php?id={$userid}\">".fullname($user).'</a>';
            $username = print_user_picture($user, $course->id, true, true, true).' '.$userlink;
            $table->data[] = array($username);
        }
        print_table($table);
    }
    
    echo '</center>';          
?>