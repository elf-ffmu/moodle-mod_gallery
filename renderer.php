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

class mod_gallery_renderer extends plugin_renderer_base {
    
    public function render_gallery_header(gallery_header $header) {
        $o = '';

        if ($header->subpage) {
            $this->page->navbar->add($header->subpage);
        }

        $this->page->set_title(get_string('pluginname', 'gallery'));
        $this->page->set_heading($header->heading);

        $o .= $this->output->header();
        
        $heading = format_string($header->heading, false, array('context' => $header->context));
        $o .= $this->output->heading($heading);

        return $o;
    }
    
    public function render_gallery_view_gallery(gallery_view_gallery $widget) {
        $o = '';
        
        if(count($widget->images)) {
            $urlparams = array('id' => $widget->coursemodule->id, 'gaction' => 'image','image'=>reset($widget->images)->id());
            $o .= $this->output->action_link(new moodle_url('/mod/gallery/view.php', $urlparams), get_string('viewpreview','gallery'),null,array('class'=>'mod-gallery-extra-nav'));
        }
        
        $o .= $this->output->box_start('generalbox boxaligncenter', 'intro');
        $o .= $this->output->box(format_text($widget->gallery->intro(), $widget->gallery->introformat()));
        $o .= $this->output->box_end();
        
        
        if($widget->edit) {
            $o .= $this->output->box_start('generalbox', 'mod-gallery-navigation-buttons');
            $urlparams = array('id' => $widget->coursemodule->id);

            if($widget->canadd) {
               $urlparams['gaction']= 'addimages';
               $o .= $this->output->single_button(new moodle_url('/mod/gallery/view.php', $urlparams), get_string('addimages','gallery'));
            }
            $o .= $this->output->box_end();
            $o .= $this->output->box('','mod-gallery-clear');
            $options = array();
            if($widget->canedit) {
                $options['batchedit'] = get_string('edit','gallery');
                $options['batchrotateleft'] = get_string('rotateleft','gallery');
                $options['batchrotateright'] = get_string('rotateright','gallery');
            }
            if($widget->candelete)
                $options['batchdelete'] = get_string ('delete','gallery');
            if($widget->candownload)
                $options['batchdownload'] = get_string ('download','gallery');
            if(count($options) && count($widget->images))
                $o .= $this->output->box($this->output->action_link('#',get_string('selectdeselectall','gallery'),null,array('id'=>'mod-gallery-select-all')),'mod-gallery-select-deselect-container');
        }
        
        
        if($widget->edit) {
            $fUrl = new moodle_url('/mod/gallery/view.php',array('id' => $widget->coursemodule->id));
            $o .= '<form action="'.$fUrl->out().'" method="post" id="mod-gallery-edit-thumb-form">';
            
        }
        
        $o .= $this->output->box_start('generalbox','mod-gallery-thumb-container');
        if($widget->edit)
            $o .= '<div id="mod-gallery-drop-indicator" style="display:none;"></div>';
        foreach($widget->images as $image) {
            $i = '<img src="'.$image->thumbnail().'" style="margin-top:'.floor((150-$image->t_height())/2).'px;"/>';         
            $urlparams = array('id' => $widget->coursemodule->id, 'gaction' => 'image', 'image' => $image->id());
            
            if($widget->edit) {
                $o .= '<div class="mod-gallery-thumb-edit" data-image-id="'.$image->id().'">';
                $o .= $a = $this->output->action_link(new moodle_url('/mod/gallery/view.php', $urlparams), $i, null, array('class'=>'mod-gallery-image-thumb-a-edit'));
                $o .= $this->output->box('','mod-gallery-clear');
                $o .= $this->output->box_start('mod-gallery-thumb-actions');
                if($widget->canedit || $widget->candelete)
                    $o .= '<input type="checkbox" value="1" name="mod-gallery-batch-'.$image->id().'" class="mod-gallery-batch-checkbox"/>';
                if($widget->canedit || ($widget->caneditown && $image->data()->user == $widget->currentuser)) {
                    $urlparams['gaction'] = 'editimageg';
                    $urlparams['image'] = $image->id();
                    $o .= $this->output->action_link(new moodle_url('/mod/gallery/view.php', $urlparams), $this->output->pix_icon('edit', get_string('editimage','gallery'),'mod_gallery'));
                    $urlparams['gaction'] = 'rotateleftg';
                    $o .= $this->output->action_link(new moodle_url('/mod/gallery/view.php', $urlparams), $this->output->pix_icon('rotateleft', get_string('rotateleft','gallery'),'mod_gallery'));
                    $urlparams['gaction'] = 'rotaterightg';
                    $o .= $this->output->action_link(new moodle_url('/mod/gallery/view.php', $urlparams), $this->output->pix_icon('rotateright', get_string('rotateright','gallery'),'mod_gallery'));
                }
                if($widget->canedit)
                    $o .= $this->output->pix_icon('dragdrop', get_string('moveimage','gallery'),'mod_gallery',array('class'=>'mod-gallery-drag-thumb'));
                if($widget->candelete || ($widget->candeleteown && $image->data()->user == $widget->currentuser)) {
                    $urlparams['gaction'] = 'imagedelete';
                    $o .= $this->output->action_link(new moodle_url('/mod/gallery/view.php', $urlparams), $this->output->pix_icon('delete', get_string('deleteimage','gallery'),'mod_gallery'), null, array('onclick'=>"return confirm('".get_string('confirmdelete','gallery')."')"));
                }
                $o .= $this->output->box_end();
                $o .= '</div>';
            } else
                $o .= $this->output->action_link(new moodle_url('/mod/gallery/view.php', $urlparams), $i, null, array('class'=>'mod-gallery-image-thumb-a'));
        }
        $o .= $this->output->box('','mod-gallery-clear');
        $o .= $this->output->box_end();
        
        if($widget->edit) {
            if(count($widget->images)) {
                if(count($options)) {
                    $o .= $this->output->box_start();
                    $o .= get_string('selectedimageslabel','gallery');
                    $o .= '<select name="gaction" id="mod-gallery-batch-action-select">';
                    foreach($options as $key => $value)
                        $o .= '<option value="'.$key.'">'.$value.'</option>';
                    $o .= '</select>';
                    $o .= '<input type="submit" name="batchsubmit" value="'.get_string('batchrun','gallery').'" />';
                    $o .= $this->output->box_end();                    
                }
            }
            $o .= '</form">';
        }
        return $o;
    }
    
