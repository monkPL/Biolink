<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$base_name = plugin_basename( 'biolink/manager.php' );
$base_page = admin_url( 'admin.php?page=' . urlencode( $base_name ) );

$biolink_perpage  = isset( $_GET['perpage'] ) ? intval( $_GET['perpage'] ) : 20;
$biolink_page = ! empty( $_GET['biopage'] ) ? intval( $_GET['biopage'] ) : 1;

if( !empty($_GET['active'])) {
	$link_id = intval($_GET['active']);
	if($link_id) {
		$wpdb->query("UPDATE {$wpdb->biolink} SET active = 0 WHERE 1=1");
		$wpdb->query("UPDATE {$wpdb->biolink} SET active = 1 WHERE `link_id`=".(int)$link_id);
	}

}

if( !empty($_POST['addlink']) ) {

	check_admin_referer('biolink_log');
	if ( ! empty( $_POST['link_to'] ) ) {
		$link_to = $_POST['link_to'];
		$active = isset($_POST['active']) ? (int)$_POST['active'] : 0;
	}
	if($active){
		$wpdb->query("UPDATE {$wpdb->biolink} SET active = 0 WHERE 1=1");
	}
	$wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->biolink} VALUES (%d, %s, %d, %d, %d )", 0, $link_to, 0, current_time('timestamp'), $active));
}


$total_links = intval($wpdb->get_var("SELECT COUNT(link_id) FROM $wpdb->biolink WHERE 1=1"));



$offset = ($biolink_page-1) * $biolink_perpage;

if(($offset + $biolink_perpage) > $total_links) {
	$max_on_page = $total_links;
} else {
	$max_on_page = ($offset + $biolink_perpage);
}

// Determine Number Of Ratings To Display On Page
if (($offset + 1) > ($total_links)) {
	$display_on_page = $total_links;
} else {
	$display_on_page = ($offset + 1);
}

// Determing Total Amount Of Pages
$total_pages = ceil($total_links / $biolink_perpage);

