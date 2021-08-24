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
 * Url module admin settings and defaults
 *
 * File         settings.php
 * Encoding     UTF-8
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $currencies = \mod_gwpayments\local\helper::get_possible_currencies();
    if (empty($currencies)) {
        $notify = new \core\output\notification(
            get_string('nocurrencysupported', 'core_payment'),
            \core\output\notification::NOTIFY_WARNING
        );
        $settings->add(new admin_setting_heading('mod_gwpayments_nocurrency', '', $OUTPUT->render($notify)));
    }

    // Logo.
    $image = '<a href="http://www.sebsoft.nl" target="_new"><img src="' .
            $OUTPUT->image_url('logo', 'mod_gwpayments') . '" /></a>&nbsp;&nbsp;&nbsp;';
    $donate = '<a href="https://customerpanel.sebsoft.nl/sebsoft/donate/intro.php" target="_new">' .
            '<img src="' . $OUTPUT->image_url('donate', 'block_coupon') . '" /></a>';
    $header = '<div class="mod_gwpayments-logopromo">' . $image . $donate . '</div>';
    $settings->add(new admin_setting_heading('mod_gwpayments_logopromo',
            get_string('promo', 'mod_gwpayments'),
            get_string('promodesc', 'mod_gwpayments', $header)));

    require_once("$CFG->libdir/resourcelib.php");
    // Modedit defaults.
    $settings->add(new admin_setting_heading('urlmodeditdefaults',
            get_string('modeditdefaults', 'admin'),
            get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configtext('gwpayments/cost',
            get_string('cost', 'mod_gwpayments'),
            '', 10.00, PARAM_FLOAT, 4));

    $settings->add(new admin_setting_configtext('gwpayments/vat',
            get_string('vat', 'mod_gwpayments'),
            get_string('vat_help', 'mod_gwpayments'), 21, PARAM_INT, 4));

    if (!empty($currencies)) {
        $settings->add(new admin_setting_configselect('gwpayments/currency',
                get_string('currency', 'mod_gwpayments'), '', 'EUR', $currencies));
    }

    $settings->add(new admin_setting_configcheckbox('gwpayments/studentdisplayonpayments',
        get_string('studentdisplayonpayments', 'mod_gwpayments'),
        get_string('studentdisplayonpayments_help', 'mod_gwpayments'), 0));

}
