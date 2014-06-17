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


/**
 * Supported features
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function gallery_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_MOD_INTRO:               
            return true;
        case FEATURE_SHOW_DESCRIPTION:        
            return true;
        case FEATURE_BACKUP_MOODLE2:          
            return true;
        default: 
            return null;
    }
}

/**
 * Add galerry instance.
 *
 * @param stdClass $data
 * @param stdClass $mform
 * @return int new book instance id
 */
function gallery_add_instance(stdClass $data, mod_gallery_mod_form $form = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    return $DB->insert_record('gallery', $data);
}

/**
 * Update gallery instance.
 *
 * @param stdClass $data
 * @param stdClass $mform
 * @return bool true
 */
function gallery_update_instance(stdClass $data, mod_gallery_mod_form $form = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;
    if(!isset($data->showdescription))
        $data->showdescription = 0;

    $DB->update_record('gallery', $data);

    return true;
}

/**
 * Delete gallery instance by activity id
 *
 * @param int $id
 * @return bool success
 */
function gallery_delete_instance($id) {
	if(is_null($id))
		return true;
		
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/gallery/locallib.php');
    require_once($CFG->dirroot.'/mod/gallery/gallery.class.php');

    if (!$DB->record_exists('gallery', array('id'=>$id))) 
        return false;
    
    $gallery = new gallery($id);
   
    $cm = get_coursemodule_from_instance('gallery', $id);
    $context = context_module::instance($cm->id);
    $images = gallery_load_images($gallery, $context);
    foreach($images as $image) 
        gallery_process_delete_image ($image, $context, $gallery);
        
    $DB->delete_records('gallery', array('id'=>$gallery->id));
    
    return true;
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settingsnav The settings navigation object
 * @param navigation_node $booknode The node to add module settings to
 * @return void
 */
function gallery_extend_settings_navigation(settings_navigation $settings, navigation_node $navref) {
    global $USER, $PAGE, $OUTPUT;

    $params = $PAGE->url->params();
    
    if (!empty($params['id']) && (has_capability('mod/gallery:manage', $PAGE->cm->context) ||
                has_capability('mod/gallery:addimages', $PAGE->cm->context) ||
                has_capability('mod/gallery:editallimages', $PAGE->cm->context) ||
                has_capability('mod/gallery:editownimages', $PAGE->cm->context) ||
                has_capability('mod/gallery:deleteallimages', $PAGE->cm->context) ||
                has_capability('mod/gallery:deleteownimages', $PAGE->cm->context))) {
        if (!empty($USER->editing)) {
            $string = get_string("turneditingoff","gallery");
            $edit = '0';
        } else {
            $string = get_string("turneditingon","gallery"); 
            $edit = '1';
        }
        $url = new moodle_url('/mod/gallery/view.php', array('id'=>$params['id'], 'edit'=>$edit, 'sesskey'=>sesskey()));
        $navref->add($string, $url, navigation_node::TYPE_SETTING);
        $PAGE->set_button($OUTPUT->single_button($url, $string, 'get')); 
    }
}

function gallery_get_coursemodule_info($coursemodule) {
    global $CFG,$OUTPUT;
    
    require_once($CFG->dirroot.'/mod/gallery/gallery.class.php');

    $gallery = new gallery($coursemodule->instance);
    if ($gallery->isValid()) {
        $info = new cached_cm_info();
        $info->content = '';
        // no filtering here because this info is cached and filtered later
        if($gallery->showdescription()) {
            $info->content = format_module_intro('gallery', $gallery->data(), $coursemodule->id, false);
        }
        if($gallery->showthumbnails()) {
            require_once($CFG->dirroot.'/mod/gallery/locallib.php');
            $context = context_module::instance($coursemodule->id);
            $images = gallery_load_images($gallery, $context);
            
            $urlParams = array('id'=>$coursemodule->id,'gaction'=>'image');
            
            $o = $OUTPUT->box_start('mod-gallery-intro-thumbnails-container');
            $o .= '<div class="mod-gallery-image-previous-intro" onclick="return modGalleryMoveThumb('.$gallery->id().',\'left\')"></div>';
            $o .= $OUTPUT->box_start('mod-gallery-intro-thumb-cont-helper','mod-gallery-intro-thumb-cont-helper-'.$gallery->id());
            $o .= $OUTPUT->box_start('mod-gallery-intro-thumbnails-table');
            foreach($images as $img) {
                $urlParams['image']=$img->id();
                $o .= $OUTPUT->action_link(new moodle_url('/mod/gallery/view.php',$urlParams), '<img src="'.$img->thumbnail().'"/>');
            }
            $o .= $OUTPUT->box_end();
            $o .= $OUTPUT->box_end();
            $o .= '<div class="mod-gallery-image-next-intro" onclick="return modGalleryMoveThumb('.$gallery->id().',\'right\')"></div>';
            $o .= $OUTPUT->box_end();
            
            $info->content .= $o;
        }
        
        $info->name  = $gallery->name();
        return $info;
    } else {
        return null;
    }
}

function gallery_cm_info_view(cm_info $cm) {
    global $PAGE;
    $PAGE->requires->js('/mod/gallery/js/intro.js');
    $module = array(
        		'name'      => 'mod_gallery',
        		'fullpath'  => '/mod/gallery/js/intro.js',
        		'requires'  => array('base', 'dom', 'anim',)
            );
    $PAGE->requires->js_init_call('M.mod_gallery.init', array(), true, $module);
        
    
}

function gallery_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false; 
    }

    require_once($CFG->dirroot.'/mod/gallery/locallib.php');
    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== GALLERY_IMAGES_FILEAREA 
            && $filearea !== GALLERY_IMAGE_THUMBS_FILEAREA
            && $filearea !== GALLERY_IMAGE_DRAFTS_FILEAREA
            && $filearea !== GALLERY_IMAGE_PREVIEWS_FILEAREA
            && $filearea !== GALLERY_IMAGE_ATTACHMENTS_FILEAREA) {
        return false;
    }

    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true, $cm);
 
    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    if (!has_capability('mod/gallery:view', $context)) {
        return false;
    }
 
    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.
 
    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.
 
    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
    }
    
    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_gallery', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }
    
    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 0, 0);
}

