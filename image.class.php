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

define('THUMBNAIL_WIDTH',150);
define('THUMBNAIL_HEIGHT',150);

define('PREVIEW_WIDTH',1024);
define('PREVIEW_HEIGHT',400);

define('GALLERY_IMAGE_SOURCE_OWN',1);
define('GALLERY_IMAGE_SOURCE_TEXT',2);

require_once($CFG->libdir.'/gdlib.php');

class gallery_image {
    
    protected $data;
    protected $thumbnail;
    protected $preview;
    protected $image;
    
    protected $image_url;
    protected $preview_url;
    protected $thumb_url;
    
    protected $width;
    protected $height;
    
    protected $t_width;
    protected $t_height;
    
    protected $p_width;
    protected $p_height;
    
    protected $context;
    
    protected $attachments;
    
    public function __construct($data, $file, $context, $prepare = true, $attachments = false) {
       
        $this->data = $data;
        $this->context = $context;
        $this->image = $file;
        
        if($prepare) {
            $image_info = $this->image->get_imageinfo();

            $this->height = $image_info['height'];
            $this->width = $image_info['width'];
            
            $this->thumbnail = $this->get_thumbnail();
            if($this->thumbnail == null)
                $this->thumbnail = $this->create_thumbnail();
            $this->load_thumbnail_info();
            
            $this->preview = $this->get_preview();
            if($this->preview == null)
                $this->preview = $this->create_preview();
            $this->load_preview_info();
            
            $this->image_url = moodle_url::make_pluginfile_url($this->image->get_contextid(), $this->image->get_component(), 
                    $this->image->get_filearea(), $this->image->get_itemid(), 
                    $this->image->get_filepath(), $this->image->get_filename());
            $this->thumb_url = moodle_url::make_pluginfile_url($this->thumbnail->get_contextid(), $this->thumbnail->get_component(), 
                    $this->thumbnail->get_filearea(), $this->thumbnail->get_itemid(), 
                    $this->thumbnail->get_filepath(), $this->thumbnail->get_filename());
            $this->preview_url = moodle_url::make_pluginfile_url($this->preview->get_contextid(), $this->preview->get_component(), 
                    $this->preview->get_filearea(), $this->preview->get_itemid(), 
                    $this->preview->get_filepath(), $this->preview->get_filename());
        }
        if($attachments) {
            $fs = get_file_storage();
            $this->attachments = $fs->get_area_files($this->context->id, 'mod_gallery', GALLERY_IMAGE_ATTACHMENTS_FILEAREA, $this->data->id, 'filename ASC');
        }
    }
        
    public function id() {
        return $this->data->id;
    }
    
    public function thumbnail() {
        return $this->thumb_url;
    }
    
    public function image() {
        return $this->image_url;
    }
    
    public function preview() {
        return $this->preview_url;
    }
    
    public function width() {
        return $this->width;
    }
    
    public function height() {
        return $this->height;
    }
    
    public function data() {
        return $this->data;
    }
    
    public function attachments() {
        return $this->attachments;
    }
    
    public function t_width() {
        return $this->t_width;
    }
    
    public function t_height() {
        return $this->t_height;
    }
    
    public function p_width() {
        return $this->p_width;
    }
    
    public function p_height() {
        return $this->p_height;
    }
    
    public function stored_file() {
        return $this->image;
    }
    
    public static function get_initial_data() {
        global $USER;
        $data = new stdClass;
        $data->id = 0;
        $data->descriptionformat = FORMAT_HTML;
        $data->description = '';
        $data->sourcetext = '';
        $data->sourceuser = 0;
        $data->sourcetype = GALLERY_IMAGE_SOURCE_TEXT;
        $data->name = '';
        $data->user = $USER->id;
        return $data;
    }
    
    public function from_form($data) {
        return gallery_image::parse_from_form($this->id(), $data, clone $this->data);
    }
    
    public static function from_form_data($uId, $data) {
        $image_data = gallery_image::get_initial_data();
        
        return gallery_image::parse_from_form($uId, $data, $image_data);
    }
    
    private static function parse_from_form($uId, $data, $image) {
        global $USER;
        $descName = 'desc-'.$uId;  $descData = $data->$descName;
        $image->description = $descData['text'];
        $image->descriptionformat = $descData['format'];
        
        $nameName = 'name-'.$uId; 
        $image->name = $data->$nameName;
        
        $sourceTypeName = 'sourcetype-'.$uId;
        if(isset($data->$sourceTypeName)) {
            $image->sourcetype = GALLERY_IMAGE_SOURCE_OWN;
            $image->sourceuser = $USER->id;
            $image->sourcetext = '';
        } else {
            $image->sourcetype = GALLERY_IMAGE_SOURCE_TEXT;
            $sourceName = 'source-'.$uId;
            $image->sourcetext = $data->$sourceName;
            $image->sourceuser = 0;
        }
        
        return $image;
    }
    
