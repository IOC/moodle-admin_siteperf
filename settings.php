<?php
//  Site performance plugin for Moodle
//  Copyright © 2012  Institut Obert de Catalunya
//
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation, either version 3 of the License, or
//  (at your option) any later version.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    tool
 * @subpackage siteperf
 * @copyright  Marc Català <mcatala@ioc.cat>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('reports', new admin_externalpage('toolsiteperf', get_string('pluginname', 'tool_siteperf'), "$CFG->wwwroot/$CFG->admin/tool/siteperf/index.php", 'moodle/site:viewreports'));