/**
* Lists all browsable file areas
* @param object $course
* @param object $cm
* @param object $context
* @return array
*/
function gallery_get_file_areas($course, $cm, $context) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/gallery/locallib.php');
    $areas = array();
    $areas[GALLERY_IMAGES_FILEAREA] = get_string('images', 'gallery');
    $areas[GALLERY_IMAGE_THUMBS_FILEAREA] = get_string('thumbnails', 'gallery');
    $areas[GALLERY_IMAGE_DRAFTS_FILEAREA] = get_string('drafts', 'gallery');
    $areas[GALLERY_IMAGE_PREVIEWS_FILEAREA] = get_string('previews', 'gallery');
    return $areas;
}

/**
* File browsing support for lightboxgallery module content area.
* @param object $browser
* @param object $areas
* @param object $course
* @param object $cm
* @param object $context
* @param string $filearea
* @param int $itemid
* @param string $filepath
* @param string $filename
* @return object file_info instance or null if not found
*/
function gallery_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/gallery/locallib.php');
    
    if ($filearea === GALLERY_IMAGES_FILEAREA) {
        $fs = get_file_storage();

        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;
        if (!$storedfile = $fs->get_file($context->id, 'mod_gallery', GALLERY_IMAGES_FILEAREA, 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_gallery', GALLERY_IMAGES_FILEAREA, 0);
            } else {
                // Not found.
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/gallery/locallib.php");
        $urlbase = $CFG->wwwroot.'/pluginfile.php';

        return new gallery_content_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea],
                                                        true, true, false, false);
    }

    // Note: folder_intro handled in file_browser automatically.

    return null;
}

defined('MOODLE_INTERNAL') || die();

/**
 *
 * Callback method for data validation---- required method for AJAXmoodle based comment API
 *
 * @param stdClass $options
 * @return bool
 */
function gallery_comment_validate(stdClass $options) {
    global $DB;

    if ($options->commentarea != 'gallery_image_comments') {
        throw new comment_exception('invalidcommentarea');
    }
    if (!$image = $DB->get_record('gallery_images', array('id'=>$options->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    $context = $options->context;
    
    if (!has_capability('mod/gallery:view', $context)) {
        throw new comment_exception('nopermissiontocomment');
    }

    return true;
}

/**
 * Permission control method for submission plugin ---- required method for AJAXmoodle based comment API
 *
 * @param stdClass $options
 * @return array
 */
function gallery_comment_permissions(stdClass $options) {
    global $DB;

    if ($options->commentarea != 'gallery_image_comments') {
        throw new comment_exception('invalidcommentarea');
    }
    if (!$image = $DB->get_record('gallery_images', array('id'=>$options->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }
    $context = $options->context;

    if (!has_capability('mod/gallery:view', $context)) {
        return array('post' => false, 'view' => false);
    }

    return array('post' => true, 'view' => true);
}