    public function rotate($angle) {
        $fileinfo = array(
            'contextid' => $this->context->id,
            'component' => 'mod_gallery',
            'filearea' => $this->image->get_filearea(),
            'itemid' => $this->image->get_itemid(),
            'filepath' => $this->image->get_filepath(),
            'filename' => $this->image->get_filename());

        ob_start();
        imagepng($this->get_image_rotated($angle));
        $image = ob_get_clean();

        $fs = get_file_storage();
        $this->image->delete();
        $this->image = $fs->create_file_from_string($fileinfo, $image);
        $image_info = $this->image->get_imageinfo();
        $this->height = $image_info['height'];
        $this->width = $image_info['width'];
        $this->preview = $this->create_preview();
        $this->thumbnail = $this->create_thumbnail();
        $this->load_preview_info();
        $this->load_thumbnail_info();
    }
    
    public function delete() {
        $this->delete_preview();
        $this->delete_thumbnail();
        $this->delete_image();
    }
    
    protected function get_thumbnail() {
        $fs = get_file_storage();
        $thumbnail = $fs->get_file($this->context->id, 'mod_gallery', GALLERY_IMAGE_THUMBS_FILEAREA, $this->image->get_itemid(), $this->image->get_filepath(),
                                       $this->data->id.'.png');
        return $thumbnail;
    }
    
    protected function get_preview() {
        $fs = get_file_storage();
        $preview = $fs->get_file($this->context->id, 'mod_gallery', GALLERY_IMAGE_PREVIEWS_FILEAREA, $this->image->get_itemid(), $this->image->get_filepath(),
                                       $this->data->id.'.png');
        return $preview;
    }
    
    protected function load_preview_info() {
        if (isset($this->preview)) {
            $image_info = $this->preview->get_imageinfo();

            $this->p_height = $image_info['height'];
            $this->p_width = $image_info['width'];
        }
    }
    
    protected function load_thumbnail_info() {
        if (isset($this->thumbnail)) {
            $image_info = $this->thumbnail->get_imageinfo();

            $this->t_height = $image_info['height'];
            $this->t_width = $image_info['width'];
        }
    }
    
    public function create_thumbnail() {
        $fileinfo = array(
            'contextid' => $this->context->id,
            'component' => 'mod_gallery',
            'filearea' => GALLERY_IMAGE_THUMBS_FILEAREA,
            'itemid' => $this->image->get_itemid(),
            'filepath' => $this->image->get_filepath(),
            'filename' => $this->data->id.'.png');

        ob_start();
        imagepng($this->get_image_resized(THUMBNAIL_WIDTH, THUMBNAIL_HEIGHT));
        $thumbnail = ob_get_clean();

        $this->delete_thumbnail();
        $fs = get_file_storage();
        return $fs->create_file_from_string($fileinfo, $thumbnail);
    }
    
    public function create_preview() {
        $fileinfo = array(
            'contextid' => $this->context->id,
            'component' => 'mod_gallery',
            'filearea' => GALLERY_IMAGE_PREVIEWS_FILEAREA,
            'itemid' => $this->image->get_itemid(),
            'filepath' => $this->image->get_filepath(),
            'filename' => $this->data->id.'.png');

        ob_start();
        imagepng($this->get_image_resized(PREVIEW_WIDTH, PREVIEW_HEIGHT));
        $preview = ob_get_clean();

        $this->delete_preview();
        $fs = get_file_storage();
        return $fs->create_file_from_string($fileinfo, $preview);
    }
    
    protected function delete_thumbnail() {
        if (isset($this->thumbnail) && is_object($this->thumbnail)) {
            $this->thumbnail->delete();
            unset($this->thumbnail);
        }
    }
    
    protected function delete_preview() {
        if (isset($this->preview) && is_object($this->preview)) {
            $this->preview->delete();
            unset($this->preview);
        }
    }
    
    protected function delete_image() {
        if (isset($this->image) && is_object($this->image)) {
            $this->image->delete();
            unset($this->image);
        }
    }
    
    protected function get_image_resized($width, $height) {
		ini_set('memory_limit','256M');
        $image = imagecreatefromstring($this->image->get_content());
        
        $ratiow = $width / $this->width;
        $ratioh = $height / $this->height;

        if($ratiow>=1 && $ratioh>=1)
            return $image;
        
        if ($ratiow < $ratioh)
            $height =floor($this->height*$ratiow);
        else
            $width =floor($this->width*$ratioh);
        
        $resized = imagecreatetruecolor($width, $height);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);

        return $resized;
    }
    
    protected function get_image_rotated($angle) {
        $image = imagecreatefromstring($this->image->get_content());
        $rotated = imagerotate($image, $angle, 0);

        return $rotated;
    }
}