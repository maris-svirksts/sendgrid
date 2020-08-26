<?php
/**
 * Description:     Generate logging for emails
 * Text Domain:     bhome-sendgrid-integration
 * Version:         1.0.0
 *
 * @package         bhome_sengrid_emails
 */

/**
 * Log generator class, allows to save data to file.
 *
 * @access public
 * @return void
 */
class Sendgrid_Listener {

	/**
	 * Get ticket id from email identificator.
	 *
	 * @access public
	 * @param string $notification_id - email identificator.
	 * @return string
	 */
	private function bhome_get_ticket_id( $notification_id = '0' ) {
		global $wpdb;

		$bhome_ticket_id = $wpdb->get_var( $wpdb->prepare( 'SELECT ticket_id FROM ticket_reply WHERE sendgrid_id = %s;', $notification_id ) );

		return $bhome_ticket_id;
	}

	/**
	 * Get reply id from email identificator.
	 *
	 * @access public
	 * @param string $notification_id - email identificator.
	 * @return string
	 */
	private function bhome_get_reply_id( $notification_id = '0' ) {
		global $wpdb;

		$bhome_ticket_id = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ticket_reply WHERE sendgrid_id = %s;', $notification_id ) );

		return $bhome_ticket_id;
	}

	/**
	 * Get property id from inquiry identificator.
	 *
	 * @access public
	 * @param string $ticket_id - inquiry identificator.
	 * @return string
	 */
	private function bhome_get_property_id( $ticket_id = '0' ) {
		global $wpdb;

		$bhome_property_id = $wpdb->get_var( $wpdb->prepare( 'SELECT post_id FROM ticket_tickets WHERE ticket_id = %s;', $ticket_id ) );

		return $bhome_property_id;
	}

	/**
	 * Get open count from email identificator.
	 *
	 * @access public
	 * @param string $notification_id - email identificator.
	 * @return string
	 */
	private function bhome_get_open_count( $notification_id = '0' ) {
		global $wpdb;

		$bhome_property_id = $wpdb->get_var( $wpdb->prepare( 'SELECT message_open FROM ticket_reply WHERE sendgrid_id = %s;', $notification_id ) );

		return $bhome_property_id;
	}

	/**
	 * Get click count from email identificator.
	 *
	 * @access public
	 * @param string $notification_id - email identificator.
	 * @return string
	 */
	private function bhome_get_click_count( $notification_id = '0' ) {
		global $wpdb;

		$bhome_property_id = $wpdb->get_var( $wpdb->prepare( 'SELECT message_click FROM ticket_reply WHERE sendgrid_id = %s;', $notification_id ) );

		return $bhome_property_id;
	}

	/**
	 * Clean up notification data.
	 *
	 * @access public
	 * @param array $notification - notification data to clean up.
	 * @return array
	 */
	public function bhome_prepare_event_data( $notification ) {
		$prepared_data = array();

		if ( false !== strpos( $notification['sg_message_id'], '.' ) ) {
			$prepared_data['sg_message_id'] = substr( $notification['sg_message_id'], 0, strpos( $notification['sg_message_id'], '.' ) );
		} else {
			$prepared_data['sg_message_id'] = $notification['sg_message_id'] ?? '';
		}

		$prepared_data['email']  = $notification['email'] ?? '';
		$prepared_data['event']  = $notification['event'] ?? '';
		$prepared_data['reason'] = $notification['reason'] ?? '';

		if ( ! empty( $prepared_data['sg_message_id'] ) ) {
			$prepared_data['ticket_id']      = $this->bhome_get_ticket_id( $prepared_data['sg_message_id'] ) ?? '0';
			$prepared_data['reply_id']       = $this->bhome_get_reply_id( $prepared_data['sg_message_id'] ) ?? 0;
			$prepared_data['post_id']        = $this->bhome_get_property_id( $prepared_data['ticket_id'] ) ?? 0;
			$prepared_data['message_open']   = $this->bhome_get_open_count( $prepared_data['sg_message_id'] ) ?? 0;
			$prepared_data['message_click']  = $this->bhome_get_click_count( $prepared_data['sg_message_id'] ) ?? 0;
			$prepared_data['url']            = get_permalink( 31151 ) . '?request=' . $prepared_data['ticket_id'];
			$prepared_data['property_title'] = ms_get_post_title( $prepared_data['post_id'] ) ?? '';
		}

		return $prepared_data;
	}

	/**
	 * Save notification updates to database.
	 *
	 * @access public
	 * @param array $notification - notification data to save to database.
	 * @return void
	 */
	public function bhome_save_notification( $notification ) {
		global $wpdb;

		switch ( $notification['event'] ) {
			case 'processed':
				$message_status = '2';
				break;
			case 'delivered':
				$message_status = '7';
				break;
			case 'deferred':
				$message_status = '3';
				break;
			case 'bounce':
				$message_status = '4';
				break;
			case 'dropped':
				$message_status = '5';
				break;
			case 'spamreport':
				$message_status = '6';
				break;
			case 'open':
				$message_status = '7';
				++$notification['message_open'];
				break;
			case 'click':
				$message_status = '7';
				++$notification['message_click'];
				break;
			default:
				$message_status = '1';
		}

		$wpdb->update(
			'ticket_reply',
			array(
				'message_status' => $message_status,
				'message_open'   => $notification['message_open'],
				'message_click'  => $notification['message_click'],
			),
			array( 'id' => $notification['reply_id'] ),
			array(
				'%s',
				'%d',
				'%d',
			),
			array( '%d' )
		);
	}

	/**
	 * Notify moderator about an issue.
	 *
	 * @access public
	 * @param mixed $notification - notification data to save to database.
	 * @return void
	 */
	public function bhome_notify_moderator( $notification ) {
		$mail_contents  = array();
		$field_location = 'options';

		$mail_contents['template_id'] = 'd-879f7c5eb2514878b88eb37bd443b8be';

		$mail_contents['receivers'][0]['receiver_email'] = 'maris.svirksts@gmail.com';
		$mail_contents['receivers'][0]['receiver_name']  = 'BH Moderators';
		$mail_contents['receivers'][1]['receiver_email'] = 'hello@boutique-homes.com';
		$mail_contents['receivers'][1]['receiver_name']  = 'BH Moderators';

		$mail_contents['shortcodes']['url']            = $notification['url'];
		$mail_contents['shortcodes']['receiver_name']  = 'BH Moderators';
		$mail_contents['shortcodes']['property_title'] = $notification['property_title'];
		$mail_contents['shortcodes']['email_address']  = $notification['email'];
		$mail_contents['shortcodes']['event']          = $notification['event'];
		$mail_contents['shortcodes']['reason']         = $notification['reason'];

		$result = bhome_send_production_email_funct( $mail_contents );
	}
}
