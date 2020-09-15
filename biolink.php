<?php
/*
Plugin Name: BioLink
Plugin URI: https://shao-lin.org
Description: This plugin redirect to your special site from BioLink (ex. on Instagram) 
Author: Monk
Version: 1.0
Author URI: https://shao-lin.org
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_BIOLINK_VERSION', '1.0' );


global $wpdb;
$wpdb->biolink = $wpdb->prefix . 'biolink';

function biolink_activation( $network_wide ) {
	if ( is_multisite() && $network_wide ) {
		$ms_sites = function_exists( 'get_sites' ) ? get_sites() : wp_get_sites();

		if( 0 < count( $ms_sites ) ) {
			foreach ( $ms_sites as $ms_site ) {
				$blog_id = class_exists( 'WP_Site' ) ? $ms_site->blog_id : $ms_site['blog_id'];
				switch_to_blog( $blog_id );
				biolink_activate();
				restore_current_blog();
			}
		}
	} else {
		biolink_activate();
	}
}

function biolink_activate() {
	
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	// Create Post Ratings Table
	$create_sql = "CREATE TABLE $wpdb->biolink (".
		"link_id INT(11) NOT NULL auto_increment,".
		"link_to TEXT NOT NULL,".
		"clicks INT(11) NOT NULL DEFAULT 0,".
		"link_timestamp VARCHAR(15) NOT NULL ,".
		"active INT(1) NOT NULL DEFAULT 0 ,".
		"PRIMARY KEY (link_id)".
		") $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $create_sql );
	
	$role = get_role( 'administrator' );
	$role->add_cap( 'manage_biolink' );
}



class WPBiolinkAdmin {


	public function __construct() {

		add_action( 'admin_menu', array( $this, 'biolink_menu' ) );
	}
	
	
	public function biolink_menu() {
		
		add_menu_page(
			__( 'BioLink', 'biolink' ),
			__( 'BioLink', 'biolink' ),
			'manage_biolink',
			'biolink/manager.php',
			'',
			'dashicons-admin-site-alt2'
		);
	}
	
}

new WPBiolinkAdmin();


register_activation_hook( __FILE__, 'biolink_activation' );


function biolink_shortcode( $atts ) {
	ob_start();
	global $wpdb;
	
	$active = $wpdb->get_row("SELECT `link_to`,`link_id` FROM $wpdb->biolink WHERE `active`=1 LIMIT 1");

	if($active){
		$wpdb->query("UPDATE $wpdb->biolink SET clicks = clicks+1 WHERE link_id = $active->link_id LIMIT 1");
		?>
		<span class="dashicons dashicons-admin-site-alt2"></span> Waiting for redirect
		<p>If not redirect, click the link: <a href="<?php echo $active->link_to;?>"><?php echo $active->link_to;?></a></p>
		<script>window.location.replace("<?php echo $active->link_to;?>");</script>
		<?php
	}
	return ob_get_clean();	
}
add_shortcode( 'biolink', 'biolink_shortcode' );
