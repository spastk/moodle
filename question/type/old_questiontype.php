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

/**
 * The default questiontype class.
 *
 * @author Martin Dougiamas and many others. This has recently been completely
 *         rewritten by Alex Smith, Julian Sedding and Gustav Delius as part of
 *         the Serving Mathematics project
 *         {@link http://maths.york.ac.uk/serving_maths}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 */

require_once($CFG->libdir . '/questionlib.php');

/**
 * This is the base class for Moodle question types.
 *
 * There are detailed comments on each method, explaining what the method is
 * for, and the circumstances under which you might need to override it.
 *
 * Note: the questiontype API should NOT be considered stable yet. Very few
 * question tyeps have been produced yet, so we do not yet know all the places
 * where the current API is insufficient. I would rather learn from the
 * experiences of the first few question type implementors, and improve the
 * interface to meet their needs, rather the freeze the API prematurely and
 * condem everyone to working round a clunky interface for ever afterwards.
 *
 * @package questionbank
 * @subpackage questiontypes
 */
class default_questiontype {
    protected $fileoptions = array(
        'subdirs' => false,
        'maxfiles' => -1,
        'maxbytes' => 0,
    );

    /**
     * Name of the question type
     *
     * The name returned should coincide with the name of the directory
     * in which this questiontype is located
     *
     * @return string the name of this question type.
     */
    function name() {
        return 'default';
    }

    /**
     * Returns a list of other question types that this one requires in order to
     * work. For example, the calculated question type is a subclass of the
     * numerical question type, which is a subclass of the shortanswer question
     * type; and the randomsamatch question type requires the shortanswer type
     * to be installed.
     *
     * @return array any other question types that this one relies on. An empty
     * array if none.
     */
    function requires_qtypes() {
        return array();
    }

    /**
     * @return string the name of this pluginfor passing to get_string, set/get_config, etc.
     */
    function plugin_name() {
        return 'qtype_' . $this->name();
    }

    /**
     * @return string the name of this question type in the user's language.
     * You should not need to override this method, the default behaviour should be fine.
     */
    function local_name() {
        return get_string($this->name(), $this->plugin_name());
    }

    /**
     * The name this question should appear as in the create new question
     * dropdown. Override this method to return false if you don't want your
     * question type to be createable, for example if it is an abstract base type,
     * otherwise, you should not need to override this method.
     *
     * @return mixed the desired string, or false to hide this question type in the menu.
     */
    function menu_name() {
        return $this->local_name();
    }

    /**
     * @return boolean override this to return false if this is not really a
     *      question type, for example the description question type is not
     *      really a question type.
     */
    function is_real_question_type() {
        return true;
    }

    /**
     * @return boolean true if this question type may require manual grading.
     */
    function is_manual_graded() {
        return false;
    }

    /**
     * @param object $question a question of this type.
     * @param string $otherquestionsinuse comma-separate list of other question ids in this attempt.
     * @return boolean true if a particular instance of this question requires manual grading.
     */
    function is_question_manual_graded($question, $otherquestionsinuse) {
        return $this->is_manual_graded();
    }

    /**
     * @return boolean true if a table analyzing responses should be shown in
     * the quiz statistics report. Usually if a question is manually graded
     * then this analysis table won't be a good idea.
     */
    function show_analysis_of_responses() {
        return !$this->is_manual_graded();
    }

    /**
     * @return boolean true if this question type can be used by the random question type.
     */
    function is_usable_by_random() {
        return true;
    }

    /**
     * @param question record.
     * @param integer subqid this is the id of the subquestion. Usually the id
     * of the question record of the question record but this is dependent on
     * the question type. Not relevant to some question types.
     * @return whether the teacher supplied responses can include wildcards. Can
     * more than one answer be equivalent to one teacher supplied response.
     */
    function has_wildcards_in_responses($question, $subqid) {
        return false;
    }

    /**
     * If your question type has a table that extends the question table, and
     * you want the base class to automatically save, backup and restore the extra fields,
     * override this method to return an array wherer the first element is the table name,
     * and the subsequent entries are the column names (apart from id and questionid).
     *
     * @return mixed array as above, or null to tell the base class to do nothing.
     */
    function extra_question_fields() {
        return null;
    }

    /**
        * If you use extra_question_fields, overload this function to return question id field name
        *  in case you table use another name for this column
        */
    function questionid_column_name() {
        return 'questionid';
    }

    /**
     * If your question type has a table that extends the question_answers table,
     * make this method return an array wherer the first element is the table name,
     * and the subsequent entries are the column names (apart from id and answerid).
     *
     * @return mixed array as above, or null to tell the base class to do nothing.
     */
    function extra_answer_fields() {
        return null;
    }

