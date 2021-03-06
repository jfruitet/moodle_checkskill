<?php

// This file is part of the Checkskill plugin for Moodle - http://moodle.org/
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
 * Code fragment to define the version of checkskill
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author  Davo Smith <moodle@davosmith.co.uk>
 * @author  Jean FRUITET <jean.fruitet@univ-nantes.fr>
 * @package mod/checkskill
 */


if (!isset($plugin)) {
    // Avoid warning message in M2.5 and below.
    $plugin = new stdClass();
}
// Used by M2.6 and above.
$plugin->version  = 2014101800;  // The current module version (Date: YYYYMMDDXX)
$plugin->cron     = 60;          // Period for cron to check this module (secs)
$plugin->maturity = MATURITY_STABLE;
$plugin->release  = '2.x (Build from Checklist by JF: : 2014101800)';
$plugin->requires = 2010112400;
$plugin->component = 'mod_checkskill';

if (!isset($module)) {
    // Avoid warning message when $module support is dropped.
    $module = new stdClass();
}
// Used by M2.5 and below.
$module->version = $plugin->version;
$module->cron = $plugin->cron;
$module->maturity = $plugin->maturity;
$module->release = $plugin->release;
$module->requires = $plugin->requires;
$module->component = $plugin->component;
