<?php
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
 * Kaltura video resource formslib class.
 *
 * @package    mod_kalvidres
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2014 Remote Learner.net Inc http://www.remote-learner.net
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once(dirname(dirname(dirname(__FILE__))).'/course/moodleform_mod.php');
require_once(dirname(dirname(dirname(__FILE__))).'/local/kaltura/locallib.php');

class mod_kalvidres_mod_form extends moodleform_mod {
    /** @var string Part of the id for the add video button. */
    protected $addvideobutton = 'add_video';

    /**
     * Forms lib definition function
     */
    public function definition() {
        global $CFG, $COURSE, $PAGE;

        $PAGE->requires->css('/mod/kalvidres/styles.css');
        $pageclass = 'kaltura-kalvidres-body';
        $PAGE->add_body_class($pageclass);

        $params = array(
            'withblocks' => 0,
            'courseid' => $COURSE->id,
            'width' => KALTURA_PANEL_WIDTH,
            'height' => KALTURA_PANEL_HEIGHT
        );

        $url = new moodle_url('/mod/kalvidres/lti_launch.php', $params);

        $params = array(
            'addvidbtnid' => 'id_'.$this->addvideobutton,
            'ltilaunchurl' => $url->out(false),
            'height' => KALTURA_PANEL_HEIGHT,
            'width' => KALTURA_PANEL_WIDTH,
            'modulename' => 'kalvidres'
        );

        $PAGE->requires->yui_module('moodle-local_kaltura-ltipanel', 'M.local_kaltura.init', array($params), null, true);
        // Make replace media language string available to the YUI modules
        $PAGE->requires->string_for_js('replace_video', 'kalvidres');
        $PAGE->requires->string_for_js('browse_and_embed', 'local_kaltura');

        // Require a YUI module to make the object tag be as large as possible.
        $params = array(
            'bodyclass' => $pageclass,
            'lastheight' => null,
            'padding' => 15
        );
        $PAGE->requires->yui_module('moodle-local_kaltura-lticontainer', 'M.local_kaltura.init', array($params), null, true);

        $mform =& $this->_form;

        // This line is needed to avoid a PHP warning when the form is submitted.
        // Because this value is set as the default for one of the formslib elements.
        $uiconf_id = '';

        /* Hidden fields */
        $attr = array('id' => 'entry_id');
        $mform->addElement('hidden', 'entry_id', '', $attr);
        $mform->setType('entry_id', PARAM_NOTAGS);

        $attr = array('id' => 'source');
        $mform->addElement('hidden', 'source', '', $attr);
        $mform->setType('source', PARAM_URL);

        $attr = array('id' => 'video_title');
        $mform->addElement('hidden', 'video_title', 'x', $attr);
        $mform->setType('video_title', PARAM_TEXT);

        $attr = array('id' => 'uiconf_id');
        $mform->addElement('hidden', 'uiconf_id', '', $attr);
        $mform->setDefault('uiconf_id', $uiconf_id);
        $mform->setType('uiconf_id', PARAM_INT);

        $attr = array('id' => 'widescreen');
        $mform->addElement('hidden', 'widescreen', 'x', $attr);
        $mform->setDefault('widescreen', 0);
        $mform->setType('widescreen', PARAM_INT);

        $attr = array('id' => 'height');
        $mform->addElement('hidden', 'height', '', $attr);
        $mform->setDefault('height', '365');
        $mform->setType('height', PARAM_TEXT);

        $attr = array('id' => 'width');
        $mform->addElement('hidden', 'width', '', $attr);
        $mform->setDefault('width', '400');
        $mform->setType('width', PARAM_TEXT);

        $attr = array('id' => 'metadata');
        $mform->addElement('hidden', 'metadata', '', $attr);
        $mform->setType('metadata', PARAM_TEXT);

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'kalvidres'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $mform->addElement('header', 'video', get_string('video_hdr', 'kalvidres'));
        $mform->setExpanded('video',true);
        $this->add_video_definition($mform);

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    /**
     * This function adds the video thumbnail element and buttons to the form.
     * @param MoodleQuickForm $mform An instance of MoodleQuickForm used to add elements to the form.
     */
    private function add_video_definition($mform) {
        $addinstance = empty($this->current->entry_id) ? true : false;

        $thumbnail = $this->get_thumbnail_markup(!$addinstance);

        $videopreview = $this->get_iframe_video_preview_markup($addinstance);

        $videodetails = $this->get_video_details($addinstance);;

        $mform->addElement('static', 'add_video_thumb', '&nbsp;', $thumbnail);
        $mform->addElement('static', 'video_details', '&nbsp;', $videodetails);
        $mform->addElement('static', 'video_preview', '&nbsp;', $videopreview);

        $videogroup = array();
        if ($addinstance) {
            $videogroup[] =& $mform->createElement('button', $this->addvideobutton, get_string('add_video', 'kalvidres'));
        } else {
            $videogroup[] =& $mform->createElement('button', $this->addvideobutton, get_string('replace_video', 'kalvidres'));
        }

        $mform->addGroup($videogroup, 'video_group', '&nbsp;', '&nbsp;', false);
    }

    /**
     * This functions returns the markup to display a thumbnail image.
     * @param bool $hide Set to true to hide it, otherwise false.  When set to hide the thumbnail markup is still rendered
     * but the display style is set to none.  The reason for this is that the YUI module uses the img tag to place the iframe just below it.
     * As well as to hide the image tag when a new video is selected.
     * @return string Returns an image element markup.
     */
    private function get_thumbnail_markup($hide = false) {
        $source = new moodle_url('/local/kaltura/pix/vidThumb.png');
        $alt    = get_string('add_video', 'kalvidres');
        $title  = get_string('add_video', 'kalvidres');

        $attr = array(
            'id' => 'video_thumbnail',
            'src' => $source->out(),
            'alt' => $alt,
            'title' => $title
        );

        if ($hide) {
            $attr['style'] = 'display:none';
        }

        $output = html_writer::empty_tag('img', $attr);

        return $output;
    }

    /**
     * This functions returns iframe markup for displaying the video preview interface.
     * @param bool $hide True to hide the element, otherwise false.
     * @return string Returns an iframe markup
     */
    private function get_iframe_video_preview_markup($hide = true) {
        $width = empty($this->current->width) ? '0px' : $this->current->width.'px';
        $height = empty($this->current->height) ? 'opx' : $this->current->height.'px';
        $source = empty($this->current->source) ? '' : $this->current->source;

        $params = array(
            'id' => 'contentframe',
            'class' => 'kaltura-player-iframe',
            'src' => $source,
            'height' => $height,
            'width' => $width,
            'allowfullscreen' => 'true',
            'allow' => 'autoplay *; fullscreen *; encrypted-media *; camera *; microphone *; display-capture *;',
        );

        if ($hide) {
            $params['style'] = 'display: none';
        }

        // If the source attribute is not empty, initiate an LTI launch to avoid having ACL issues when another user with permissions edits the module.
        // This also assists with full screen functionality on some mobile devices.
        if (!empty($source)) {
            $ltiparams = array(
                'courseid' => $this->current->course,
                'height' => $height,
                'width' => $width,
                'withblocks' => 0,
                'source' => $source
            );

            $url = new moodle_url('/mod/kalvidres/lti_launch.php', $ltiparams);
            $params['src'] = $url->out(false);
        }

        $header = html_writer::tag('h4', get_string('preview', 'mod_kalvidres'));
        $iframe = html_writer::tag('iframe', '', $params);

        $iframeContainer = html_writer::tag('div', $iframe, array(
            'class' => 'kaltura-player-container'
        ));
        $output = html_writer::tag('div', $header . $iframeContainer);

        return $output;
    }

    private function get_video_details($hide = false) {

        $params = [];
        if ($hide) {
            $params['style'] = 'display: none';
        }

        $details = [];
        $details['id'] = get_string('metadata_id', 'mod_kalvidres') . ': ' . $this->current->entry_id;
        $details['title'] = get_string('metadata_title', 'mod_kalvidres') . ': ' . $this->current->video_title;

        $decodedmetadata = base64_decode($this->current->metadata);
        $parsedmetadata = unserialize($decodedmetadata);

        if ($parsedmetadata) {
//            $details['title'] = get_string('metadata_title', 'mod_kalvidres') . ': ' . $parsedmetadata->title;
//            $details['description'] = get_string('metadata_description', 'mod_kalvidres') . ': ' . $parsedmetadata->description;
            $details['owner'] = get_string('metadata_owner', 'mod_kalvidres') . ': ' . $parsedmetadata->owner;
            $details['creation_date'] = get_string('metadata_creationdate', 'mod_kalvidres') . ': ' . userdate($parsedmetadata->createdat);
//            $details['url'] = 'URL: ' . html_writer::link(local_kaltura_get_config()->kaf_uri . '/media/t/' . $parsedmetadata->entryid, 'Video', ['target' => '_blank']);
        }

        $header = html_writer::tag('h4', get_string('metadata', 'mod_kalvidres'));
        $metadatalist = html_writer::alist($details);
        $output = html_writer::div($header . $metadatalist, '', $params);

        return $output;
    }

    /**
     * This function validates the form on save.
     *
     * @param array $data Array of form values
     * @param array $files Array of files
     * @return array $errors Array of error messages
     */
    public function validation($data, $files) {
        $errors = array();

        if (empty($data['source'])) {
            $errors['add_video_thumb'] = get_string('novidsource', 'kalvidres');
        }

        return $errors;
    }
}
