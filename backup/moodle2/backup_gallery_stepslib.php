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
 * Define all the backup steps that will be used by the backup_lightboxgallery_activity_task
 */

/**
 * Define the complete lightboxgallery structure for backup, with file and id annotations
 */
require_once($CFG->dirroot.'/mod/gallery/locallib.php');

class backup_gallery_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        // Define each element separated.
        $gallery = new backup_nested_element('gallery', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'showdescription', 
            'showthumbnails', 'imageattachments', 'timemodified', 'timecreated'
        ));

        $images = new backup_nested_element('images');
        $image = new backup_nested_element('image', array('id'), array(
            'gallery', 'user', 'description', 'descriptionformat',
            'sourcetext','sourceuser','sourcetype','ordering',
            'type','timemodified','timecreated'
        ));

        // Build the tree.
        $gallery->add_child($images);
        $images->add_child($image);

        // Define sources.
        $gallery->set_source_table('gallery', array('id' => backup::VAR_ACTIVITYID));
        $image->set_source_table('gallery_images', array('gallery' => backup::VAR_PARENTID));

        // Define file annotations.
        $gallery->annotate_files('mod_gallery', 'intro', null);
        $gallery->annotate_files('mod_gallery', GALLERY_IMAGES_FILEAREA, 'id');
        
        $image->annotate_ids('user', 'user');
        $image->annotate_ids('user', 'sourceuser');
        
        // Return the root element (lightboxgallery), wrapped into standard activity structure.
        return $this->prepare_activity_structure($gallery);
    }
}
