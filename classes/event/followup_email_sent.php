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
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The followup email sent event.
 *
 * @package    local_followup_email
 * @copyright  2020 Matt Donnelly
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_followup_email\event;

use coding_exception;
use core\event\base;
use lang_string;

defined('MOODLE_INTERNAL') || die();

/**
 * The followup_email_sent event class.
 *
 * @since     Moodle 3.7
 * @copyright 2020 Matt Donnelly
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

class followup_email_sent extends base {

    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * @return lang_string|string
     * @throws coding_exception
     */

    public static function get_name() {
        return get_string('followupemailsent', 'local_followup_email');
    }

    public function get_description() {
        return "A followup email of type {$this->other['relatedevent']} was sent to user {$this->relateduserid} in course {$this->courseid}.";
    }

}