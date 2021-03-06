<?php
if (! interface_exists ( 'PostmanZendMailTransportConfigurationFactory' )) {
	interface PostmanZendMailTransportConfigurationFactory {
		function createConfig(PostmanTransport $transport);
	}
}

if (! class_exists ( 'PostmanBasicAuthConfigurationFactory' )) {
	class PostmanBasicAuthConfigurationFactory implements PostmanZendMailTransportConfigurationFactory {
		private $logger;
		public function __construct() {
			$this->logger = new PostmanLogger ( get_class ( $this ) );
		}
		public function createConfig(PostmanTransport $transport) {
			
			// retrieve the hostname and port form the transport
			$hostname = $transport->getHostname ();
			$port = $transport->getHostPort ();
			$securityType = $transport->getSecurityType ();
			$authType = $transport->getAuthenticationType ();
			$username = $transport->getCredentialsId ();
			$password = $transport->getCredentialsSecret ();
			
			// create the Configuration structure for Zend_Mail
			$config = array (
					'port' => $port 
			);
			$this->logger->debug ( sprintf ( 'Using %s:%s ', $hostname, $port ) );
			if ($securityType != PostmanOptions::ENCRYPTION_TYPE_NONE) {
				$config ['ssl'] = $securityType;
				$this->logger->debug ( 'Using encryption ' . $securityType );
			} else {
				$this->logger->debug ( 'Using no encryption' );
			}
			if ($authType != PostmanOptions::AUTHENTICATION_TYPE_NONE) {
				$config ['auth'] = $authType;
				$config ['username'] = $username;
				$config ['password'] = $password;
				$this->logger->debug ( sprintf ( 'Using auth %s with username %s and password %s ', $authType, $username, PostmanUtils::obfuscatePassword ( $password ) ) );
			} else {
				$this->logger->debug ( 'Using no authentication' );
			}
			
			// return the Configuration structure
			return $config;
		}
	}
}

if (! class_exists ( 'PostmanOAuth2ConfigurationFactory' )) {
	class PostmanOAuth2ConfigurationFactory implements PostmanZendMailTransportConfigurationFactory {
		private $logger;
		public function __construct() {
			$this->logger = new PostmanLogger ( get_class ( $this ) );
		}
		public function createConfig(PostmanTransport $transport) {
			// retrieve the hostname and port form the transport
			$hostname = $transport->getHostname ();
			$port = $transport->getHostPort ();
			
			// the sender email is needed for the OAuth2 Bearer token
			$senderEmail = PostmanOptions::getInstance ()->getEnvelopeSender ();
			assert ( ! empty ( $senderEmail ) );
			
			// the vendor is required for Yahoo's OAuth2 implementation
			$vendor = $this->createVendorString ( $hostname );
			
			// create the OAuth2 SMTP Authentication string
			$initClientRequestEncoded = $this->createAuthenticationString ( $senderEmail, PostmanOAuthToken::getInstance ()->getAccessToken (), $vendor );
			
			// create the Configuration structure for Zend_Mail
			$config = $this->createConfiguration ( $hostname, $port, $transport->getSecurityType (), $transport->getAuthenticationType (), $initClientRequestEncoded );
			
			// return the Configuration structure
			return $config;
		}
		
		/**
		 *
		 * Create the Configuration structure for Zend_Mail
		 *
		 * @param unknown $hostname        	
		 * @param unknown $port        	
		 * @param unknown $securityType        	
		 * @param unknown $authenticationType        	
		 * @param unknown $initClientRequestEncoded        	
		 * @return multitype:unknown NULL
		 */
		private function createConfiguration($hostname, $port, $securityType, $authenticationType, $initClientRequestEncoded) {
			$config = array (
					'ssl' => $securityType,
					'port' => $port,
					'auth' => $authenticationType,
					'xoauth2_request' => $initClientRequestEncoded 
			);
			$this->logger->debug ( sprintf ( 'Using auth %s with encryption %s to %s:%s ', $config ['auth'], $config ['ssl'], $hostname, $config ['port'] ) );
			return $config;
		}
		
		/**
		 * Create the vendor string (for Yahoo servers only)
		 *
		 * @param unknown $hostname        	
		 * @return string
		 */
		private function createVendorString($hostname) {
			// the vendor is required for Yahoo's OAuth2 implementation
			$vendor = '';
			if (PostmanUtils::endsWith ( $hostname, 'yahoo.com' )) {
				// Yahoo Mail requires a Vendor - see http://imapclient.freshfoo.com/changeset/535%3A80ae438f4e4a/
				$pluginData = apply_filters ( 'postman_get_plugin_metadata', null );
				$vendor = sprintf ( "vendor=Postman SMTP %s\1", $pluginData ['version'] );
			}
			return $vendor;
		}
		
		/**
		 * Create the standard OAuth2 SMTP Authentication string
		 *
		 * @param unknown $senderEmail        	
		 * @param unknown $oauth2AccessToken        	
		 * @param unknown $vendor        	
		 * @return string
		 */
		private function createAuthenticationString($senderEmail, $oauth2AccessToken, $vendor) {
			$initClientRequestEncoded = base64_encode ( sprintf ( "user=%s\1auth=Bearer %s\1%s\1", $senderEmail, $oauth2AccessToken, $vendor ) );
			return $initClientRequestEncoded;
		}
	}
}
