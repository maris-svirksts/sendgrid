<?php
/**
 * Plugin Name:     BHome SendGrid Integration
 * Description:     Add an interface for calling SendGrid
 * Text Domain:     bhome-sendgrid-integration
 * Version:         1.0.0
 *
 * @package         bhome_sengrid_emails
 */

/*
BHome SendGrid Integration is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

BHome SendGrid Integration is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with BHome SendGrid Integration. If not, see {URI to Plugin License}.
*/

/**
 * Add required files and call SendGrid instance.
 *
 * @access public
 * @return void
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/class-sendgrid-emails.php';
require_once __DIR__ . '/class-analog-log.php';
require_once __DIR__ . '/class-sendgrid-listener.php';

global $send_grid;

$send_grid = new \SendGrid( '<your ID here>' );

/*
 * Listener for Sendgrid notifications.
 */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'bhome-sendgrid-emails/v1',
			'/endpoint',
			array(
				'methods'  => array( 'POST', 'GET' ),
				'callback' => 'bhome_sendgrid_listener',
			)
		);
	}
);

/**
 * Transform SendGrid notifications into usable information.
 *
 * @access public
 * @param json $request - data from SendGrid.
 * @return void
 */
function bhome_sendgrid_listener( $request ) {
	$data = $request->get_json_params();

	$listener_event = new Sendgrid_Listener();

	foreach ( $data as $individual_event ) {
		$prepared_event = $listener_event->bhome_prepare_event_data( $individual_event );

		if ( ! empty( $prepared_event['ticket_id'] ) ) {
			$notification_process = $listener_event->bhome_save_notification( $prepared_event );

			if ( in_array( $prepared_event['event'], array( 'bounce' ), true ) ) {
				$email_notification = $listener_event->bhome_notify_moderator( $prepared_event );
			}
		}
	}

	// Might delete later: keeping an unedited version for comparison for now.
	$log        = new Analog_Log();
	$log_result = $log->bhome_save_log( $data, 'listener_data.txt' );
}

/**
 * Send test email.
 *
 * @access public
 * @return string
 */
function bhome_send_test_email_funct() {
	$mail_data = new \SendGrid\Mail\Mail();
	$result    = '';

	$work   = new SendGrid_Emails();
	$result = $work->bhome_send_test_email( $mail_data );

	return $result;
}

/**
 * Send the actual emails and log results.
 *
 * @access public
 * @param array $mail_contents - arguments.
 * @return array
 */
function bhome_send_production_email_funct( $mail_contents ) {
	$mail_data   = new \SendGrid\Mail\Mail();
	$result      = array();
	$return_data = array();

	$work   = new SendGrid_Emails();
	$result = $work->bhome_send_production_email( $mail_data, $mail_contents );

	$log        = new Analog_Log();
	$log_result = $log->bhome_save_log( $result );

	$return_data['status_code'] = $result['status_code'];

	$pos = strpos( $result['response_headers'][5], 'X-Message-Id' );
	if ( false !== $pos ) {
		$return_data['sendgrid_id'] = str_replace( 'X-Message-Id: ', '', $result['response_headers'][5] );
	} else {
		$return_data['sendgrid_id'] = '';
	}

	return $return_data;
}
