<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

/**
 * This view allows checking deck states
 *
 * @package mod_magtest
 * @category mod
 * @author Valery Fremaux
 * @contributors Etienne Roze, Didier Gibaud
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
* overrides moodleform for test setup
*/
class mod_magtest_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $COURSE;

        $mform =& $this->_form;

        $editoroptions = magtest_getEditorOptions($this->context);

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'timinghdr', get_string('availability'));

        $startdatearray[] = &$mform->createElement('date_time_selector', 'starttime', '');
        $startdatearray[] = &$mform->createElement('checkbox', 'starttimeenable', '');
        $mform->addGroup($startdatearray, 'startfrom', get_string('starttime', 'magtest'), ' ', false);
        $mform->disabledIf('startfrom', 'starttimeenable');

        $enddatearray[] = &$mform->createElement('date_time_selector', 'endtime', '');
        $enddatearray[] = &$mform->createElement('checkbox', 'endtimeenable', '');
        $mform->addGroup($enddatearray, 'endfrom', get_string('endtime', 'magtest'), ' ', false);
        $mform->disabledIf('endfrom', 'endtimeenable');

        // $mform->addElement('date_time_selector', 'starttime', get_string('starttime', 'magtest'),
        //     array('optional' => true));
        // $mform->addElement('date_time_selector', 'endtime', get_string('endtime', 'magtest'),
        //     array('optional' => true));

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'magtesthdr', get_string('questionandsubmission', 'magtest'));

        $mform->addElement('checkbox', 'singlechoice', get_string('singlechoice', 'magtest'));
        $mform->addHelpButton('singlechoice', 'singlechoice', 'magtest');

        $mform->addElement('checkbox', 'weighted', get_string('weighted', 'magtest'));
        $mform->addHelpButton('weighted', 'weighted', 'magtest');
        $mform->disabledIf('weight', 'singlechoice', 'checked');

        $mform->addElement('checkbox', 'usemakegroups', get_string('usemakegroups', 'magtest'));
        $mform->addHelpButton('usemakegroups', 'usemakegroups', 'magtest');

        $mform->addElement('text', 'pagesize', get_string('pagesize', 'magtest'), array('size' => 3));
        $mform->addHelpButton('pagesize', 'pagesize', 'magtest');
        $mform->setType('pagesize', PARAM_TEXT);

        $mform->addElement('checkbox', 'allowreplay', get_string('allowreplay', 'magtest'));
        $mform->addHelpButton('allowreplay', 'allowreplay', 'magtest');

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'aftersubmithdr', get_string('after_submit', 'magtest'));

        $mform->addElement('editor', 'result_editor', get_string('resulttext', 'magtest'), null, $editoroptions);
        $mform->setType('result_editor', PARAM_RAW);

        //-------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------------------------------
        // Buttons
        $this->add_action_buttons();
    }

    /**
     * Load in existing data as form defaults
     *
     * @param stdClass|array $default_values object or array of default values
     */
    public function data_preprocessing(&$default_values) {
        $editoroptions = magtest_getEditorOptions($this->context);

        if ($this->current->instance) {
            // Edit an existing magtest - let us prepare the added editor elements (intro done automatically)
            $draftitemid = file_get_submitted_draft_itemid('result');
            $default_values['result_editor']['text'] =
                        file_prepare_draft_area($draftitemid,
                        $this->context->id,
                        'mod_magtest',
                        'result',
                        false,
                        $editoroptions,
                        $default_values['result']);
            $default_values['result_editor']['format'] = $default_values['resultformat'];
            $default_values['result_editor']['itemid'] = $draftitemid;
        } else {
            // Add a new magtest instance
            $draftitemid = file_get_submitted_draft_itemid('result_editor');

            // No context yet, itemid not used
            file_prepare_draft_area($draftitemid, null, 'mod_magtest', 'result', false);
            $default_values['result_editor']['text'] = '';
            $default_values['result_editor']['format'] = editors_get_preferred_format();
            $default_values['result_editor']['itemid'] = $draftitemid;
        }
    }

    /**
     * Load in existing data as form defaults
     *
     * @param stdClass|array $default_values object or array of default values
     */
    public function set_data($default_values) {
        if (!is_object($default_values)) {
            // An object is needed for file_prepare_standard_editor
            $default_values = (object)$default_values;
        }

        if (!isset($default_values->result)){
            $default_values->result = "";
            $default_values->resultformat = "";
        }

        $context = $this->context;
        $editoroptions = magtest_getEditorOptions($context);

        $default_values = file_prepare_standard_editor(
              $default_values,
              'result',
              $editoroptions,
              $context,
              'mod_magtest',
              'result',
              $default_values->id);

        parent::set_data($default_values);
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    function get_data() {
        $data = parent::get_data();

        if ($data !== null) {
            $data->resultformat = $data->result_editor['format'];
            $data->result = $data->result_editor['text'];

            if (!empty($data->completionunlocked)) {
                // Turn off completion settings if the checkboxes aren't ticked
                $autocompletion = !empty($data->completion) &&
                    $data->completion == COMPLETION_TRACKING_AUTOMATIC;
                if (!$autocompletion || empty($data->completionsubmit)) {
                    $data->completionsubmit=0;
                }
            }
        }

        return $data;
    }


    public function validation($data, $files = null) {
        $errors = array();
        return $errors;
    }
}
