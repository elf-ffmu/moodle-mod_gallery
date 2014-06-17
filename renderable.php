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

class gallery_view_gallery implements renderable {
    
    public $gallery;
    public $edit;
    public $coursemodule;
    public $images;
    
    public $canadd;
    
    public $canedit;
    public $caneditown;
    
    public $candelete;
    public $candeleteown;
    
    public $candownload;
    
    public $currentuser;
    
    public function __construct(gallery $gallery, array $images, $coursemodule, $edit = 0, $canadd = 0, $canedit = 0, $caneditown = 0, $candelete = 0, $candeleteown = 0, $candownload = 0,$currentuser = 0) {
        $this->gallery = $gallery;
        $this->edit = $edit;
        $this->coursemodule = $coursemodule;
        $this->images = $images;
        $this->canadd = $canadd;
        $this->canedit = $canedit;
        $this->caneditown = $caneditown;
        $this->candelete = $candelete;
        $this->candeleteown = $candeleteown;
        $this->currentuser = $currentuser;
        $this->candownload = $candownload;
    }
}

class gallery_header implements renderable {
    
    public $heading;
    public $subpage;
    public $context;
    
    public function __construct($heading, $context, $subpage = 0) {
        $this->heading = $heading;
        $this->subpage = $subpage;
        $this->context = $context;
    }    
}

class gallery_image_preview implements renderable {
    
    public $image;
    public $thumbnails;
    public $context;
    public $edit;
    public $coursemodule;
    public $user;
    
    public $showoriginalimage;
    
    public $canedit;
    public $caneditown;
    
    public $candelete;
    public $candeleteown;
    
    public $currentuser;
    
    public function __construct($image, $thumbnails, $coursemodule, $context, $showoriginalimage, $edit = 0, $canedit = 0, $caneditown = 0, $candelete = 0, $candeleteown = 0, $currentuser = 0) {
        global $DB;
        $this->image = $image;
        $this->thumbnails = $thumbnails;
        $this->context = $context;
        $this->edit = $edit;
        $this->coursemodule = $coursemodule;
        $this->canedit = $canedit;
        $this->caneditown = $caneditown;
        $this->candelete = $candelete;
        $this->candeleteown = $candeleteown;
        $this->currentuser = $currentuser;
        $this->showoriginalimage = $showoriginalimage;
        if($image->data()->sourcetype == GALLERY_IMAGE_SOURCE_OWN)
            $this->user = $DB->get_record('user',array('id'=>$image->data()->sourceuser));
    }
    
}

class gallery_form implements renderable {
    public $form = null;
    public $classname = '';
    public $jsinitfunction = '';


    public function __construct($classname, moodleform $form, $jsinitfunction = '') {
        $this->classname = $classname;
        $this->form = $form;
        $this->jsinitfunction = $jsinitfunction;
    }

}

class gallery_no_permission implements renderable {
    
    public $cm;
    
    public function __construct($cm) {
        $this->cm = $cm;
    }
}