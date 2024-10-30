<?php

class CPI_PLUGIN {

	function __construct(){

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_menu',  array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, "admin_init" ) );
		add_action( 'admin_print_styles', array( $this, "admin_print_styles") );
		add_filter( 'manage_users_columns', array( $this, 'manage_users_columns' ), 9999 );
		add_action( 'manage_users_custom_column',   array( $this, 'manage_users_custom_column' ), 11, 3);
		add_filter( 'manage_users_sortable_columns', array( $this, 'manage_users_sortable_columns' ) );
		add_action( 'wp_ajax_cpi_import', array( $this, "ajax_import" ) );
		add_action( 'wp_ajax_cpi_get_users' , array( $this, "ajax_get_users") );
		add_action( 'profile_update', array( $this, 'profile_update' ) );
		add_action( 'cpi_cron', array( $this, 'cron' ) );
		register_activation_hook( CPI_PLUGIN, array( $this, 'activate' ) ); 
		register_deactivation_hook( CPI_PLUGIN, array( $this, 'deactivate' ) ); 
	}

	function activate(){

		wp_schedule_event( time() , "hourly", "cpi_cron");

	}

	function deactivate(){

		wp_clear_scheduled_hook("cpi_cron");
	}

	function cron(){

		foreach( $this->get_all_users() as $user ):

			$this->profile_update( $user->ID );

		endforeach;
	}
	
	function admin_notices() {

		$webservice_key = get_option("cpi_webservice_key","");
		$user_id = get_option("cpi_user_id","");

		if( ( !$webservice_key || !$user_id ) && !$this->is_on_settings_page() ) :

		    require_once CPI_ABSPATH."/inc/webservice_credentials_not_set.php";

		else :

	    	if( !$this->webservice_credentials_valid() ):

		    	require_once CPI_ABSPATH."/inc/webservice_credentials_invalid.php";

			else:

				if( $this->get_remaining_users_count() > 0 ) :

		    		require_once CPI_ABSPATH."/inc/users_for_importing.php";

		    	endif;

				if( $this->is_on_settings_page() ) :

		    		require_once CPI_ABSPATH."/inc/webservice_credentials_valid.php";

		    	endif;

			endif;

	    endif;

	}

	function admin_menu(){
		add_options_page( 'CRM PRO Importer Settings', 'CRM PRO Importer Settings', 'manage_options', 'cpi-settings', array( $this, 'settings_page' ) );
		add_management_page( 'CRM PRO Import', 'CRM PRO Import', 'manage_options', 'cpi-import', array( $this, 'import_page' ) );
	}

	function admin_print_styles(){

		wp_enqueue_style( "cpi-admin" , CPI_URL ."/css/admin.css" );
		wp_enqueue_script( "cpi-admin" , CPI_URL ."/js/admin.js" );
		wp_enqueue_script( 'jquery-ui-progressbar', CPI_URL . '/js/jquery.ui.progressbar.min.1.7.2.js', array( 'jquery-ui-core' ), '1.7.2' );
		wp_enqueue_style( 'jquery-ui-regenthumbs', CPI_URL . '/css/jquery-ui-1.7.2.custom.css', array(), '1.7.2' );
	}

	function admin_init(){

		if( isset($_POST["action"] ) && $_POST["action"] == "cpi_settings_save" ) : 

			$this->save_settings();

			wp_redirect( admin_url( 'options-general.php?page=cpi-settings&updated=true' )  );

			exit;

		endif;
	}

	function manage_users_columns( $columns ){

		$new_columns = array();

		foreach( $columns as $index=>$column ):

			$new_columns[ $index ] = $column;

			if( $index == "cb" ):
				$new_columns["cpi"] = "CRM PRO Importer";
			endif;

		endforeach;

	    return $new_columns;
	}

