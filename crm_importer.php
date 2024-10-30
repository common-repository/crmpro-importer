<?php
/*
Plugin Name: CRM PRO Importer
Description: Plugin used to import users to CRM PRO
Author: Vukadin Njegos
Version: 1.0
*/

define( "CPI_PLUGIN",  __FILE__ );
define( "CPI_ABSPATH", dirname( __FILE__ )  );
define( "CPI_URL", plugins_url( "" , __FILE__ ) );

require_once "lib/class.CPI_WEBSERVICE_REQUEST.php";
require_once "lib/class.CPI_PLUGIN.php";

new CPI_PLUGIN();