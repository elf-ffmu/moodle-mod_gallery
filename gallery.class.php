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

class gallery {
    
    protected $data;
    
    public function __construct($id) {
        global $DB;
        $this->data = $DB->get_record('gallery',array('id'=>$id),'*', MUST_EXIST);
    }
    
    public function id() {
        return $this->data->id;
    }
    
    public function course() {
        return $this->data->course;
    }
    
    public function name() {
        return $this->data->name;
    }
    
    public function intro() {
        return $this->data->intro;
    }
    
    public function introformat() {
        return $this->data->introformat;
    }
    
    public function showdescription() {
        return $this->data->showdescription;
    }
    
    public function showthumbnails() {
        return $this->data->showthumbnails;
    }
    
    public function showoriginalimage() {
        return $this->data->showoriginalimage;
    }
    
    public function imageattachments() {
        return $this->data->imageattachments;
    }
    
    public function isValid() {
        return !is_null($this->data);
    }
    
    public function data() {
        return $this->data;
    }
}
