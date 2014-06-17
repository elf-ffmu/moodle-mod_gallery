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
// GNU General Public License for more detail.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    mod
 * @subpackage gallery
 * @copyright  2014 Filip Benco, elf@phil.muni.cz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_gallery_mod_form extends moodleform_mod {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        global $CFG;
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('galleryname', 'gallery'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) 
            $mform->setType('name', PARAM_TEXT);
        else 
            $mform->setType('name', PARAM_CLEANHTML);
        
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->add_intro_editor(true, get_string('description', 'gallery'));

        $mform->addElement('advcheckbox','showthumbnails', get_string('showthumbnails','gallery'));
        $mform->addHelpButton('showthumbnails', 'showthumbnails', 'gallery');
        
        $mform->addElement('advcheckbox','showoriginalimage', get_string('showoriginalimage','gallery'));
        $mform->addHelpButton('showoriginalimage', 'showoriginalimage', 'gallery');
        
        $mform->addElement('advcheckbox','imageattachments', get_string('imageattachments','gallery'));
        $mform->addHelpButton('imageattachments', 'imageattachments', 'gallery');
       
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }
    
    public function getNotSubmittedData() {
        return (object) $this->_form->exportValues();
    }
}