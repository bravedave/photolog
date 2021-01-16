<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

abstract class sys extends dvc\sys {
	static function notifySupport( $subject, $message) {
		// $mailer = self::mailer();
		// $mailer->ContentType = 'text/plain';
		// $mailer->IsHTML(false);
		// $mailer->Subject  = $subject;
		// $mailer->Body = $message;
		// $mailer->AddAddress( config::$SUPPORT_EMAIL, config::$SUPPORT_NAME);

		// if ( !$mailer->Send()) {
		// 	self::logger( 'sys::notifySupport : ' . $mailer->ErrorInfo);
		// 	self::logger( $subject . ':' . $message);

		// }

	}

}
