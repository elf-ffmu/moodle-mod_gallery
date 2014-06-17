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

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/gallery/gallery.class.php');
require_once($CFG->dirroot . '/mod/gallery/renderable.php');
require_once($CFG->dirroot . '/mod/gallery/locallib.php');

$id = required_param('id', PARAM_INT);
$edit = optional_param('edit', -1, PARAM_BOOL);
$action = optional_param('gaction', 'gallery', PARAM_ALPHA);
$iid = optional_param('image', 0, PARAM_INT);

$cm = get_coursemodule_from_id('gallery', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

$PAGE->set_context($context);
$PAGE->set_cm($cm);

$urlparams = array('id' => $id, 'action' => $action);
$url = new moodle_url('/mod/gallery/view.php', $urlparams);
require_login($course, true, $cm);
$PAGE->set_url($url);


gallery_process_editing($edit, $context);

require_capability('mod/gallery:view', $context);

$renderer = $PAGE->get_renderer('mod_gallery');
$gallery = new gallery($cm->instance);

//preprocess actions
$mform = null;
$images = null;
$loadImages = true;
if($action == 'addimages') {
    if(!has_capability('mod/gallery:addimages', $context) && !has_capability('mod/gallery:manage', $context))
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=nopermission');
    require_once($CFG->dirroot.'/mod/gallery/image_upload_form.php');
    $mform = new mod_gallery_image_upload_form(null,array('id'=>$cm->id));
    if ($mform->is_cancelled()) 
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id);
    if (($formdata = $mform->get_data()) && confirm_sesskey()) {
        $images = gallery_process_drafts($context,$gallery);
        $action = 'addimagedesc'; $loadImages = false;
    }
}
if($action == 'addimagedesc') {
    if(!has_capability('mod/gallery:addimages', $context) && !has_capability('mod/gallery:manage', $context))
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=nopermission');
    
    require_once($CFG->dirroot.'/mod/gallery/image_edit_form.php');
    if($loadImages)
        $images = gallery_get_draft_images ($context, $gallery);
    $mform = new mod_gallery_image_edit_form(null,array('action'=>'addimagedesc','gallery'=>$gallery,'images'=>$images,'id'=>$cm->id,'contextid'=>$context->id));
    if ($mform->is_cancelled()) 
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id);
    if (($formdata = $mform->get_data()) && confirm_sesskey()) {
        $images = gallery_process_image_drats_save($formdata, $context, $gallery, $images);
        rebuild_course_cache($gallery->course(),true);
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id);
    }
}
if($action == 'imagedelete') {
    require_once($CFG->dirroot.'/mod/gallery/imagemanager.class.php');
    $img = gallery_imagemanager::get_image($iid);
    if(has_capability('mod/gallery:manage', $context) || has_capability('mod/gallery:deleteallimages', $context) || (has_capability('mod/gallery:deleteownimages', $context) && $USER->id == $img->user)) {
        gallery_process_delete_image(gallery_load_image($context, $img), $context, $gallery);
        rebuild_course_cache($gallery->course(),true);
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id);
    } else 
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=nopermission');
}
if($action == 'rotateleftg') {
    require_once($CFG->dirroot.'/mod/gallery/imagemanager.class.php');
    $img = gallery_imagemanager::get_image($iid);
    if(has_capability('mod/gallery:manage', $context) || has_capability('mod/gallery:editallimages', $context) || (has_capability('mod/gallery:editownimages', $context) && $USER->id == $img->user)) {
        gallery_process_rotate_image('left',gallery_load_image($context, $img),$context);
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id);
    } else 
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=nopermission');
}
if($action == 'rotaterightg') {
    require_once($CFG->dirroot.'/mod/gallery/imagemanager.class.php');
    $img = gallery_imagemanager::get_image($iid);
    if(has_capability('mod/gallery:manage', $context) || has_capability('mod/gallery:editallimages', $context) || (has_capability('mod/gallery:editownimages', $context) && $USER->id == $img->user)) {
        gallery_process_rotate_image('right',gallery_load_image($context, $img),$context);
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id);
    } else
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=nopermission');
}
if($action == 'rotatelefti') {
    require_once($CFG->dirroot.'/mod/gallery/imagemanager.class.php');
    $img = gallery_imagemanager::get_image($iid);
    if(has_capability('mod/gallery:manage', $context) || has_capability('mod/gallery:editallimages', $context) || (has_capability('mod/gallery:editownimages', $context) && $USER->id == $img->user)) {
        gallery_process_rotate_image('left',gallery_load_image($context, $img),$context);
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=image&image='.$iid);
    } else
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=nopermission');
}
if($action == 'rotaterighti') {
    require_once($CFG->dirroot.'/mod/gallery/imagemanager.class.php');
    $img = gallery_imagemanager::get_image($iid);
    if(has_capability('mod/gallery:manage', $context) || has_capability('mod/gallery:editallimages', $context) || (has_capability('mod/gallery:editownimages', $context) && $USER->id == $img->user)) {
        gallery_process_rotate_image('right',gallery_load_image($context, $img),$context);
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=image&image='.$iid);
    } else
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=nopermission');
}
if($action == 'editimageg') {
    require_once($CFG->dirroot.'/mod/gallery/image_edit_form.php');
    require_once($CFG->dirroot.'/mod/gallery/imagemanager.class.php');
    $img = gallery_imagemanager::get_image($iid);
    if(has_capability('mod/gallery:manage', $context) || has_capability('mod/gallery:editallimages', $context) || (has_capability('mod/gallery:editownimages', $context) && $USER->id == $img->user)) {
        $images = array(gallery_load_image($context, $img));
        $mform = new mod_gallery_image_edit_form(null,array('action'=>'editimageg','gallery'=>$gallery,'images'=>$images,'id'=>$cm->id,'contextid'=>$context->id, 'image'=>$iid));
        if ($mform->is_cancelled()) 
            redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id);
        if (($formdata = $mform->get_data()) && confirm_sesskey()) {
            gallery_process_images_save($formdata, $images, $context, $gallery);
            redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id);
        }
    } else
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=nopermission');
}
if($action == 'editimage') {
    require_once($CFG->dirroot.'/mod/gallery/image_edit_form.php');
    require_once($CFG->dirroot.'/mod/gallery/imagemanager.class.php');
    $img = gallery_imagemanager::get_image($iid);
    if(has_capability('mod/gallery:manage', $context) || has_capability('mod/gallery:editallimages', $context) || (has_capability('mod/gallery:editownimages', $context) && $USER->id == $img->user)) {
        $images = array(gallery_load_image($context, $img));
        $mform = new mod_gallery_image_edit_form(null,array('action'=>'editimage','gallery'=>$gallery,'images'=>$images,'id'=>$cm->id,'contextid'=>$context->id, 'image'=>$iid));
        if ($mform->is_cancelled()) 
            redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=image&image='.$iid);
        if (($formdata = $mform->get_data()) && confirm_sesskey()) {
            gallery_process_images_save($formdata, $images, $context, $gallery);
            redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=image&image='.$iid);
        }
    } else
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=nopermission');
}
if($action == 'batchedit') {
    if(has_capability('mod/gallery:editallimages', $context) || has_capability('mod/gallery:manage', $context)) {    
        require_once($CFG->dirroot.'/mod/gallery/image_edit_form.php');
        $images = gallery_load_batch_images($gallery, $context);
        $mform = new mod_gallery_image_edit_form(null,array('action'=>'batchedit','gallery'=>$gallery,'images'=>$images,'id'=>$cm->id,'contextid'=>$context->id));
        if ($mform->is_cancelled()) 
            redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id);
        if (($formdata = $mform->get_data()) && confirm_sesskey()) {
            gallery_process_images_save($formdata, $images, $context, $gallery);
            redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id);
        }
    } else 
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=nopermission');
}
if($action == 'batchrotateleft') {
    if(has_capability('mod/gallery:editallimages', $context) || has_capability('mod/gallery:manage', $context)) {
        $images = gallery_load_batch_images($gallery, $context);
        foreach($images as $img)
            gallery_process_rotate_image('left',$img,$context);
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id);
    } else
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=nopermission');
}
if($action == 'batchrotateright') {
    if(has_capability('mod/gallery:editallimages', $context) || has_capability('mod/gallery:manage', $context)) {
        $images = gallery_load_batch_images($gallery, $context);
        foreach($images as $img)
            gallery_process_rotate_image('right',$img,$context);
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id);
    } else
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=nopermission');
}
if($action == 'batchdelete') {
    if(has_capability('mod/gallery:deleteallimages', $context) || has_capability('mod/gallery:manage', $context)) {
        $images = gallery_load_batch_images($gallery, $context);
        foreach($images as $img)
            gallery_process_delete_image($img, $context, $gallery);
        rebuild_course_cache($gallery->course(),true);
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id);
    } else
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=nopermission');
}
if($action == 'batchdownload') {
    if(!has_capability('mod/gallery:manage', $context)) 
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=nopermission');
    
    $gallery = new gallery($cm->instance);

    $images = gallery_load_batch_images($gallery, $context);
    $packedPhotos = gallery_get_packed_images($images,$gallery,$context);
    if($packedPhotos) 
        send_stored_file ($packedPhotos);
    die;
}
if($action == 'downloadall') {
    if(!has_capability('mod/gallery:manage', $context)) 
        redirect($CFG->wwwroot.'/mod/gallery/view.php?id='.$cm->id.'&gaction=nopermission');
    if(confirm_sesskey()) {
        require_once($CFG->dirroot . '/mod/gallery/gallery.class.php');
        require_once($CFG->dirroot.'/mod/gallery/locallib.php');
        $gallery = new gallery($cm->instance);

        $images = gallery_load_images($gallery, $context);
        $packedPhotos = gallery_get_packed_images($images,$gallery,$context);
        if($packedPhotos) 
            send_stored_file ($packedPhotos);
        die;
    }
}