    /**
     * Return an instance of the question editing form definition. This looks for a
     * class called edit_{$this->name()}_question_form in the file
     * question/type/{$this->name()}/edit_{$this->name()}_question_form.php
     * and if it exists returns an instance of it.
     *
     * @param string $submiturl passed on to the constructor call.
     * @return object an instance of the form definition, or null if one could not be found.
     */
    function create_editing_form($submiturl, $question, $category, $contexts, $formeditable) {
        global $CFG;
        require_once("{$CFG->dirroot}/question/type/edit_question_form.php");
        $definition_file = $CFG->dirroot.'/question/type/'.$this->name().'/edit_'.$this->name().'_form.php';
        if (!(is_readable($definition_file) && is_file($definition_file))) {
            return null;
        }
        require_once($definition_file);
        $classname = 'question_edit_'.$this->name().'_form';
        if (!class_exists($classname)) {
            return null;
        }
        return new $classname($submiturl, $question, $category, $contexts, $formeditable);
    }

    /**
     * @return string the full path of the folder this plugin's files live in.
     */
    function plugin_dir() {
        global $CFG;
        return $CFG->dirroot . '/question/type/' . $this->name();
    }

    /**
     * @return string the URL of the folder this plugin's files live in.
     */
    function plugin_baseurl() {
        global $CFG;
        return $CFG->wwwroot . '/question/type/' . $this->name();
    }

    /**
     * This method should be overriden if you want to include a special heading or some other
     * html on a question editing page besides the question editing form.
     *
     * @param question_edit_form $mform a child of question_edit_form
     * @param object $question
     * @param string $wizardnow is '' for first page.
     */
    function display_question_editing_page(&$mform, $question, $wizardnow){
        global $OUTPUT;
        $heading = $this->get_heading(empty($question->id));

        echo $OUTPUT->heading_with_help($heading, $this->name(), $this->plugin_name());

        $permissionstrs = array();
        if (!empty($question->id)){
            if ($question->formoptions->canedit){
                $permissionstrs[] = get_string('permissionedit', 'question');
            }
            if ($question->formoptions->canmove){
                $permissionstrs[] = get_string('permissionmove', 'question');
            }
            if ($question->formoptions->cansaveasnew){
                $permissionstrs[] = get_string('permissionsaveasnew', 'question');
            }
        }
        if (!$question->formoptions->movecontext  && count($permissionstrs)){
            echo $OUTPUT->heading(get_string('permissionto', 'question'), 3);
            $html = '<ul>';
            foreach ($permissionstrs as $permissionstr){
                $html .= '<li>'.$permissionstr.'</li>';
            }
            $html .= '</ul>';
            echo $OUTPUT->box($html, 'boxwidthnarrow boxaligncenter generalbox');
        }
        $mform->display();
    }

    /**
     * Method called by display_question_editing_page and by question.php to get heading for breadcrumbs.
     *
     * @return array a string heading and the langmodule in which it was found.
     */
    function get_heading($adding = false){
        if ($adding) {
            $prefix = 'adding';
        } else {
            $prefix = 'editing';
        }
        return get_string($prefix . $this->name(), $this->plugin_name());
    }

