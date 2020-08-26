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
class Analog_Log {

	/**
	 * Save the message to log file.
	 *
	 * @access public
	 * @param mixed  $message_to_log - message data to save to log file.
	 * @param string $log_file - Log file name.
	 * @return void
	 */
	public function bhome_save_log( $message_to_log, $log_file = 'log.txt' ) {
		$flattened_message = print_r( $message_to_log, true );
		$log_location      = __DIR__ . '/' . $log_file;

		Analog::handler( Analog\Handler\File::init( $log_location ) );
		Analog::log( $flattened_message );
	}
}
