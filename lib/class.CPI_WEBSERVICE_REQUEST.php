<?php

class CPI_WEBSERVICE_REQUEST {

	private $webservice_key;
	private $user_id;
	private $debug = false;
	private $webservice_url = "https://www.crmpro.com/service.cfm";

	function __construct( $webservice_key = "" , $user_id = 0 ){

		$this->webservice_key = $webservice_key;
		$this->user_id = $user_id;
	}

	function enableDebugging(){

		$this->debug = true;
	}

	function disableDebugging(){

		$this->debug = false;
	}

	function test(){

		$xml = new DOMDocument( "1.0", "UTF-8" );

		$object = $xml->createElement( "object" );

		$object->setAttribute( "class", "TIMEZONE" );
		$object->setAttribute( "action", "GET" );
		$object->setAttribute( "access_key", $this->webservice_key );
		$object->setAttribute( "user_id", $this->user_id );
		$object->setAttribute( "id", "America/Los_Angeles" );

		$xml->appendChild( $object );

		$data = $xml->saveXML();

		$response = $this->request( $data );

		return $this->check_error( $response ) === "";
	}

	function create_contact( $data ){

		$xml = new DOMDocument( "1.0", "UTF-8" );

		$object = $xml->createElement( "object" );

		$object->setAttribute( "class", "CONTACT" );
		$object->setAttribute( "action", "CREATE" );
		$object->setAttribute( "access_key", $this->webservice_key );
		$object->setAttribute( "user_id", $this->user_id );
		$object->setAttribute( "create_company", "YES" );
		$object->setAttribute( "validate_duplicate", "YES" );
		$object->setAttribute( "email_campaign_id", "00" );
		$object->setAttribute( "update_criteria", "email" );
		$object->setAttribute( "relaxed_match", "false" );
		$object->setAttribute( "use_blanks", "true" );
		$object->setAttribute( "duplicate_behaviour", "update" );
		$object->setAttribute( "onmultiple", "FIRST" );

		$properties = $xml->createElement( "properties" );

		if( isset( $data["firstname" ] ) ) :
			$property_firstname = $xml->createElement("property");
			$property_firstname->setAttribute( "name", "FIRST_NAME" );
			$property_firstname->setAttribute( "value", $data["firstname" ] );
			$properties->appendChild( $property_firstname );
		endif;

		if( isset( $data["lastname" ] ) ) :
			$property_lastname = $xml->createElement("property");
			$property_lastname->setAttribute( "name", "SURNAME" );
			$property_lastname->setAttribute( "value", $data["lastname" ] );
			$properties->appendChild( $property_lastname );
		endif;

		if( isset( $data["email" ] ) ) :
			$property_email = $xml->createElement("property");
			$property_email->setAttribute( "name", "EMAIL" );
			$property_email->setAttribute( "value", $data["email" ] );
			$properties->appendChild( $property_email );
		endif;

		$property_source = $xml->createElement("property");
		$property_source->setAttribute( "name", "SOURCE" );
		$property_source->setAttribute( "value", get_bloginfo("name") );
		$properties->appendChild( $property_source );
	
		$property_status = $xml->createElement("property");
		$property_status->setAttribute( "name", "STATUS" );
		$property_status->setAttribute( "value", "NEW" );
		$properties->appendChild( $property_status );

		$object->appendChild( $properties );

		$xml->appendChild( $object );

		$XMLdata = $xml->saveXML();

		$response = $this->request( $XMLdata );

		$error = $this->check_error( $response );

		$response_obj = array(
			"request" => $XMLdata,
			"response" => $response,
			"error_msg" => $error
		);

		if( is_numeric( $response ) ):

			$response_obj["status"] = "OK";

		elseif( $response == "TOO_MANY_CONTACTS_ALREADY_EXISTS" ) :

			$response_obj["status"] = "ERROR";
			$response_obj["error_msg"] = $data["email" ] ." already exists in database.";

		else :

			$response_obj["status"] = "ERROR";

		endif;

		return $response_obj;

		//return is_numeric( $response ) ? "OK" : $this->check_error( $response ) ;

	}

	private function check_error( $response ){

		if( substr( trim( $response ) , 0, 9 ) == "AUTH_FAIL" ) :
			return "Web Service credentials are invalid.";
		endif;

		if( substr( trim( $response ) , 0, 15 ) == "INVALID REQUEST" ) :
			return "Web Service error.";
		endif;

		return "";

	}

	private function request(  $data ){

		$url = sprintf( 
			$this->webservice_url."?debug=%s&packet=%s",
			( $this->debug ? "YES" : "NO" ),
			urlencode( $data )
		);

		$response = file_get_contents( $url );

		return $response;

	}
}