    /**
    * Saves (creates or updates) a question.
    *
    * Given some question info and some data about the answers
    * this function parses, organises and saves the question
    * It is used by {@link question.php} when saving new data from
    * a form, and also by {@link import.php} when importing questions
    * This function in turn calls {@link save_question_options}
    * to save question-type specific data.
    *
    * Whether we are saving a new question or updating an existing one can be
    * determined by testing !empty($question->id). If it is not empty, we are updating.
    *
    * The question will be saved in category $form->category.
    *
    * @param object $question the question object which should be updated. For a new question will be mostly empty.
    * @param object $form the object containing the information to save, as if from the question editing form.
    * @param object $course not really used any more.
    * @return object On success, return the new question object. On failure,
    *       return an object as follows. If the error object has an errors field,
    *       display that as an error message. Otherwise, the editing form will be
    *       redisplayed with validation errors, from validation_errors field, which
    *       is itself an object, shown next to the form fields. (I don't think this is accurate any more.)
    */
    function save_question($question, $form) {
        global $USER, $DB, $OUTPUT;

        list($question->category) = explode(',', $form->category);
        $context = $this->get_context_by_category_id($question->category);

        // This default implementation is suitable for most
        // question types.

        // First, save the basic question itself
        $question->name = trim($form->name);
        $question->parent = isset($form->parent) ? $form->parent : 0;
        $question->length = $this->actual_number_of_questions($question);
        $question->penalty = isset($form->penalty) ? $form->penalty : 0;

        if (empty($form->questiontext['text'])) {
            $question->questiontext = '';
        } else {
            $question->questiontext = trim($form->questiontext['text']);;
        }
        $question->questiontextformat = !empty($form->questiontext['format'])?$form->questiontext['format']:0;

        if (empty($form->generalfeedback['text'])) {
            $question->generalfeedback = '';
        } else {
            $question->generalfeedback = trim($form->generalfeedback['text']);
        }
        $question->generalfeedbackformat = !empty($form->generalfeedback['format'])?$form->generalfeedback['format']:0;

        if (empty($question->name)) {
            $question->name = shorten_text(strip_tags($form->questiontext['text']), 15);
            if (empty($question->name)) {
                $question->name = '-';
            }
        }

        if ($question->penalty > 1 or $question->penalty < 0) {
            $question->errors['penalty'] = get_string('invalidpenalty', 'quiz');
        }

        if (isset($form->defaultgrade)) {
            $question->defaultgrade = $form->defaultgrade;
        }

        // If the question is new, create it.
        if (empty($question->id)) {
            // Set the unique code
            $question->stamp = make_unique_id_code();
            $question->createdby = $USER->id;
            $question->timecreated = time();
            $question->id = $DB->insert_record('question', $question);
        }

        // Now, whether we are updating a existing question, or creating a new
        // one, we have to do the files processing and update the record.
        /// Question already exists, update.
        $question->modifiedby = $USER->id;
        $question->timemodified = time();

        if (!empty($question->questiontext) && !empty($form->questiontext['itemid'])) {
            $question->questiontext = file_save_draft_area_files($form->questiontext['itemid'], $context->id, 'question', 'questiontext', (int)$question->id, $this->fileoptions, $question->questiontext);
        }
        if (!empty($question->generalfeedback) && !empty($form->generalfeedback['itemid'])) {
            $question->generalfeedback = file_save_draft_area_files($form->generalfeedback['itemid'], $context->id, 'question', 'generalfeedback', (int)$question->id, $this->fileoptions, $question->generalfeedback);
        }
        $DB->update_record('question', $question);

        // Now to save all the answers and type-specific options
        $form->id = $question->id;
        $form->qtype = $question->qtype;
        $form->category = $question->category;
        $form->questiontext = $question->questiontext;
        $form->questiontextformat = $question->questiontextformat;
        // current context
        $form->context = $context;

        $result = $this->save_question_options($form);

        if (!empty($result->error)) {
            print_error($result->error);
        }

        if (!empty($result->notice)) {
            notice($result->notice, "question.php?id=$question->id");
        }

        if (!empty($result->noticeyesno)) {
            throw new coding_exception('$result->noticeyesno no longer supported in save_question.');
        }

        // Give the question a unique version stamp determined by question_hash()
        $DB->set_field('question', 'version', question_hash($question), array('id' => $question->id));

        return $question;
    }

    /**
     * Saves question-type specific options
     *
     * This is called by {@link save_question()} to save the question-type specific data
     * @return object $result->error or $result->noticeyesno or $result->notice
     * @param object $question  This holds the information from the editing form,
     *      it is not a standard question object.
     */
    function save_question_options($question) {
        global $DB;
        $extra_question_fields = $this->extra_question_fields();

        if (is_array($extra_question_fields)) {
            $question_extension_table = array_shift($extra_question_fields);

            $function = 'update_record';
            $questionidcolname = $this->questionid_column_name();
            $options = $DB->get_record($question_extension_table, array($questionidcolname => $question->id));
            if (!$options) {
                $function = 'insert_record';
                $options = new stdClass;
                $options->$questionidcolname = $question->id;
            }
            foreach ($extra_question_fields as $field) {
                if (!isset($question->$field)) {
                    $result = new stdClass;
                    $result->error = "No data for field $field when saving " .
                            $this->name() . ' question id ' . $question->id;
                    return $result;
                }
                $options->$field = $question->$field;
            }

            if (!$DB->{$function}($question_extension_table, $options)) {
                $result = new stdClass;
                $result->error = 'Could not save question options for ' .
                        $this->name() . ' question id ' . $question->id;
                return $result;
            }
        }

        $extra_answer_fields = $this->extra_answer_fields();
        // TODO save the answers, with any extra data.

        return null;
    }

