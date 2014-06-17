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

class mod_gallery_image_edit_form extends moodleform {
    
    
    protected function definition() {
        $mform = $this->_form;
        
        $action = $this->_customdata['action'];
        $gallery = $this->_customdata['gallery'];
        
        $data = array();
        foreach($this->_customdata['images'] as $image) {
            if($action == 'batchedit') {
                $mform->addElement ('hidden','mod-gallery-batch-'.$image->id(),'1');
                $mform->setType('mod-gallery-batch-'.$image->id(),PARAM_BOOL);
            }
            
            $uniqueId = '';
            $imagePreview = '';
            
            if($action == 'addimagedesc') {
                $uniqueId = clean_param($image->stored_file()->get_filename(), PARAM_ALPHANUM);
                $imagePreview =  moodle_url::make_pluginfile_url($image->stored_file()->get_contextid(), $image->stored_file()->get_component(), 
                        $image->stored_file()->get_filearea(), $image->stored_file()->get_itemid(), 
                        $image->stored_file()->get_filepath(), $image->stored_file()->get_filename());
            } else {
                $uniqueId = $image->id();
                $imagePreview = $image->thumbnail();
            }
            
            $mform->addElement('header','header-'.$uniqueId,$image->data()->name);
            $mform->setExpanded('header-'.$uniqueId);
            
            $mform->addElement('text','name-'.$uniqueId,  get_string('imagename','gallery'),array('size'=>'40'));
            $mform->setType('name-'.$uniqueId, PARAM_TEXT);
            
            $mform->addElement('editor', 'desc-'.$uniqueId,'<img src="'.$imagePreview.'" style="max-width:136px; max-height:150px;" />',
                    array('rows' => 3), array('collapsed' => true));
            $mform->setType('desc-'.$uniqueId, PARAM_RAW);
            
            if($gallery->imageattachments())
                $mform->addElement('filemanager', 'attachments-'.$uniqueId, get_string('attachments', 'gallery'), null, array('subdirs' => 0));
            
            $mform->addElement('checkbox', 'sourcetype-'.$uniqueId, get_string('sourceown','gallery'));
            $mform->setType('sourcetype-'.$uniqueId, PARAM_BOOL);
            
            $mform->addElement('text','source-'.$uniqueId,  get_string('source','gallery'), array('size'=>'70'));
            $mform->setType('source-'.$uniqueId,PARAM_TEXT);
            $mform->disabledIf('source-'.$uniqueId, 'sourcetype-'.$uniqueId, 'checked');
            
            $data['name-'.$uniqueId] = $image->data()->name;
            $data['desc-'.$uniqueId]['text'] = $image->data()->description;
            $data['desc-'.$uniqueId]['format'] = $image->data()->descriptionformat;
            if($image->data()->sourcetype == GALLERY_IMAGE_SOURCE_TEXT) {
                $data['source-'.$uniqueId] = $image->data()->sourcetext;
                $data['sourcetype-'.$uniqueId] = false;
            } elseif($image->data()->sourcetype == GALLERY_IMAGE_SOURCE_OWN) {
                $data['source-'.$uniqueId] = '';
                $data['sourcetype-'.$uniqueId] = true;
            }
            if($action != 'addimagedesc' && $gallery->imageattachments()) {
                $draftitemid = file_get_submitted_draft_itemid('attachments-'.$uniqueId);
                file_prepare_draft_area($draftitemid, $this->_customdata['contextid'], 'mod_gallery', GALLERY_IMAGE_ATTACHMENTS_FILEAREA, $image->id(),array('subdirs' => 0));
                $data['attachments-'.$uniqueId] = $draftitemid;
            }
        }
        
        $mform->addElement('hidden','gaction',$action);
        if($action == 'editimage' || $action == 'editimageg') {
            $mform->addElement('hidden','image',$this->_customdata['image']);
            $mform->setType('image',PARAM_INT);
        }
        $mform->setType('gaction', PARAM_ALPHA);
        $mform->addElement('hidden','id',$this->_customdata['id']);
        $mform->setType('id',PARAM_INT);
        
        if($action == 'editimage' || $action == 'editimageg' || $action == 'batchedit') {
            $this->add_action_buttons(true, get_string('savechanges','gallery'));
        } else {
            $this->add_action_buttons(true, get_string('saveimages','gallery'));
        }
        
        $this->set_data($data);
    }   
    
    /**
     * Perform minimal validation on the grade form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        $action = $this->_customdata['action'];
        foreach($this->_customdata['images'] as $image) {
            $uniqueId = '';
            if($action == 'addimagedesc') 
                $uniqueId = clean_param($image->stored_file()->get_filename(), PARAM_ALPHANUM);  
            else 
                $uniqueId = $image->id();
            
            $sourceTypeName = 'sourcetype-'.$uniqueId;
            $sourceName = 'source-'.$uniqueId;
            $source = '';
            if(isset($data[$sourceName])) 
                $source = trim($data[$sourceName]);
            if(!isset($data[$sourceTypeName]) && empty($source))
                $errors[$sourceName] = get_string('missingsourceerror','gallery');
        }
        return $errors;
    }
}