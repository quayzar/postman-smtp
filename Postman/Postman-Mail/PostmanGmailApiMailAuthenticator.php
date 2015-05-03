<?php
require_once 'PostmanMailAuthenticator.php';
if (! class_exists ( 'PostmanGmailApiMailAuthenticator' )) {
	class PostmanGmailApiMailAuthenticator extends PostmanOAuth2MailAuthenticator {
		private function getEncryptionType() {
			return PostmanGoogleMailApiTransport::ENCRYPTION_TYPE;
		}
		private function getPort() {
			return PostmanGoogleMailApiTransport::PORT;
		}
	}
}