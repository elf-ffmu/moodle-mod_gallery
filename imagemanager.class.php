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
class gallery_imagemanager {
    
    public static function create_image(stdClass $image) {
        global $DB;
        $image->timecreated = time();
        $image->timemodified = $image->timecreated;
        $image->ordering = $DB->count_records('gallery_images',array('gallery'=>$image->gallery)) + 1;
        $image->id = $DB->insert_record('gallery_images',$image);
        return $image;
    }
    
    public static function update_image(stdClass $image) {
        global $DB;
        $image->timemodified = time();
        $DB->update_record('gallery_images',$image);
    }
    
    public static function get_images($gallery, $from = 0, $limit = 0) {
        global $DB;
        return $DB->get_records('gallery_images',array('gallery'=>$gallery->id()),'ordering ASC','*',$from,$limit);
    }
    
    public static function get_image($id) {
        global $DB;
        return $DB->get_record('gallery_images',array('id'=>$id));
    }
    
    public static function delete_image($id) {
        global $DB;
        $img = $DB->get_record('gallery_images',array('id'=>$id));
        $DB->execute('UPDATE {gallery_images} SET ordering=ordering-1 WHERE ordering > ?', array($img->ordering));
        $DB->delete_records('gallery_images',array('id' => $id));
    }
    
    public static function count_images($galleryId) {
        global $DB;
        return $DB->count_records('gallery_images',array('gallery'=>$galleryId));
    }
}