	function manage_users_custom_column( $value, $column_name, $user_id ){
		

		switch( $column_name ) :

			case "cpi" :

				$webservice_user_id = (int)get_option("cpi_user_id","");

				$imported_time = get_user_meta(  $user_id , "cpi_imported_".$webservice_user_id , true );
				$updated = get_user_meta(  $user_id , "cpi_updated" , true );
				$imported = $imported_time !== "";

				$html =  "";

				if( $updated && $imported ):

					$html .= "<span class='cpi-status cpi-status-no' title='Click to update user into CRM PRO' ></span>";

				elseif( $imported ) :

					$html .= "<span class='cpi-status cpi-status-yes' title='Imported at ".$imported_time.". Click to update the record in CRMPRO.' ></span>";
				else :

					$html .= "<span class='cpi-status cpi-status-no' title='Import this user to CRM PRO' ></span>";

				endif;

				return $html;

			break;

			default :

				return $value;

			break;

		endswitch;
	}

	function manage_users_sortable_columns( $columns ){

		$columns["cpi"] = "cpi";

		return $columns;
	}	

	function settings_page(){
		 require_once CPI_ABSPATH."/inc/page_settings.php";
	}

	function save_settings(){

		$webservice_key = stripslashes( $_POST["cpi_webservice_key"] );
		$webservice_user_id = stripslashes( $_POST["cpi_user_id"] );

		update_option( 'cpi_webservice_key' , $webservice_key );
		update_option( 'cpi_user_id' , $webservice_user_id );

		$this->test_webservice_credentials();

	}

	function is_on_settings_page(){
		return isset( $_GET["page"] ) && $_GET["page"] === "cpi-settings";
	}

	function import_page(){
		 require_once CPI_ABSPATH."/inc/page_import.php";
	}

	function test_webservice_credentials(){

		$webservice_key = get_option("cpi_webservice_key","");
		$webservice_user_id = get_option("cpi_user_id","");

		$request = new CPI_WEBSERVICE_REQUEST( $webservice_key , $webservice_user_id );

		$request->enableDebugging();

		delete_transient( "cpi_webservice_access" );

		if( $request->test() ):

			set_transient( "cpi_webservice_access" , "YES" , 60 * 60 * 24 );

			return true;
		else :

			set_transient( "cpi_webservice_access" , "NO" , 60 * 60 * 24 );

			return false;
		endif;

	}

	function webservice_credentials_valid(){

		if( get_transient("cpi_webservice_access" ) !== false ) return get_transient("cpi_webservice_access" ) === "YES";

		return $this->test_webservice_credentials();
	}

	function user_imported( $user_id ){

		$webservice_user_id = (int)get_option("cpi_user_id","");
		delete_user_meta( $user_id , "cpi_updated ");
		add_user_meta( $user_id , "cpi_imported_".$webservice_user_id, date("m/d/Y H:i:s"), true ) or update_user_meta(  $user_id , "cpi_imported_".$webservice_user_id, date("m/d/Y H:i:s") );

		$hash = get_user_meta(  $user_id , "cpi_hash" , true );
		$updated = get_user_meta(  $user_id , "cpi_updated" , true );
		$userdata = get_userdata( $user_id );

		$data = array(
			trim( $userdata->user_firstname ),
			trim( $userdata->user_lastname ),
			$userdata->user_email
		);

		$merged_data = implode(",",$data);
		$data_hash = md5( $merged_data ."cpi" );

		add_user_meta( $user_id , "cpi_hash", $data_hash , true ) or update_user_meta(  $user_id , "cpi_hash", $data_hash );

	}

	function get_user_roles() {

		global $wp_roles;

		if ( ! isset( $wp_roles ) )
		    $wp_roles = new WP_Roles();

		return $wp_roles->get_names();
	}

	function get_remaining_users_count( $roles = "" ){

		return count( $this->get_remaining_users( $roles ) );
	}

