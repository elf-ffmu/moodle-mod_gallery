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

require_once("$CFG->libdir/filelib.php");

define('GALLERY_IMAGES_FILEAREA','gallery_images');
define('GALLERY_IMAGE_THUMBS_FILEAREA','gallery_thumbs');
define('GALLERY_IMAGE_PREVIEWS_FILEAREA','gallery_previews');
define('GALLERY_IMAGE_DRAFTS_FILEAREA','gallery_drafts');
define('GALLERY_IMAGE_ATTACHMENTS_FILEAREA','gallery_attachments');

function gallery_process_editing($edit, $context) {
    global $USER;

    if (has_capability('mod/gallery:manage', $context) || 
            has_capability('mod/gallery:editallimages', $context) ||
            has_capability('mod/gallery:editownimages', $context) ||
            has_capability('mod/gallery:deleteallimages', $context) ||
            has_capability('mod/gallery:deleteownimages', $context) ||
            has_capability('mod/gallery:addimages', $context)) {
        if ($edit != -1 and confirm_sesskey()) 
            $USER->editing = $edit;
    } else
        $USER->editing = 0;
    if(!isset($USER->editing))
        $USER->editing = 0;
}

function gallery_process_drafts($context, $gallery) {
    global $CFG, $USER, $PAGE;
    require_once($CFG->dirroot.'/mod/gallery/image.class.php');
    
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_gallery', GALLERY_IMAGE_DRAFTS_FILEAREA, $gallery->id());
    $fs->delete_area_files($context->id, 'mod_gallery', 'unpacktemp');
    
    $draftid = file_get_submitted_draft_itemid('images');
    if (!$files = $fs->get_area_files(
        get_context_instance(CONTEXT_USER, $USER->id)->id, 'user', 'draft', $draftid, 'filename ASC', false)) {
        redirect($PAGE->url);
    }

    $preloaded_images = array();
    
    $i = 1;
    foreach($files as $file) {
        if(!$file->is_valid_image()) {
            $packer = get_file_packer($file->get_mimetype());
            if($packer) {
                $file->extract_to_storage($packer, $context->id, 'mod_gallery', 'unpacktemp', $i, '/');
                $unpackedFiles = $fs->get_area_files($context->id, 'mod_gallery', 'unpacktemp', $i,'filename ASC');
                $preloaded_images = array_merge($preloaded_images, $unpackedFiles);
                $file->delete();
                $i++;
            }
        } 
            $preloaded_images[] = $file;
    }
    
    $images = array();
    foreach ($preloaded_images as $file) {
        $data = gallery_image::get_initial_data();
        $data->name = $file->get_filename();
        if ($file->is_valid_image()) {
            $fileinfo = array(
                'contextid' => $context->id,
                'component' => 'mod_gallery',
                'filearea' =>  GALLERY_IMAGE_DRAFTS_FILEAREA,
                'itemid' => $gallery->id(),
                'filepath' => '/',
                'filename' =>  $file->get_filename()
            );
            if (!$fs->get_file($context->id, 'mod_gallery', GALLERY_IMAGE_DRAFTS_FILEAREA, $gallery->id(), '/', $file->get_filename())) {
                $file = $fs->create_file_from_storedfile($fileinfo, $file);
                $images[] = new gallery_image($data,$file,null,false);
            }
        }
    }
    $fs->delete_area_files($context->id, 'mod_gallery', 'unpacktemp');
    
    return $images;
}

function gallery_get_draft_images($context, $gallery) {
    global $PAGE, $CFG;
    $fs = get_file_storage();
    if (!$files = $fs->get_area_files(
        $context->id, 'mod_gallery', GALLERY_IMAGE_DRAFTS_FILEAREA, $gallery->id(), 'filename ASC', false)) {
        redirect($PAGE->url);
    }
    
    require_once($CFG->dirroot.'/mod/gallery/image.class.php');
    $images = array();
    foreach($files as $file) {
        $data = gallery_image::get_initial_data();
        $data->name = $file->get_filename();
        $images[] = new gallery_image($data,$file,null,false);
    }
    return $images;
}

function gallery_process_image_drats_save($data, $context, $gallery, $files) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/gallery/image.class.php');
    require_once($CFG->dirroot.'/mod/gallery/imagemanager.class.php');

    $fs = get_file_storage();
    foreach ($files as $file) {
        $uId = clean_param($file->stored_file()->get_filename(), PARAM_ALPHANUM);
        $imgData = gallery_image::from_form_data($uId, $data);
        $imgData->gallery = $gallery->id();
        $imgData->type = strtolower(pathinfo($file->stored_file()->get_filename(), PATHINFO_EXTENSION));
        $image_data = gallery_imagemanager::create_image($imgData);
        
        $filename = $image_data->id.'.'.  strtolower(pathinfo($file->stored_file()->get_filename(), PATHINFO_EXTENSION));
        $fileinfo = array(
            'contextid' => $context->id,
            'component' => 'mod_gallery',
            'filearea' =>  GALLERY_IMAGES_FILEAREA,
            'itemid' => $gallery->id(),
            'filepath' => '/',
            'filename' =>  $filename
        );
        if (!$fs->get_file($context->id, 'mod_gallery', GALLERY_IMAGES_FILEAREA, $gallery->id(), '/', $filename)) {
            $file = $fs->create_file_from_storedfile($fileinfo, $file->stored_file());
            $image = new gallery_image($image_data, $file, $context);
            if($gallery->imageattachments()) {
                $attachmentsStr = 'attachments-'.$uId;
                file_save_draft_area_files($data->$attachmentsStr, $context->id, 'mod_gallery', GALLERY_IMAGE_ATTACHMENTS_FILEAREA, $image->id(), array('subdirs' => 0)); 
            }
        }
    }
    $fs->delete_area_files($context->id, 'mod_gallery', GALLERY_IMAGE_DRAFTS_FILEAREA, $gallery->id());
}

