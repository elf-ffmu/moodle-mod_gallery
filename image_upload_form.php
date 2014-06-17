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
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');


require_once($CFG->libdir.'/formslib.php');

class mod_gallery_image_upload_form extends moodleform {
    
    
    protected function definition() {
        $mform = $this->_form;
        
        $mform->addElement('filemanager','images',  get_string('images','gallery'), null, 
                array('subdirs'=>0,'accepted_types'=>array('web_image','archive')));
        
        $mform->addElement('hidden','gaction','addimages');
        $mform->setType('gaction', PARAM_ALPHA);
        $mform->addElement('hidden','id',$this->_customdata['id']);
        $mform->setType('id',PARAM_INT);
        
        $this->add_action_buttons(true, get_string('uploadimages','gallery'));
    }    
}