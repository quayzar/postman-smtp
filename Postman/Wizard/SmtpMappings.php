<?php

namespace Postman {

	class SmtpMapping {
		private $emailDomain = array (
				'gmail.com' => 'smtp.gmail.com',
				'hotmail.com' => 'smtp.live.com',
				'outlook.com' => 'smtp.live.com',
				'yahoo.co.uk' => 'smtp.mail.yahoo.co.uk',
				'yahoo.com.au' => 'smtp.mail.yahoo.com.au',
				'yahoo.com' => 'smtp.mail.yahoo.com' 
		);
		private $mx = array (
				'google.com' => 'smtp.gmail.com',
				'hotmail.com' => 'smtp.live.com'
		);
		public function getSmtpFromEmail($email) {
			$hostname = substr ( strrchr ( $email, "@" ), 1 );
			while ( list ( $domain, $smtp ) = each ( $this->emailDomain ) ) {
				if (strcasecmp ( $hostname, $domain ) == 0) {
					return $smtp;
				}
			}
			return false;
		}
		public function getSmtpFromMx($mx) {
			while ( list ( $domain, $smtp ) = each ( $this->mx ) ) {
				if ($this->endswith ( $mx, $domain )) {
					return $smtp;
				}
			}
			return false;
		}
		function endswith($string, $test) {
			$strlen = strlen ( $string );
			$testlen = strlen ( $test );
			if ($testlen > $strlen)
				return false;
			return substr_compare ( $string, $test, $strlen - $testlen, $testlen, true ) === 0;
		}
	}
}