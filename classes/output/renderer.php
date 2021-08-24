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
 * Renderer class.
 *
 * File         renderer.php
 * Encoding     UTF-8
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gwpayments\output;

defined('MOODLE_INTERNAL') or die('NO_ACCESS');

use context;

/**
 * mod_gwpayments\output\renderer
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    /**
     * Render paymentdetails for given context/user.
     *
     * @param context $context
     * @param int $userid
     * @return string
     */
    public function paymentdetails(context $context, $userid = null) {
        $widget = new component\paymentdetails($context, $userid);
        return $this->render_paymentdetails($widget);
    }

    /**
     * Render paymentdetails for given widget instance.
     *
     * @param component\paymentdetails $widget
     */
    public function render_paymentdetails(component\paymentdetails $widget) {
        $context = $widget->export_for_template($this);
        return $this->render_from_template('mod_gwpayments/paymentdetails', $context);
    }

}
