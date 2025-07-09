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
 * Strings for component 'gwpayments', language 'en', branch 'MOODLE_20_STABLE'
 *
 * File         gwpayments.php
 * Encoding     UTF-8
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 RvD
 * @author      RvD <helpdesk@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['amount'] = 'Amount';
$string['completiondetail:submit'] = 'Make a payment';
$string['completiondisabled:label'] = 'Completion is disabled.';
$string['completiondisabled:warning'] = 'Completion is disabled. This course module relies on course completion as a way of making other resources available.';
$string['completionsubmit'] = 'Users must pay for this course module for it to be considered complete, creating the possibility to unlock other resources.';
$string['contentheader'] = 'Gateway payments settings';
$string['cost'] = 'Unlocking cost';
$string['cost_help'] = 'The unlocking cost indicates the payment one must perform before this course module is considered to be paid for.<br/>
In turn, this marks the module as completed which can in turn be used as a basis for basic completion rules, hence unlocking resources in the course.';
$string['costduration'] = 'Unlock duration';
$string['costduration_help'] = 'When setting an unlocking duration, this restricts the completion state to the given duration.<br/>
Effectively, this will create an expiration date for the unlocked resources.<br/> Hence, this option can be used to only unlock resources for a specified amount of time.<br/>
A value of 0 indicates no expiry.';
$string['currency'] = 'Currency';
$string['disablepaymentonmisconfig'] = 'Disable payment button when no (valid) payment can be made.';
$string['disablepaymentonmisconfig_help'] = 'When enabled, the payment button will be disabled when a payment cannot be made, e.g. on currency mismappings or no gateways are available';
$string['err:no-payment-account-set'] = 'No payment account is set for this activity.';
$string['err:payment-account-not-exists'] = 'The payment account for this activity no longer exists.';
$string['err:payment-account-unavailable'] = 'The payment account for this activity is unavailable.';
$string['err:payment-no-available-gateways'] = 'No gateway available for this activity. This is usually the case when the expected currency is not available for any gateway or one or more gateway(s) is/are disabled.';
$string['err:payment:misconfiguration'] = 'Due to a misconfiguration no payment can be made at this time.<br/>
If this message persists, please notify the system administrator.';
$string['err:table:set_sql'] = 'Can\'t set sql: table sets it\'s own.';
$string['event:order:delivered'] = 'Order delivered';
$string['gwpayments:addinstance'] = 'Add a new Gateway Payments resource';
$string['gwpayments:submitpayment'] = 'Submit a payment';
$string['gwpayments:view'] = 'View module';
$string['gwpayments:viewpayments'] = 'View payments overview';
$string['modulename'] = 'Gateway Payments';
$string['modulename_help'] = 'The Gateway Payments course module enables a teacher/course creator to provide a resource that needs to be paid for, which can in turn be used to unlock access to other rewsources in the course based on activity completion.
The Gateway Payments course module marks activity completion on payment, hence making it possible to use this course module as a locking mechanism for other course sections/modules.';
$string['modulename_link'] = 'mod/gwpayments/view';
$string['modulenameplural'] = 'Payments';
$string['no-payment-yet'] = 'No payment has yet been done by you.';
$string['notenrolledchoose'] = 'You have to be enrolled in this course to perform payment actions.';
$string['page-mod-gwpayments-x'] = 'Any Gateway Payments course module page';
$string['paymentaccount'] = 'Payment account';
$string['paymentaccount_help'] = 'Unlocking fees will be paid to this account.';
$string['paymentrequired'] = 'Your payment is required to unlock the rest of the resources in this course.';
$string['pluginadministration'] = 'Gateway Payments module administration';
$string['pluginname'] = 'Gateway Payments';
$string['privacy:metadata:database:gwpayments'] = 'Information about Gateway Payments course module payments.';
$string['privacy:metadata:database:gwpayments:amount'] = 'The amount for the payment.';
$string['privacy:metadata:database:gwpayments:currency'] = 'The currency of the payment.';
$string['privacy:metadata:database:gwpayments:timecreated'] = 'The time when the payment was made.';
$string['privacy:metadata:database:gwpayments:timeexpire'] = 'The time when the payment will be deemed expired.';
$string['privacy:metadata:database:gwpayments:timemodified'] = 'The time when the payment record was last updated.';
$string['privacy:metadata:database:gwpayments:userid'] = 'The user who made the payment.';
$string['promo'] = 'Gateway Payments course module for Moodle';
$string['promodesc'] = 'This plugin is written by Sebsoft Managed Hosting & Software Development
(<a href=\'https://www.sebsoft.nl/\' target=\'_new\'>https://sebsoft.nl</a>).<br /><br />
{$a}<br /><br />';
$string['sendpaymentbutton'] = 'Select payment type';
$string['status'] = 'Status';
$string['status:active'] = 'Active';
$string['status:expired'] = 'Expired';
$string['studentdisplayonpayments'] = 'Allow view page for students?';
$string['studentdisplayonpayments_help'] = 'Allows the student to access the module\'s view page when they\'ve paid.<br/>
The view page only displays the information related to the current user\'s payment(s).';
$string['task:defaulttasks'] = 'Default tasks.';
$string['timecreated'] = 'Time paid';
$string['timeexpire'] = 'Expires';
$string['vat'] = 'VAT';
$string['vat_help'] = 'VAT percentage of course cost (note: course cost is including VAT).';