    public function render_gallery_image_preview(gallery_image_preview $img) {
        global $CFG;
        require_once($CFG->dirroot.'/comment/lib.php');
        $o = '';
        
        $urlparams = array('id' => $img->coursemodule->id);
        $o .= $this->output->action_link(new moodle_url('/mod/gallery/view.php', $urlparams), get_string('returntogallery','gallery'),null,array('class'=>'mod-gallery-extra-nav'));
        
        $o .= $this->output->heading($img->image->data()->name, '3','','mod-gallery-image-name');
        
        $o .= $this->output->box_start('generalbox', 'mod-gallery-navigation-buttons');
        if($img->edit) {
            if($img->canedit || ($img->caneditown && $img->image->data()->user == $img->currentuser)) {
                $urlparams = array('id' => $img->coursemodule->id, 'gaction' => 'editimage', 'image'=>$img->image->id());
                $o .= $this->output->action_link(new moodle_url('/mod/gallery/view.php', $urlparams), $this->output->pix_icon('edit', get_string('editimage','gallery'),'mod_gallery'),null,array('class'=>'mod-gallery-edit-actions'));
                $urlparams['gaction'] = 'rotatelefti';
                $o .= $this->output->action_link(new moodle_url('/mod/gallery/view.php', $urlparams), $this->output->pix_icon('rotateleft', get_string('rotateleft','gallery'),'mod_gallery'),null,array('class'=>'mod-gallery-edit-actions'));
                $urlparams['gaction'] = 'rotaterighti';
                $o .= $this->output->action_link(new moodle_url('/mod/gallery/view.php', $urlparams), $this->output->pix_icon('rotateright', get_string('rotateright','gallery'),'mod_gallery'),null,array('class'=>'mod-gallery-edit-actions'));
            }
            if($img->candelete || ($img->candeleteown && $img->image->data()->user == $img->currentuser)) {
                $urlparams['gaction'] = 'imagedelete';
                $o .= $this->output->action_link(new moodle_url('/mod/gallery/view.php', $urlparams), $this->output->pix_icon('delete', get_string('deleteimage','gallery'),'mod_gallery'), null, array('onclick'=>"return confirm('".get_string('confirmdelete','gallery')."')",'class'=>'mod-gallery-delete-actions'));
            }
        }
        $o .= $this->output->box_end();
        
        $o .= $this->output->box_start('mod-gallery-images-div');
        
        $o .= $this->output->box_start('mod-gallery-image-preview');

        if($img->image->data()->ordering != 1)
            $o .= $this->output->pix_icon('prev', get_string('previousimage','gallery'), 'mod_gallery',array('id'=>'mod-gallery-image-previous','onclick'=>'return showImagePrev()'));
        else
            $o .= $this->output->pix_icon('prev', get_string('previousimage','gallery'), 'mod_gallery',array('id'=>'mod-gallery-image-previous','onclick'=>'return showImagePrev()','style'=>'display:none;'));
        
        if($img->image->data()->ordering == count($img->thumbnails))
            $o .= $this->output->pix_icon('next', get_string('nextimage','gallery'), 'mod_gallery',array('id'=>'mod-gallery-image-next','onclick'=>'return showImageNext()','style'=>'display:none;'));
        else
            $o .= $this->output->pix_icon('next', get_string('nextimage','gallery'), 'mod_gallery',array('id'=>'mod-gallery-image-next','onclick'=>'return showImageNext()'));
        
        $o .= $this->output->box_start();
        $o .= $this->output->box_start('mod-gallery-image-preview-table');
        $o .= $this->output->box_start('mod-gallery-image-preview-table-cell');
        foreach($img->thumbnails as $thumb) {
            if($thumb->id() == $img->image->id()) 
                $o .= '<a href="'.$thumb->image().'" data-lightbox="gallery" title="'.$thumb->data()->name.'" id="mod-gallery-image-perview-a-'.$thumb->id().'" >';
            else 
                $o .= '<a href="'.$thumb->image().'" data-lightbox="gallery" title="'.$thumb->data()->name.'" style="display:none;" id="mod-gallery-image-perview-a-'.$thumb->id().'" >';
                
            $o .= '<img src="'.$thumb->preview().'" class="mod-gallery-image-preview-img"/>';  
            $o .= '</a>';
        }
        $o .= $this->output->box_end();
        $o .= $this->output->box_end();
        $o .= $this->output->box_end();
              
        $o .= $this->output->box_end();
        
        $o .= $this->output->box_start('mod-gallery-images-side');
                
        foreach($img->thumbnails as $thumb) {
            $o .= $this->output->action_link('#', '<img src="'.$thumb->thumbnail().'" />',null,array('onclick'=>'return showImage('.$thumb->id().')','data-id'=>$thumb->id(),'data-preview'=>$thumb->preview(),'id'=>'mod-gallery-thumb-'.$thumb->id()));
            $o .= $this->output->box('', 'mod-gallery-hidden-description', 'mod-gallery-image-desc-'.$thumb->id());
            $o .= $this->output->box('', 'mod-gallery-hidden-name', 'mod-gallery-image-name-'.$thumb->id());
            $o .= $this->output->box('', 'mod-gallery-hidden-source', 'mod-gallery-image-source-'.$thumb->id());
            $o .= $this->output->box('', 'mod-gallery-hidden-attachments', 'mod-gallery-image-attachments-'.$thumb->id());
        }
        $o .= $this->output->box_end();
        $o .= $this->output->box('','mod-gallery-clear');
        $o .= $this->output->box_end();
        
        if($img->showoriginalimage)
            $o .= $this->output->box($this->output->action_link($img->image->image(), get_string('downloadoriginalimage','gallery'),null,array('target'=>'_blank')),'','mod-gallery-image-preview-download');
        
        $o .= $this->output->box_start('','mod-gallery-image-source');
        if($img->image->data()->sourcetype == GALLERY_IMAGE_SOURCE_OWN) {
            $urlparams = array('id'=>$img->user->id);
            $o .= '<strong>'.get_string('author','gallery') . ':</strong> ';
            $o .= $this->output->action_link(new moodle_url('/user/profile.php',$urlparams), fullname($img->user));
        }
        if($img->image->data()->sourcetype == GALLERY_IMAGE_SOURCE_TEXT) {
            $o .= '<strong>'.get_string('source','gallery') . ':</strong> ';
            $o .= $img->image->data()->sourcetext;
        }
        $o .= $this->output->box_end();
        
        $o .= $this->output->box(format_text($img->image->data()->description,$img->image->data()->descriptionformat),null,'mod-gallery-image-desc');

        $o .= $this->output->box_start('','mod-gallery-image-attachments');
        foreach($img->image->attachments() as $att) {
            if($att->is_directory())
                continue;
            $ico = $this->output->pix_icon(file_file_icon($att),$att->get_filename(),'moodle',array('class'=>'icon'));
            $o .= $this->output->box_start();
            $attUrl = moodle_url::make_pluginfile_url($att->get_contextid(), $att->get_component(), 
                    $att->get_filearea(), $att->get_itemid(), 
                    $att->get_filepath(), $att->get_filename());
            $o .= $this->output->action_link($attUrl, $ico.$att->get_filename());
            $o .= $this->output->box_end();
        }
        $o .= $this->output->box_end();
        
        comment::init();
        $options = new stdClass();
        $options->area    = 'gallery_image_comments';
        $options->context = $img->context;
        $options->component = 'mod_gallery';
        $options->showcount = true;
        $options->displaycancel = true;
        foreach($img->thumbnails as $thumb) {
            $options->itemid  = $thumb->id();
            $comment = new comment($options);
            $comment->set_view_permission(true);
            if($thumb->id() == $img->image->id()) {
                $o .= '<div id="mod-gallery-image-comments-'.$thumb->id().'" class="box generalbox mod-gallery-image-comments">';
                $o .= $comment->output(true);
                $o .= "</div>";
            } else {
                $o .= '<div id="mod-gallery-image-comments-'.$thumb->id().'" class="box generalbox mod-gallery-image-comments" style="display:none;">';
                $o .= $comment->output(true);
                $o .= "</div>";
            }
        }
        

        return $o;
    }
    
    public function render_gallery_form(gallery_form $form) {
        $o = '';
        if ($form->jsinitfunction) {
            $this->page->requires->js_init_call($form->jsinitfunction, array());
        }
        $o .= $this->output->box_start('boxaligncenter ' . $form->classname);
        $o .= $this->moodleform($form->form);
        $o .= $this->output->box_end();
        return $o;
    }
    
    public function render_footer() {
        return $this->output->footer();
    }
    
    public function render_gallery_no_permission(gallery_no_permission $widget) {
        $o = '';
        $o .= get_string('nopermission','gallery');
        $o .= $this->output->single_button(new moodle_url('/mod/gallery/view.php', array('id' => $widget->cm->id)), get_string('returntogallery','gallery'));
        return $o;
    }
    
    protected function moodleform(moodleform $mform) {
        ob_start();
        $mform->display();
        $o = ob_get_contents();
        ob_end_clean();

        return $o;
    }
}