    /**
     * Loads the question type specific options for the question.
     *
     * This function loads any question type specific options for the
     * question from the database into the question object. This information
     * is placed in the $question->options field. A question type is
     * free, however, to decide on a internal structure of the options field.
     * @return bool            Indicates success or failure.
     * @param object $question The question object for the question. This object
     *                         should be updated to include the question type
     *                         specific information (it is passed by reference).
     */
    function get_question_options(&$question) {
        global $CFG, $DB, $OUTPUT;

        if (!isset($question->options)) {
            $question->options = new stdClass();
        }

        $extra_question_fields = $this->extra_question_fields();
        if (is_array($extra_question_fields)) {
            $question_extension_table = array_shift($extra_question_fields);
            $extra_data = $DB->get_record($question_extension_table, array($this->questionid_column_name() => $question->id), implode(', ', $extra_question_fields));
            if ($extra_data) {
                foreach ($extra_question_fields as $field) {
                    $question->options->$field = $extra_data->$field;
                }
            } else {
                echo $OUTPUT->notification("Failed to load question options from the table $question_extension_table for questionid " .
                        $question->id);
                return false;
            }
        }

        $extra_answer_fields = $this->extra_answer_fields();
        if (is_array($extra_answer_fields)) {
            $answer_extension_table = array_shift($extra_answer_fields);
            $question->options->answers = $DB->get_records_sql("
                    SELECT qa.*, qax." . implode(', qax.', $extra_answer_fields) . "
                    FROM {question_answers} qa, {$answer_extension_table} qax
                    WHERE qa.questionid = ? AND qax.answerid = qa.id", array($question->id));
            if (!$question->options->answers) {
                echo $OUTPUT->notification("Failed to load question answers from the table $answer_extension_table for questionid " .
                        $question->id);
                return false;
            }
        } else {
            // Don't check for success or failure because some question types do not use the answers table.
            $question->options->answers = $DB->get_records('question_answers', array('question' => $question->id), 'id ASC');
        }

        return true;
    }

    /**
    * Deletes states from the question-type specific tables
    *
    * @param string $stateslist  Comma separated list of state ids to be deleted
    */
    function delete_states($stateslist) {
        /// The default question type does not have any tables of its own
        // therefore there is nothing to delete

        return true;
    }

    /**
     * Deletes the question-type specific data when a question is deleted.
     * @param integer $question the question being deleted.
     * @param integer $contextid the context this quesiotn belongs to.
     */
    function delete_question($questionid, $contextid) {
        global $DB;

        $this->delete_files($questionid, $contextid);

        $extra_question_fields = $this->extra_question_fields();
        if (is_array($extra_question_fields)) {
            $question_extension_table = array_shift($extra_question_fields);
            $DB->delete_records($question_extension_table,
                    array($this->questionid_column_name() => $questionid));
        }

        $extra_answer_fields = $this->extra_answer_fields();
        if (is_array($extra_answer_fields)) {
            $answer_extension_table = array_shift($extra_answer_fields);
            $DB->delete_records_select($answer_extension_table,
                "answerid IN (SELECT qa.id FROM {question_answers} qa WHERE qa.question = ?)", array($questionid));
        }

        $DB->delete_records('question_answers', array('question' => $questionid));
    }

    /**
    * Returns the number of question numbers which are used by the question
    *
    * This function returns the number of question numbers to be assigned
    * to the question. Most question types will have length one; they will be
    * assigned one number. The 'description' type, however does not use up a
    * number and so has a length of zero. Other question types may wish to
    * handle a bundle of questions and hence return a number greater than one.
    * @return integer         The number of question numbers which should be
    *                         assigned to the question.
    * @param object $question The question whose length is to be determined.
    *                         Question type specific information is included.
    */
    function actual_number_of_questions($question) {
        // By default, each question is given one number
        return 1;
    }

    /**
    * Creates empty session and response information for the question
    *
    * This function is called to start a question session. Empty question type
    * specific session data (if any) and empty response data will be added to the
    * state object. Session data is any data which must persist throughout the
    * attempt possibly with updates as the user interacts with the
    * question. This function does NOT create new entries in the database for
    * the session; a call to the {@link save_session_and_responses} member will
    * occur to do this.
    * @return bool            Indicates success or failure.
    * @param object $question The question for which the session is to be
    *                         created. Question type specific information is
    *                         included.
    * @param object $state    The state to create the session for. Note that
    *                         this will not have been saved in the database so
    *                         there will be no id. This object will be updated
    *                         to include the question type specific information
    *                         (it is passed by reference). In particular, empty
    *                         responses will be created in the ->responses
    *                         field.
    * @param object $cmoptions
    * @param object $attempt  The attempt for which the session is to be
    *                         started. Questions may wish to initialize the
    *                         session in different ways depending on the user id
    *                         or time available for the attempt.
    */
    function create_session_and_responses(&$question, &$state, $cmoptions, $attempt) {
        // The default implementation should work for the legacy question types.
        // Most question types with only a single form field for the student's response
        // will use the empty string '' as the index for that one response. This will
        // automatically be stored in and restored from the answer field in the
        // question_states table.
        $state->responses = array(
                '' => '',
        );
        return true;
    }

    /**
    * Restores the session data and most recent responses for the given state
    *
    * This function loads any session data associated with the question
    * session in the given state from the database into the state object.
    * In particular it loads the responses that have been saved for the given
    * state into the ->responses member of the state object.
    *
    * Question types with only a single form field for the student's response
    * will not need not restore the responses; the value of the answer
    * field in the question_states table is restored to ->responses['']
    * before this function is called. Question types with more response fields
    * should override this method and set the ->responses field to an
    * associative array of responses.
    * @return bool            Indicates success or failure.
    * @param object $question The question object for the question including any
    *                         question type specific information.
    * @param object $state    The saved state to load the session for. This
    *                         object should be updated to include the question
    *                         type specific session information and responses
    *                         (it is passed by reference).
    */
    function restore_session_and_responses(&$question, &$state) {
        // The default implementation does nothing (successfully)
        return true;
    }

    /**
    * Saves the session data and responses for the given question and state
    *
    * This function saves the question type specific session data from the
    * state object to the database. In particular for most question types it saves the
    * responses from the ->responses member of the state object. The question type
    * non-specific data for the state has already been saved in the question_states
    * table and the state object contains the corresponding id and
    * sequence number which may be used to index a question type specific table.
    *
    * Question types with only a single form field for the student's response
    * which is contained in ->responses[''] will not have to save this response,
    * it will already have been saved to the answer field of the question_states table.
    * Question types with more response fields should override this method to convert
    * the data the ->responses array into a single string field, and save it in the
    * database. The implementation in the multichoice question type is a good model to follow.
    * http://cvs.moodle.org/contrib/plugins/question/type/opaque/questiontype.php?view=markup
    * has a solution that is probably quite generally applicable.
    * @return bool            Indicates success or failure.
    * @param object $question The question object for the question including
    *                         the question type specific information.
    * @param object $state    The state for which the question type specific
    *                         data and responses should be saved.
    */
    function save_session_and_responses(&$question, &$state) {
        // The default implementation does nothing (successfully)
        return true;
    }

    /**
    * Returns an array of values which will give full marks if graded as
    * the $state->responses field
    *
    * The correct answer to the question in the given state, or an example of
    * a correct answer if there are many, is returned. This is used by some question
    * types in the {@link grade_responses()} function but it is also used by the
    * question preview screen to fill in correct responses.
    * @return mixed           A response array giving the responses corresponding
    *                         to the (or a) correct answer to the question. If there is
    *                         no correct answer that scores 100% then null is returned.
    * @param object $question The question for which the correct answer is to
    *                         be retrieved. Question type specific information is
    *                         available.
    * @param object $state    The state of the question, for which a correct answer is
    *                         needed. Question type specific information is included.
    */
    function get_correct_responses(&$question, &$state) {
        /* The default implementation returns the response for the first answer
        that gives full marks. */
        if ($question->options->answers) {
            foreach ($question->options->answers as $answer) {
                if (((int) $answer->fraction) === 1) {
                    return array('' => $answer->answer);
                }
            }
        }
        return null;
    }

    /**
    * Return an array of values with the texts for all possible responses stored
    * for the question
    *
    * All answers are found and their text values isolated
    * @return object          A mixed object
    *             ->id        question id. Needed to manage random questions:
    *                         it's the id of the actual question presented to user in a given attempt
    *             ->responses An array of values giving the responses corresponding
    *                         to all answers to the question. Answer ids are used as keys.
    *                         The text and partial credit are the object components
    * @param object $question The question for which the answers are to
    *                         be retrieved. Question type specific information is
    *                         available.
    */
    // ULPGC ecastro
    function get_all_responses(&$question, &$state) {
        if (isset($question->options->answers) && is_array($question->options->answers)) {
            $answers = array();
            foreach ($question->options->answers as $aid=>$answer) {
                $r = new stdClass;
                $r->answer = $answer->answer;
                $r->credit = $answer->fraction;
                $answers[$aid] = $r;
            }
            $result = new stdClass;
            $result->id = $question->id;
            $result->responses = $answers;
            return $result;
        } else {
            return null;
        }
    }
    /**
     * The difference between this method an get_all_responses is that this
     * method is not passed a state object. It is the possible answers to a
     * question no matter what the state.
     * This method is not called for random questions.
     * @return array of possible answers.
     */
    function get_possible_responses(&$question) {
        static $responses = array();
        if (!isset($responses[$question->id])){
            $responses[$question->id] = $this->get_all_responses($question, new object());
        }
        return array($question->id => $responses[$question->id]->responses);
    }

    /**
     * @param object $question
     * @return mixed either a integer score out of 1 that the average random
     * guess by a student might give or an empty string which means will not
     * calculate.
     */
    function get_random_guess_score($question) {
        return 0;
    }
   /**
    * Return the actual response to the question in a given state
    * for the question. Text is not yet formatted for output.
    *
    * @return mixed           An array containing the response or reponses (multiple answer, match)
    *                         given by the user in a particular attempt.
    * @param object $question The question for which the correct answer is to
    *                         be retrieved. Question type specific information is
    *                         available.
    * @param object $state    The state object that corresponds to the question,
    *                         for which a correct answer is needed. Question
    *                         type specific information is included.
    */
    // ULPGC ecastro
    function get_actual_response($question, $state) {
       if (!empty($state->responses)) {
           $responses[] = $state->responses[''];
       } else {
           $responses[] = '';
       }
       return $responses;
    }

    function get_actual_response_details($question, $state) {
        $response = array_shift($this->get_actual_response($question, $state));
        $teacherresponses = $this->get_possible_responses($question, $state);
        //only one response
        list($tsubqid, $tresponses) = each($teacherresponses);
        $responsedetail = new stdClass();
        $responsedetail->subqid = $tsubqid;
        $responsedetail->response = $response;
        if ($aid = $this->check_response($question, $state)){
            $responsedetail->aid = $aid;
        } else {
            foreach ($tresponses as $aid => $tresponse){
                if ($tresponse->answer == $response){
                    $responsedetail->aid = $aid;
                    break;
                }
            }
        }
        if (isset($responsedetail->aid)){
            $responsedetail->credit = $tresponses[$aid]->credit;
        } else {
            $responsedetail->aid = 0;
            $responsedetail->credit = 0;
        }
        return array($responsedetail);
    }

    // ULPGC ecastro
    function get_fractional_grade(&$question, &$state) {
        $grade = $state->grade;
        if ($question->maxgrade > 0) {
            return (float)($grade / $question->maxgrade);
        } else {
            return (float)$grade;
        }
    }


    /**
    * Checks if the response given is correct and returns the id
    *
    * @return int             The ide number for the stored answer that matches the response
    *                         given by the user in a particular attempt.
    * @param object $question The question for which the correct answer is to
    *                         be retrieved. Question type specific information is
    *                         available.
    * @param object $state    The state object that corresponds to the question,
    *                         for which a correct answer is needed. Question
    *                         type specific information is included.
    */
    // ULPGC ecastro
    function check_response(&$question, &$state){
        return false;
    }

    // Used by the following function, so that it only returns results once per quiz page.
    private $htmlheadalreadydone = false;
    /**
     * Hook to allow question types to include required JavaScrip or CSS on pages
     * where they are going to be printed.
     *
     * If this question type requires JavaScript to function,
     * then this method, which will be called before print_header on any page
     * where this question is going to be printed, is a chance to call
     * $PAGE->requires->js, and so on.
     *
     * The two parameters match the first two parameters of print_question.
     *
     * @param object $question The question object.
     * @param object $state    The state object.
     */
    function get_html_head_contributions(&$question, &$state) {
        // We only do this once for this question type, no matter how often this
        // method is called on one page.
        if ($this->htmlheadalreadydone) {
            return;
        }
        $this->htmlheadalreadydone = true;

        // By default, we link to any of the files script.js or script.php that
        // exist in the plugin folder.
        $this->find_standard_scripts();
    }

    /**
     * Like @see{get_html_head_contributions}, but this method is for CSS and
     * JavaScript required on the question editing page question/question.php.
     */
    function get_editing_head_contributions() {
        // By default, we link to any of the files styles.css, styles.php,
        // script.js or script.php that exist in the plugin folder.
        // Core question types should not use this mechanism. Their styles
        // should be included in the standard theme.
        $this->find_standard_scripts();
    }

    /**
     * Utility method used by @see{get_html_head_contributions} and
     * @see{get_editing_head_contributions}. This looks for any of the files
     * script.js or script.php that exist in the plugin folder and ensures they
     * get included.
     */
    protected function find_standard_scripts() {
        global $PAGE;

        $plugindir = $this->plugin_dir();
        $plugindirrel = 'question/type/' . $this->name();

        if (file_exists($plugindir . '/script.js')) {
            $PAGE->requires->js('/' . $plugindirrel . '/script.js');
        }
        if (file_exists($plugindir . '/script.php')) {
            $PAGE->requires->js('/' . $plugindirrel . '/script.php');
        }
    }

    /**
    * Returns true if the editing wizard is finished, false otherwise.
    *
    * The default implementation returns true, which is suitable for all question-
    * types that only use one editing form. This function is used in
    * question.php to decide whether we can regrade any states of the edited
    * question and redirect to edit.php.
    *
    * The dataset dependent question-type, which is extended by the calculated
    * question-type, overwrites this method because it uses multiple pages (i.e.
    * a wizard) to set up the question and associated datasets.
    *
    * @param object $form  The data submitted by the previous page.
    *
    * @return boolean      Whether the wizard's last page was submitted or not.
    */
    function finished_edit_wizard(&$form) {
        //In the default case there is only one edit page.
        return true;
    }

    /**
     * Call format_text from weblib.php with the options appropriate to question types.
     *
     * @param string $text the text to format.
     * @param integer $text the type of text. Normally $question->questiontextformat.
     * @param object $cmoptions the context the string is being displayed in. Only $cmoptions->course is used.
     * @return string the formatted text.
     */
    function format_text($text, $textformat, $cmoptions = NULL) {
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->para = false;
        return format_text($text, $textformat, $formatoptions, $cmoptions === NULL ? NULL : $cmoptions->course);
    }

    /**
     * @return the best link to pass to print_error.
     * @param $cmoptions as passed in from outside.
     */
    function error_link($cmoptions) {
        global $CFG;
        $cm = get_coursemodule_from_instance('quiz', $cmoptions->id);
        if (!empty($cm->id)) {
            return $CFG->wwwroot . '/mod/quiz/view.php?id=' . $cm->id;
        } else if (!empty($cm->course)) {
            return $CFG->wwwroot . '/course/view.php?id=' . $cm->course;
        } else {
            return '';
        }
    }

/// IMPORT/EXPORT FUNCTIONS /////////////////

    /*
     * Imports question from the Moodle XML format
     *
     * Imports question using information from extra_question_fields function
     * If some of you fields contains id's you'll need to reimplement this
     */
    function import_from_xml($data, $question, $format, $extra=null) {
        $question_type = $data['@']['type'];
        if ($question_type != $this->name()) {
            return false;
        }

        $extraquestionfields = $this->extra_question_fields();
        if (!is_array($extraquestionfields)) {
            return false;
        }

        //omit table name
        array_shift($extraquestionfields);
        $qo = $format->import_headers($data);
        $qo->qtype = $question_type;

        foreach ($extraquestionfields as $field) {
            $qo->$field = $format->getpath($data, array('#',$field,0,'#'), $qo->$field);
        }

        // run through the answers
        $answers = $data['#']['answer'];
        $a_count = 0;
        $extraasnwersfields = $this->extra_answer_fields();
        if (is_array($extraasnwersfields)) {
            //TODO import the answers, with any extra data.
        } else {
            foreach ($answers as $answer) {
                $ans = $format->import_answer($answer);
                $qo->answer[$a_count] = $ans->answer;
                $qo->fraction[$a_count] = $ans->fraction;
                $qo->feedback[$a_count] = $ans->feedback;
                ++$a_count;
            }
        }
        return $qo;
    }

    /*
     * Export question to the Moodle XML format
     *
     * Export question using information from extra_question_fields function
     * If some of you fields contains id's you'll need to reimplement this
     */
    function export_to_xml($question, $format, $extra=null) {
        $extraquestionfields = $this->extra_question_fields();
        if (!is_array($extraquestionfields)) {
            return false;
        }

        //omit table name
        array_shift($extraquestionfields);
        $expout='';
        foreach ($extraquestionfields as $field) {
            $exportedvalue = $question->options->$field;
            if (!empty($exportedvalue) && htmlspecialchars($exportedvalue) != $exportedvalue) {
                $exportedvalue = '<![CDATA[' . $exportedvalue . ']]>';
            }
            $expout .= "    <$field>{$exportedvalue}</$field>\n";
        }

        $extraasnwersfields = $this->extra_answer_fields();
        if (is_array($extraasnwersfields)) {
            //TODO export answers with any extra data
        } else {
            foreach ($question->options->answers as $answer) {
                $percent = 100 * $answer->fraction;
                $expout .= "    <answer fraction=\"$percent\">\n";
                $expout .= $format->writetext($answer->answer, 3, false);
                $expout .= "      <feedback>\n";
                $expout .= $format->writetext($answer->feedback, 4, false);
                $expout .= "      </feedback>\n";
                $expout .= "    </answer>\n";
            }
        }
        return $expout;
    }

    /**
     * Abstract function implemented by each question type. It runs all the code
     * required to set up and save a question of any type for testing purposes.
     * Alternate DB table prefix may be used to facilitate data deletion.
     */
    function generate_test($name, $courseid=null) {
        $form = new stdClass();
        $form->name = $name;
        $form->questiontextformat = 1;
        $form->questiontext = 'test question, generated by script';
        $form->defaultgrade = 1;
        $form->penalty = 0.1;
        $form->generalfeedback = "Well done";

        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $newcategory = question_make_default_categories(array($context));
        $form->category = $newcategory->id . ',1';

        $question = new stdClass();
        $question->courseid = $courseid;
        $question->qtype = $this->qtype;
        return array($form, $question);
    }

    /**
     * Get question context by category id
     * @param int $category
     * @return object $context
     */
    function get_context_by_category_id($category) {
        global $DB;
        $contextid = $DB->get_field('question_categories', 'contextid', array('id'=>$category));
        $context = get_context_instance_by_id($contextid);
        return $context;
    }

    /**
     * Save the file belonging to one text field.
     *
     * @param array $field the data from the form (or from import). This will
     *      normally have come from the formslib editor element, so it will be an
     *      array with keys 'text', 'format' and 'itemid'. However, when we are
     *      importing, it will be an array with keys 'text', 'format' and 'files'
     * @param object $context the context the question is in.
     * @param string $component indentifies the file area question.
     * @param string $filearea indentifies the file area questiontext, generalfeedback,answerfeedback.
     * @param integer $itemid identifies the file area.
     *
     * @return string the text for this field, after files have been processed.
     */
    protected function import_or_save_files($field, $context, $component, $filearea, $itemid) {
        if (!empty($field['itemid'])) {
            // This is the normal case. We are safing the questions editing form.
            return file_save_draft_area_files($field['itemid'], $context->id, $component,
                    $filearea, $itemid, $this->fileoptions, trim($field['text']));

        } else if (!empty($field['files'])) {
            // This is the case when we are doing an import.
            foreach ($field['files'] as $file) {
                $this->import_file($context, $component,  $filearea, $itemid, $file);
            }
        }
        return trim($field['text']);
    }

    /**
     * Move all the files belonging to this question from one context to another.
     * @param integer $questionid the question being moved.
     * @param integer $oldcontextid the context it is moving from.
     * @param integer $newcontextid the context it is moving to.
     */
    public function move_files($questionid, $oldcontextid, $newcontextid) {
        $fs = get_file_storage();
        $fs->move_area_files_to_new_context($oldcontextid,
                $newcontextid, 'question', 'questiontext', $questionid);
        $fs->move_area_files_to_new_context($oldcontextid,
                $newcontextid, 'question', 'generalfeedback', $questionid);
    }

    /**
     * Move all the files belonging to this question's answers when the question
     * is moved from one context to another.
     * @param integer $questionid the question being moved.
     * @param integer $oldcontextid the context it is moving from.
     * @param integer $newcontextid the context it is moving to.
     * @param boolean $answerstoo whether there is an 'answer' question area,
     *      as well as an 'answerfeedback' one. Default false.
     */
    protected function move_files_in_answers($questionid, $oldcontextid, $newcontextid, $answerstoo = false) {
        global $DB;
        $fs = get_file_storage();

        $answerids = $DB->get_records_menu('question_answers',
                array('question' => $questionid), 'id', 'id,1');
        foreach ($answerids as $answerid => $notused) {
            if ($answerstoo) {
                $fs->move_area_files_to_new_context($oldcontextid,
                        $newcontextid, 'question', 'answer', $answerid);
            }
            $fs->move_area_files_to_new_context($oldcontextid,
                    $newcontextid, 'question', 'answerfeedback', $answerid);
        }
    }

    /**
     * Delete all the files belonging to this question.
     * @param integer $questionid the question being deleted.
     * @param integer $contextid the context the question is in.
     */
    protected function delete_files($questionid, $contextid) {
        $fs = get_file_storage();
        $fs->delete_area_files($contextid, 'question', 'questiontext', $questionid);
        $fs->delete_area_files($contextid, 'question', 'generalfeedback', $questionid);
    }

    /**
     * Delete all the files belonging to this question's answers.
     * @param integer $questionid the question being deleted.
     * @param integer $contextid the context the question is in.
     * @param boolean $answerstoo whether there is an 'answer' question area,
     *      as well as an 'answerfeedback' one. Default false.
     */
    protected function delete_files_in_answers($questionid, $contextid, $answerstoo = false) {
        global $DB;
        $fs = get_file_storage();

        $answerids = $DB->get_records_menu('question_answers',
                array('question' => $questionid), 'id', 'id,1');
        foreach ($answerids as $answerid => $notused) {
            if ($answerstoo) {
                $fs->delete_area_files($contextid, 'question', 'answer', $answerid);
            }
            $fs->delete_area_files($contextid, 'question', 'answerfeedback', $answerid);
        }
    }

    function import_file($context, $component, $filearea, $itemid, $file) {
        $fs = get_file_storage();
        $record = new stdclass;
        if (is_object($context)) {
            $record->contextid = $context->id;
        } else {
            $record->contextid = $context;
        }
        $record->component = $component;
        $record->filearea  = $filearea;
        $record->itemid    = $itemid;
        $record->filename  = $file->name;
        $record->filepath  = '/';
        return $fs->create_file_from_string($record, $this->decode_file($file));
    }

    function decode_file($file) {
        switch ($file->encoding) {
        case 'base64':
        default:
            return base64_decode($file->content);
        }
    }
}