switch($action) {
    case 'gallery':
        if($USER->editing) {
            $PAGE->requires->js('/mod/gallery/js/edit.js',true);
            $module = array(
        		'name'      => 'mod_gallery',
        		'fullpath'  => '/mod/gallery/js/edit.js',
        		'requires'  => array('base', 'dom', 'dd-drop', 'dd-proxy', 'io'),
                        'strings'   => array(array('confirmdelete', 'gallery'))
            );
            $PAGE->requires->js_init_call('M.mod_gallery.init', array(array('context'=>$context->id)), false, $module);
        
        }
        echo $renderer->render(new gallery_header($gallery->name(),$context));
        $images = gallery_load_images($gallery, $context);
        if($USER->editing)
            echo $renderer->render(new gallery_view_gallery($gallery, $images, $cm, 
                    $USER->editing, has_capability('mod/gallery:addimages', $context),
                    has_capability('mod/gallery:editallimages', $context), has_capability('mod/gallery:editownimages', $context),
                    has_capability('mod/gallery:deleteallimages', $context), has_capability('mod/gallery:deleteownimages', $context),  has_capability('mod/gallery:manage', $context), $USER->id));     
        else
            echo $renderer->render(new gallery_view_gallery($gallery, $images, $cm));
        break;
    case 'image':
        $PAGE->requires->css('/mod/gallery/css/lightbox.css');
        $PAGE->requires->js('/mod/gallery/js/jquery-1.10.2.min.js');
        $PAGE->requires->js('/mod/gallery/js/lightbox-2.6.min.js');        
        $PAGE->requires->js('/mod/gallery/js/module.js',true);
        $module = array(
        		'name'      => 'mod_gallery',
        		'fullpath'  => '/mod/gallery/js/module.js',
        		'requires'  => array('base', 'dom', 'event','io'),
                        'strings'   => array(
                            array('image','gallery'),
                            array('of','gallery'),
                            array('downloadoriginalimage','gallery')
                        )
        );
        $images = gallery_load_images($gallery, $context, $iid);
        $canedit = has_capability('mod/gallery:editallimages', $context) || (has_capability('mod/gallery:editownimages', $context) && $USER->id == $images[$iid]->data()->user);
        $candelete = has_capability('mod/gallery:deleteallimages', $context) || (has_capability('mod/gallery:deleteownimages', $context) && $USER->id == $images[$iid]->data()->user);
        $PAGE->requires->js_init_call('M.mod_gallery.init', array(array('context'=>$context->id,'currentImage'=>$iid,'showOriginal'=>(bool)$gallery->showoriginalimage(),'canEdit'=>$canedit,'canDelete'=>$candelete)), false, $module);
        echo $renderer->render(new gallery_header($gallery->name(),$context));
        if($USER->editing)
            echo $renderer->render(new gallery_image_preview($images[$iid], $images, $cm, $context, $gallery->showoriginalimage(),$USER->editing,
                    has_capability('mod/gallery:editallimages', $context), has_capability('mod/gallery:editownimages', $context),
                    has_capability('mod/gallery:deleteallimages', $context), has_capability('mod/gallery:deleteownimages', $context)));
        else
            echo $renderer->render(new gallery_image_preview($images[$iid], $images, $cm, $context, $gallery->showoriginalimage()));
        break;
    case 'addimages':
        echo $renderer->render(new gallery_header($gallery->name(),$context));
        echo $renderer->render(new gallery_form('imageupload',$mform));
        break;
    case 'addimagedesc':
    case 'editimageg':
    case 'editimage':
    case 'batchedit':
        echo $renderer->render(new gallery_header($gallery->name(),$context));
        require_once($CFG->dirroot.'/mod/gallery/image_edit_form.php');
        echo $renderer->render(new gallery_form('imageedit', $mform));
        break;
    case 'nopermission':
        echo $renderer->render(new gallery_header($gallery->name(),$context));
        echo $renderer->render(new gallery_no_permission($cm));
        break;
}

echo $renderer->render_footer();