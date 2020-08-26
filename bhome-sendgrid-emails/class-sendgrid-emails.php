<?php
/**
 * Description:     Generate data for SendGrid call
 * Text Domain:     bhome-sendgrid-integration
 * Version:         1.0.0
 *
 * @package         bhome_sengrid_emails
 */

/**
 * Email generator class, allows to send data for SendGrid call.
 *
 * @access public
 * @return void
 */
class SendGrid_Emails {

	/**
	 * Do a test email call.
	 *
	 * @access public
	 * @param object $mail_data SendGrid Mail class.
	 * @return object
	 */
	public function bhome_send_test_email( $mail_data ) {
		$email  = $this->bhome_test_data( $mail_data );
		$result = $this->bhome_send_email( $email );

		return $result;
	}

	/**
	 * Do an email call.
	 *
	 * @access public
	 * @param object $mail_data - SendGrid Mail class.
	 * @param array  $mail_contents - arguments.
	 * @return string
	 */
	public function bhome_send_production_email( $mail_data, $mail_contents ) {
		$valid_data = false;
		$result     = '';

		$valid_data = $this->bhome_validate_input( $mail_contents );
		if ( $valid_data ) {
			$validated_mail_contents = $this->bhome_clean_data( $mail_contents );
			$email                   = $this->bhome_production_data( $mail_data, $validated_mail_contents );
			$result                  = $this->bhome_send_email( $email );
		}

		return $result;
	}

	/**
	 * Fix possible data input problems.
	 *
	 * @access private
	 * @param array $mail_contents - data for SendGrid object.
	 * @return array
	 */
	private function bhome_clean_data( $mail_contents ) {

		// Fix issues with email. Whitespace, coma, multiple emails within one field.
		$counter         = 0;
		$email_pieces_p1 = array();
		$email_pieces_p2 = array();

		$email_pieces_p1 = explode( ',', $mail_contents['receivers'][0]['receiver_email'] );

		if ( ! empty( $mail_contents['receivers'][1]['receiver_email'] ) ) {
			$email_pieces_p2 = explode( ',', $mail_contents['receivers'][1]['receiver_email'] );
		}

		$bhome_merged_emails = array_merge( $email_pieces_p1, $email_pieces_p2 );
		$bhome_unique_emails = array_unique( $bhome_merged_emails );

		foreach ( $bhome_unique_emails as $unique_email ) {
			$mail_contents['receivers'][ $counter ]['receiver_email'] = trim( $unique_email );
			// Hackish: sets all receiver_names to the first one.
			$mail_contents['receivers'][ $counter ]['receiver_name'] = $mail_contents['receivers'][0]['receiver_name'];

			$counter++;
		}

		return $mail_contents;
	}

	/**
	 * Validate the input data.
	 *
	 * @access private
	 * @param array $mail_contents - data for SendGrid object.
	 * TODO: add validation.
	 * @return bool
	 */
	private function bhome_validate_input( $mail_contents ) {
		$result = true;

		return $result;
	}

	/**
	 * Fill the email fields required with test data.
	 *
	 * @access private
	 * @param object $mail_data - SendGrid object fill.
	 * @return object
	 */
	private function bhome_test_data( $mail_data ) {
		$mail_data->setFrom( 'behome@boutique-homes.com', 'Example User' );
		$mail_data->setSubject( 'Sending with SendGrid is Fun' );
		$mail_data->addTo( 'maris.svirksts@gmail.com', 'Example User' );
		$mail_data->addContent( 'text/plain', 'and easy to do anywhere, even with PHP' );
		$mail_data->addContent( 'text/html', '<strong>and easy to do anywhere, even with PHP</strong>' );

		return $mail_data;
	}

	/**
	 * Fill the email fields required with production data.
	 *
	 * @access private
	 * @param object $mail_data - SendGrid object fill.
	 * @param array  $mail_contents - variables to set within SendGrid object.
	 * TODO: make setFrom fields editable through Custom Fields PRO plugin.
	 * @return object
	 */
	private function bhome_production_data( $mail_data, $mail_contents ) {
		$mail_data->setFrom( 'behome@boutique-homes.com', 'BoutiqueHomes' );

		foreach ( $mail_contents['receivers'] as $receiver ) {
			$mail_data->addTo(
				$receiver['receiver_email'],
				$receiver['receiver_name'],
				$mail_contents['shortcodes']
			);
		}

		if ( $mail_contents['template_id'] ) {
			$mail_data->setTemplateId( $mail_contents['template_id'] );
		}

		if ( ! empty( $mail_contents['post_id'] ) && in_category( 3305, $mail_contents['post_id'] ) ) {
			$mail_data->addBcc( 'h5@verana.com', 'BH' );
		}

		$mail_data->setAsm( 14605, array( 14605, 14606, 14607 ) );

		return $mail_data;
	}

	/**
	 * Send the email to SendGrid.
	 *
	 * @access private
	 * @param array $email - filled out SendGrid object.
	 * TODO: Implement logging.
	 * @return string
	 */
	private function bhome_send_email( $email ) {
		global $send_grid;
		$response = new stdClass();
		$result   = array();

		try {
			$response              = $send_grid->send( $email );
			$result['status_code'] = $response->statusCode();

			if ( 200 <= $result['status_code'] && 300 > $result['status_code'] ) {
				$result['status_code'] = 1;
			}

			$result['response_headers'] = $response->headers();
		} catch ( Exception $e ) {
			$result['exception_message'] = $e->getMessage();
		}

		return $result;
	}
}
