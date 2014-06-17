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

/**
 * Define all the restore steps that will be used by the restore_lightboxgallery_activity_task
 */

/**
 * Structure step to restore one lightboxgallery activity
 */
require_once($CFG->dirroot.'/mod/gallery/locallib.php');
require_once($CFG->dirroot.'/mod/gallery/image.class.php');

class restore_gallery_activity_structure_step extends restore_activity_structure_step {

    protected $imageMapping = array();
    
    protected function define_structure() {

        $paths = array();

        $gallery = new restore_path_element('gallery', '/activity/gallery');
        $paths[] = $gallery;

        $image = new restore_path_element('gallery_image', '/activity/gallery/images/image');
        $paths[] = $image;


        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_gallery($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $newitemid = $DB->insert_record('gallery', $data);
        $this->set_mapping('gallery_id', $oldid, $newitemid, true);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_gallery_image($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->gallery = $this->get_new_parentid('gallery');
        $data->user = $this->get_mappingid('user', $data->user);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        if($data->sourcetype == GALLERY_IMAGE_SOURCE_OWN)
            $data->sourceuser = $this->get_mappingid ('user', $data->sourceuser);
        $newitemid = $DB->insert_record('gallery_images', $data);
        $this->set_mapping('image_id', $oldid, $newitemid);
    }

    protected function after_execute() {
        $this->add_related_files('mod_gallery', 'intro', null);
        $this->add_related_files('mod_gallery', GALLERY_IMAGES_FILEAREA, 'gallery_id');

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->task->get_contextid(), 'mod_gallery', GALLERY_IMAGES_FILEAREA);
        $cms = array();
        $ctxs = array();

        foreach($files as $file) {
            if(!$file->is_valid_image())
                continue;
            
            $gId = $file->get_itemid();
            if(!isset($cms[$gId]))
                $cms[$gId] = get_coursemodule_from_instance('gallery', $gId);
            if(!isset($ctxs[$gId]))
                $ctxs[$gId] = context_module::instance($cms[$gId]->id);
            $iId = $this->get_mapping('image_id', pathinfo($file->get_filename(), PATHINFO_FILENAME))->newitemid;
            $fileinfo = array(
                'contextid' => $ctxs[$gId]->id,
                'component' => 'mod_gallery',
                'filearea' =>  GALLERY_IMAGES_FILEAREA,
                'itemid' => $gId,
                'filepath' => '/',
                'filename' =>  $iId.'.'.pathinfo($file->get_filename(), PATHINFO_EXTENSION)
            );
            $nFile = $fs->create_file_from_storedfile($fileinfo, $file);
            $file->delete();
        }
    }
}
