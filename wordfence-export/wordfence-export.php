<?php
/**
 * @package wordfence-export
 * @version 1.0
 */
/*
Plugin Name: Wordfence Export
Plugin URI: n/a
Description: Export data from Wordfence
Author: asa@wordfence.com
Version: 1.0
Author URI: https://www.wordfence.com
*/

add_action('plugins_loaded', 'wfex_export');

function wfex_export() {
	
	// Check if there is an export request
	$wfEx = isset($_GET['_wfex']) ? @$_GET['_wfex'] : false;
	
	if ($wfEx) {
		
		// Verify it's an admin
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}		
		
		// Verify nonce
		$nonceValid = wp_verify_nonce(@$_GET['nonce'], 'wfex');
		if(!$nonceValid){
			die("Invalid security token.");
		}			
		
		// Output export
		$valid_exports = Array(
			'LiveTraffic' => 'LiveTraffic',
			'Logins' => 'Logins'
		);
				
		if (array_key_exists($wfEx, $valid_exports)) {
			header('Content-type: text/csv');
			header('Content-disposition: attachment;filename='.$valid_exports[$wfEx].'-'.date("Y-m-d").'.csv');
	
			if( class_exists( 'wordfence' ) ) {
				
				switch ($wfEx) {
					
					case 'LiveTraffic':
						wfex_exportLiveTraffic();
						break;
					case 'Logins':
						wfex_exportLogins();
						break;
				}
				
			} else {
				die("This plugin requires Wordfence to be installed and activated.");
			}
			
		} else {
			die("Invalid export type.");
		}

	}
	
}

function wfex_exportLiveTraffic() {
	// Live Traffic
	global $wpdb;
	$table = $wpdb->base_prefix . 'wfHits';
	
	$myrows = $wpdb->get_results( "SELECT * FROM ".$table." ORDER BY ctime DESC" );

	$fp = fopen('php://output', 'w');
	fputcsv($fp, array("Date", "IP", "StatusCode", "URL", "Referer", "IsHuman", "IsGoogle", "Action", "UserAgent" ));
	foreach ( $myrows as $row ) {

		$niceip = wfUtils::inet_ntop($row->IP);
		$nicedate = date('Y-m-d H:i:s', $row->ctime);
		
		$line = array($nicedate, $niceip, $row->statusCode, $row->URL, $row->referer, $row->jsRun, $row->isGoogle, $row->action, $row->UA);
		
		fputcsv($fp, $line);
	}
	fclose($fp);
}

function wfex_exportLogins() {
	// Logins
	global $wpdb;
	$table = $wpdb->base_prefix . 'wfLogins';
	
	$myrows = $wpdb->get_results( "SELECT * FROM ".$table." ORDER BY ctime DESC" );

	$fp = fopen('php://output', 'w');
	fputcsv($fp, array("Date", "IP", "Fail", "Username", "UserID", "Action", "UserAgent" ));
	foreach ( $myrows as $row ) {

		$niceip = wfUtils::inet_ntop($row->IP);
		$nicedate = date('Y-m-d H:i:s', $row->ctime);
		
		$line = array($nicedate, $niceip, $row->fail, $row->username, $row->userID, $row->action, $row->UA);
		
		fputcsv($fp, $line);
	}
	fclose($fp);
}
	
add_action( 'network_admin_menu', 'wfex_admin_page' );
add_action( 'admin_menu', 'wfex_admin_page' );		

function wfex_admin_page() {
	add_menu_page( "wfex", "WF Export", 'manage_options', 'wfex', 'wfex_page');	
}

function wfex_page() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	$nonce = wp_create_nonce('wfex');

?>
<h1>Wordfence Export</h1>
	<p>CSV download will start when you click a link.</p>
	
	<?php if( class_exists( 'wordfence' ) ) { ?>
		<a href="?_wfex=LiveTraffic&nonce=<?php echo $nonce; ?>">Live Traffic CSV</a><br/>
		<a href="?_wfex=Logins&nonce=<?php echo $nonce; ?>">Logins and Login attempts CSV</a><br/>
		<?php
	} else { ?>
		This plugin requires Wordfence to be installed and activated.
	<?php
	}
}

