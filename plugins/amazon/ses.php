<?php
/**
 * Amazon Simple Email Service (SES) client
 * 
 * Class to verify email address (before production access) and send emails
 * 
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2011 RIVER (www.river.se)
 * @package       snow
 * @since         Snow v 0.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 * 
 */


class snow_amazon_ses
{
	// Access Key ID
	private $conf_keyId;
	
	// Secret Access Key
	private $conf_secretKey;
	
	// SES access url (US East-1 only for now)
	private $conf_url = "https://email.us-east-1.amazonaws.com";

	
	/**
	 * 
	 * Constructor
	 * 
	 * @param string $conf_keyId			Amazon Secret Access Key
	 * @param string $conf_secretKey		Amazon Access Key ID
	 * @param string $conf_url				Amazon API SES url
	 * 
	 */
	function __construct( $conf_keyId, $conf_secretKey, $conf_url = null )
	{
		$this->conf_secretKey = $conf_secretKey;
		$this->conf_keyId = $conf_keyId;
		
		if( !is_null($conf_url) )
			$this->conf_url = $conf_url;
			
	}
	
	/**
	 * 
	 * 
	 * Submit an email address for verification
	 * 
	 * @param string $email
	 */
	public function verifyEmailAddress( $email )
	{
		$parameters = array( "EmailAddress" => $email );
		return $this->makeRequest( "VerifyEmailAddress", $parameters );
	}
	
	
	
	
	/**
	 * Send email
	 * 
	 * 
	 * @param string $src_email
	 * @param string/array $dst_email
	 * @param string $msg_subject
	 * @param string $msg_body_html
	 * @param string $msg_body_text
	 * @param string/array $cc_email
	 * @param string/array $bcc_email
	 * @param string $charset
	 */
	public function sendEmail( $src_email, $dst_email, $msg_subject, $msg_body_html = "", $msg_body_text = "", $cc_email = null, $bcc_email = null, $charset = "UTF-8" )
	{
		
		// Default parameter
		$parameters = array(	"Source" => $src_email,
								"Message.Subject.Data" => $msg_subject,
								"Message.Subject.Charset" => $charset,
								"Message.Body.Text.Data" => $msg_body_text,
								"Message.Body.Text.Charset" => $charset,
								"Message.Body.Html.Data" => $msg_body_html,
								"Message.Body.Html.Charset" => $charset
							);
		
		// To field
		$i = 1;
		$dst_email = is_array( $dst_email ) ? $dst_email : array( $dst_email );
		foreach( $dst_email as $one )
		{
			$parameters[ ("Destination.ToAddresses.member." . $i++) ] = $one;
		}
		
		// CC field
		if( !is_null( $cc_email) )
		{
			$i = 1;
			$cc_email = is_array( $cc_email ) ? $cc_email : array( $cc_email );
			foreach( $cc_email as $one )
			{
				$parameters[ ("Destination.CcAddresses.member." . $i++) ] = $one;
			}
		}
		
		// BCC field
		if( !is_null( $bcc_email) )
		{
			$i = 1;
			$bcc_email = is_array( $bcc_email ) ? $bcc_email : array( $bcc_email );
			foreach( $bcc_email as $one )
			{
				$parameters[ ("Destination.BccAddresses.member." . $i++) ] = $one;
			}
		}
							
							
		
		return $this->makeRequest( "SendEmail", $parameters );
		
	}


	private function makeRequest( $action, $parameters = null )
	{
			
		$post_body = array( "Action=" . $action );
		
		if( is_array($parameters) && count($parameters) > 0 )
		{
			foreach( $parameters as $key=>$val )
			{
				$post_body[] = $key . "=" . urlencode( $val );
			}
		}
		$httpDate = gmdate("D, d M Y H:i:s O");
		
		$signature = $this->hex2b64( hash_hmac  ( "sha1"  , $httpDate  , $this->conf_secretKey ) );
		
		$retry = 3;
		do{
			$req = new snow_core_curl();
			
			$args = array();
			$args['headers']["content-type"] = "application/x-www-form-urlencoded";
			$args['headers']["Date"] = $httpDate;
			$args['headers']["X-Amzn-Authorization"] = "AWS3-HTTPS AWSAccessKeyId={$this->conf_keyId}, Algorithm=HmacSHA1, Signature=" . $signature; 
			
			$params_body = implode("&", $post_body);
			
			$response = $req->post( $this->conf_url, $params_body, $args );
			
			$code = $response['response'];
			if( $code > 200 && $retry > 0 )
				$retry--;
			else if( $code > 200 )
			{
				echo $response['body'];
				exit();
			}
			else
				$retry = 0;
			
				
		}while($retry > 0);
		
		return  $response;
	}
	
	
	private function hex2b64($str) {
	    $raw = '';
	    for ($i=0; $i < strlen($str); $i+=2) {
	        $raw .= chr(hexdec(substr($str, $i, 2)));
	    }
	    return base64_encode($raw);
	}
	
	
	
}