function gallery_process_images_save($data, $images,$context,$gallery) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/gallery/imagemanager.class.php');
    require_once($CFG->dirroot.'/mod/gallery/image.class.php');
    
    foreach($images as $image) {
        $imgData = $image->from_form($data);
        if($image->data()->name != $imgData->name ||
                $image->data()->description != $imgData->description ||
                $image->data()->descriptionformat != $imgData->descriptionformat ||
                $image->data()->sourcetype != $imgData->sourcetype ||
                ($image->data()->sourcetype == GALLERY_IMAGE_SOURCE_TEXT && $image->data()->sourcetext != $imgData->sourcetext)) {
            gallery_imagemanager::update_image($imgData);
        }
        if($gallery->imageattachments()) {
            $attachmentsStr = 'attachments-'.$image->id();
            file_save_draft_area_files($data->$attachmentsStr, $context->id, 'mod_gallery', GALLERY_IMAGE_ATTACHMENTS_FILEAREA, $image->id(), array('subdirs' => 0)); 
        }
    }
}

function gallery_load_images($gallery, $context, $iid = false) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/gallery/image.class.php');
    require_once($CFG->dirroot.'/mod/gallery/imagemanager.class.php');
    
    $images_db = gallery_imagemanager::get_images($gallery);
    
    $images = array();
    $fs = get_file_storage();
    foreach($images_db as $idb) {
        if($iid && $idb->id == $iid) {
            $images[$idb->id] = new gallery_image($idb, $fs->get_file($context->id, 'mod_gallery', GALLERY_IMAGES_FILEAREA, $gallery->id(), '/',
                                       $idb->id.'.'.$idb->type),$context,true,true);
        } else {
            $images[$idb->id] = new gallery_image($idb, $fs->get_file($context->id, 'mod_gallery', GALLERY_IMAGES_FILEAREA, $gallery->id(), '/',
                                       $idb->id.'.'.$idb->type),$context);   
        }
    }
    return $images;
}

function gallery_load_image($context,$image_db) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/gallery/image.class.php');
    require_once($CFG->dirroot.'/mod/gallery/imagemanager.class.php');
    
    $fs = get_file_storage();
    
    return new gallery_image($image_db, $fs->get_file($context->id, 'mod_gallery', GALLERY_IMAGES_FILEAREA, $image_db->gallery, '/',
                                       $image_db->id.'.'.$image_db->type),$context,true,true);
}

function gallery_process_rotate_image($direction,$image) {
    $angle = 90;
    if($direction == 'right')
        $angle = 270;
    $image->rotate($angle);
}

function gallery_process_delete_image($img, $context, $gallery) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/gallery/image.class.php');
    require_once($CFG->dirroot.'/mod/gallery/imagemanager.class.php');
    require_once($CFG->dirroot.'/comment/lib.php');
    $fs = get_file_storage();
    $img->delete();
    $fs->delete_area_files($context->id, 'mod_gallery', GALLERY_IMAGE_ATTACHMENTS_FILEAREA, $gallery->id());
    gallery_imagemanager::delete_image($img->id());
    comment::delete_comments(array('contextid'=>$context->id,'commentarea'=>'gallery_image_comments','itemid'=>$img->id()));
}

function gallery_get_packed_images($images, $gallery, $context) {
    global $USER, $DB;
    $packer = get_file_packer('application/zip');
    $fs = get_file_storage();
    $fs->delete_area_files($context->id,'mod_gallery','gallery_packed_images');

    $preparedFiles = array();
    $users = array();
    foreach($images as $img) {
        if(!isset($users[$img->data()->user]))
            $users[$img->data()->user] = $DB->get_record('user',array('id'=>$img->data()->user));
        $preparedFiles[fullname($users[$img->data()->user]).' - img'.$img->stored_file()->get_filename()] = $img->stored_file();
    }
    return $packer->archive_to_storage($preparedFiles, $context->id, 'mod_gallery', 'gallery_packed_images', $gallery->id(), '/', $gallery->id().'-'.$gallery->name().'.zip', $USER->id);
}

function gallery_load_batch_images($gallery, $context) {
    $images = gallery_load_images($gallery, $context);
    $batchImages = array();
    foreach($images as $image) {
        if(isset($_POST['mod-gallery-batch-'.$image->id()]))
            $batchImages[$image->id()] = $image;
    }
    return $batchImages;
}

class gallery_content_file_info extends file_info_stored {
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}