$biolinks_urls = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->biolink} WHERE 1=1 LIMIT %d, %d", $offset, $biolink_perpage ) );
?>
<div class="wrap">
	<h1><?php esc_html_e('Manage BioLink', 'biolink'); ?></h1>
	<p>Create site or page with this shortcode to redirect your active link</p>
	<textarea readonly="true" style="text-align: center">[biolink]</textarea>
	<h2><?php esc_html_e('BioLink Logs', 'biolink'); ?></h2>
	<p><?php printf(

		esc_html__('Displaying %1$s to %2$s of %3$s biolinks log entries.', 'biolink'),
		'<strong>' . number_format_i18n( $display_on_page ) . '</strong>',
		'<strong>' . number_format_i18n( $max_on_page ) . '</strong>',
		'<strong>' . number_format_i18n( $total_links ) . '</strong>'
		);
	?></p>
	<table class="widefat">
		<thead>
			<tr>
				<th width="2%"><?php esc_html_e('ID', 'biolink'); ?></th>
				<th width="30%"><?php esc_html_e('URL', 'biolink'); ?></th>
				<th width="10%"><?php esc_html_e('Clicks', 'biolink'); ?></th>
				<th width="20%"><?php esc_html_e('Date / Time', 'biolink'); ?></th>
				<th width="10%"><?php esc_html_e('Active', 'biolink'); ?></th>
				<th width="10%"><?php esc_html_e('Set Active', 'biolink'); ?></th>
			</tr>
		</thead>
		<tbody>
	<?php
		if($biolinks_urls) {
			$i = 0;
			foreach($biolinks_urls as $biolinks_url) {
				if($i%2 == 0) {
					$style = 'class="alternate"';
				}  else {
					$style = '';
				}
				$link_id = intval($biolinks_url->link_id);
				$link_to = esc_html( stripslashes($biolinks_url->link_to) );
				$clicks = intval($biolinks_url->clicks);
				$link_date = esc_html(mysql2date(sprintf(__('%s @ %s', 'biolink'), get_option('date_format'), get_option('time_format')), gmdate('Y-m-d H:i:s', $biolinks_url->link_timestamp)));
				$active = $biolinks_url->active ? '<span style="color:green" class="dashicons dashicons-yes-alt"></span>' : '<span class="dashicons dashicons-minus"></span>';
				echo "<tr $style>\n";
				echo '<td>'.$link_id.'</td>'."\n";
				echo '<td><a href="'.$link_to.'" target="_new">'.$link_to.'</a></td>'."\n";
				echo '<td><span class="dashicons dashicons-chart-bar"></span> '.number_format_i18n($clicks).'</td>'."\n";
				echo '<td><span class="dashicons dashicons-calendar-alt"></span>'.$link_date.'</td>'."\n";
				echo "<td>$active</td>\n";
				echo "<td><a href='".esc_url( $base_page."&active=".$link_id )."'>Set</a></td>\n";
				echo '</tr>';
				$i++;
			}
		} else {
			echo '<tr><td colspan="6" align="center"><strong>'.esc_html__('No Logs Found', 'biolink').'</strong></td></tr>';
		}
	?>
		</tbody>
	</table>
		<?php
			if($total_pages > 1) {
		?>
		<br />
		<table class="widefat">
			<tr>
				<td align="<?php echo is_rtl() ? 'right' : 'left'; ?>" width="50%">
					<?php
						if($biolink_page > 1 && ((($biolink_page*$biolink_perpage)-($biolink_perpage-1)) <= $total_ratings)) {
							echo '<strong>&laquo;</strong> <a href="'.$base_page.'&amp;biopage='.($biolink_page-1).'" title="&laquo; '.esc_html__('Previous Page', 'biolink').'">'.esc_html__('Previous Page', 'biolink').'</a>';
						} else {
							echo '&nbsp;';
						}
					?>
				</td>
				<td align="<?php echo is_rtl() ? 'left' : 'right'; ?>" width="50%">
					<?php
						if($biolink_page >= 1 && ((($biolink_page*$biolink_perpage)+1) <=  $total_ratings)) {
							echo '<a href="'.$base_page.'&amp;biopage='.($biolink_page+1).'" title="'.esc_html__('Next Page', 'biolink').' &raquo;">'.esc_html__('Next Page', 'biolink').'</a> <strong>&raquo;</strong>';
						} else {
							echo '&nbsp;';
						}
					?>
				</td>
			</tr>
			<tr class="alternate">
				<td colspan="2" align="center">
					<?php printf(esc_html__('Pages (%s): ', 'wp-postratings'), number_format_i18n($total_pages)); ?>
					<?php
						if ($biolink_page >= 4) {
							echo '<strong><a href="'.$base_page.'&amp;biopage=1'.'" title="'.esc_html__('Go to First Page', 'biolink').'">&laquo; '.esc_html__('First', 'biolink').'</a></strong> ... ';
						}
						if($biolink_page > 1) {
							echo ' <strong><a href="'.$base_page.'&amp;biopage='.($biolink_page-1).'" title="&laquo; '.esc_html__('Go to Page', 'biolink').' '.number_format_i18n($biolink_page-1).'">&laquo;</a></strong> ';
						}
						for($i = $biolink_page - 2 ; $i  <= $biolink_page +2; $i++) {
							if ($i >= 1 && $i <= $total_pages) {
								if($i == $biolink_page) {
									echo '<strong>['.number_format_i18n($i).']</strong> ';
								} else {
									echo '<a href="'.$base_page.'&amp;biopage='.($i).'" title="'.esc_html__('Page', 'biolink').' '.number_format_i18n($i).'">'.number_format_i18n($i).'</a> ';
								}
							}
						}
						if($biolink_page < $total_pages) {
							echo ' <strong><a href="'.$base_page.'&amp;biopage='.($biolink_page+1).'" title="'.esc_html__('Go to Page', 'biolink').' '.number_format_i18n($biolink_page+1).' &raquo;">&raquo;</a></strong> ';
						}
						if (($biolink_page+2) < $total_pages) {
							echo ' ... <strong><a href="'.$base_page.'&amp;biopage='.($total_pages).'" title="'.esc_html__('Go to Last Page', 'biolink').'">'.esc_html__('Last', 'biolink').' &raquo;</a></strong>';
						}
					?>
				</td>
			</tr>
		</table>

		<?php
			}
		?>
	<br />
	<form action="<?php echo esc_url( $base_page ); ?>" method="post">
		<input type="hidden" name="page" value="<?php echo $base_name; ?>" />
		<input type="hidden" name="active" value="0" />
		<?php wp_nonce_field('biolink_log'); ?>
		<h3><?php echo esc_html__('Add new link', 'biolink');?></h3>
		<table class="widefat">
			<tr>
				<td>URL: <input type="text" name="link_to" value="" /></td>
			</tr>
			<tr>
				<td>Active: <input type="checkbox" name="active" value="1" /></td>
			</tr>
			<tr>
				<td><input type="submit" name="addlink" value="<?php esc_html_e('Save', 'biolink'); ?>" class="button" /></td>
			</tr>
		</table>
	</form>
</div>