	function get_remaining_users( $roles = "" ){

		$webservice_user_id = (int)get_option("cpi_user_id","");

		$users = array();

		if( is_array( $roles ) ):

			foreach( $roles as $role ):

				$users = array_merge( 
					$users, 
					get_users( 
						array(
							"role" => $role,
							"meta_query" => array(
								array(
									'key'     => "cpi_imported_".$webservice_user_id,
									'compare' => 'NOT EXISTS'
								)
							)
						) 
					)
				);

			endforeach;

		else :

			$users =  get_users( 
				array(
					"role" => $roles,
					"meta_query" => array(
						array(
							'key'     => "cpi_imported_".$webservice_user_id,
							'compare' => 'NOT EXISTS'
						)
					)
				)
			);

		endif;

		return $users;
	}

	function get_all_users_count( $roles = "" ){

		return count( $this->get_all_users( $roles ) );
	}

	function get_all_users( $roles ){

		$users = array();

		if( is_array( $roles ) ):

			foreach( $roles as $role ):

				$users = array_merge( 
					$users, 
					get_users( 
						array(
							"role" => $role
						) 
					)
				);

			endforeach;

		else :

			$users =  get_users( 
				array(
					"role" => $roles
				)
			);

		endif;

		return $users;
	}

	private function import_user( $user_id ){

		$webservice_key = get_option("cpi_webservice_key","");
		$webservice_user_id = get_option("cpi_user_id","");

		$request = new CPI_WEBSERVICE_REQUEST( $webservice_key , $webservice_user_id );

		$userdata = get_userdata( $user_id );
		
		$data = array(
			"firstname" => trim( $userdata->user_firstname ),
			"lastname" =>trim( $userdata->user_lastname ),
			"email" => $userdata->user_email
		);

		if( $data["firstname"] == "" && $data["lastname"] == "" ):

			$data["firstname"] = $userdata->user_login;
			$data["lastname"] = "";

		endif;

		$service_response = $request->create_contact( $data );

		
		if( $service_response["status"] === "OK" ):
			$this->user_imported( $user_id );
		endif;

		return $service_response;

	}

	function ajax_import(){

		$user_id = (int)$_POST["user_id"];

		$response = array();

		$response["time"] = date("m/d/Y H:i:s");

		$service_response = $this->import_user( $user_id );
	
		header("Content-type:application/json");
		echo json_encode( $service_response );
		exit;
	}

	function ajax_get_users(){

		$roles = $_POST["roles"];

		$all = (int)$_POST["all"];

		$users = $all === 1 ? $this->get_all_users( $roles ) : $this->get_remaining_users( $roles );

		$response = array();

		$response["users"] = array();

		foreach( $users as $user ):

			$userdata = get_userdata( $user->ID );


			if( !$userdata->user_firstname && !$userdata->user_lastname ):
				$username = $userdata->user_login;
			else : 
				$username = trim( $userdata->user_firstname." ".$userdata->user_lastname );
			endif;

			$response["users"][] = array(
				"user_id" => $user->ID,
				"name" => $username
			);

		endforeach;

		header("Content-type:application/json");
		echo json_encode( $response );
		exit;
	}

	function profile_update( $user_id ){

		$webservice_user_id = (int)get_option("cpi_user_id","");
		$hash = get_user_meta(  $user_id , "cpi_hash" , true );
		$updated = get_user_meta(  $user_id , "cpi_updated" , true );
		$userdata = get_userdata( $user_id );

		$data = array(
			trim( $userdata->user_firstname ),
			trim( $userdata->user_lastname ),
			$userdata->user_email
		);

		$merged_data = implode(",",$data);
		$data_hash = md5( $merged_data ."cpi" );

		if( $hash !== $data_hash ):
			add_user_meta( $user_id , "cpi_updated" , 1 , true ) or update_user_meta( $user_id , "cpi_updated" , 1  );
			delete_user_meta( $user_id , "cpi_imported_".$webservice_user_id );
		endif;	

	}
}
