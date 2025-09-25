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

import * as Fragment from 'core/fragment';
import * as Templates from 'core/templates';

/**
 * Initialiser
 *
 * @param {String} selector
 * @returns {void}
 */
export const init = (selector) => {
    const el = document.querySelector(selector);
    let params = {id: el.dataset.id, cmid: el.dataset.cmid};

    Fragment.loadFragment('mod_gwpayments', 'paymentregion', el.dataset.contextid, params)
            //.then((html, js) => {
            .then(function(html, js) {
                Templates.replaceNodeContents(selector, html, js);
            });
};
