<?php
/*
 * Plugin Name:	WP Plugins Statistics Table
 * Plugin URI:	https://bplugins.com/
 * Description:	Get Plugin Statistics Table
 * Version:		1.0.0
 * Author:		bPlugins LLC
 * Author URI:	http://bplugins.com
 * License:		GPLv3
 */

require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

if ( !class_exists( 'BPluginsWPPluginsStatsTable' ) ) {
	class BPluginsWPPluginsStatsTable{
		public function __construct(){
			add_shortcode( 'stats_table', [$this, 'statsShortCode'] );
			add_shortcode( 'wp_enqueue_scripts', [$this, 'enqueueScripts'] );
		}

		function enqueueScripts(){
            wp_enqueue_style( 'dataTables', 'https://cdn.datatables.net/2.1.3/css/dataTables.dataTables.css' );
			wp_enqueue_script( 'dataTables', 'https://cdn.datatables.net/2.1.3/js/dataTables.js', [], true );
        }

		function lastDayDownloads( $slug ){
			$apiUrl = "https://api.wordpress.org/stats/plugin/1.0/downloads.php?slug=$slug&limit=2";
			$response = wp_remote_get( $apiUrl );
			$responseBody = wp_remote_retrieve_body( $response );
			$result = json_decode( $responseBody, true );

			if ( !empty( $result ) ) {
				return end( $result );
			} else {
				return 0;
			}
		}

		function lastUpdateTime( $time ){
			$day = intval( ( time() - strtotime( $time ) ) / ( 60 * 60 * 24 ) );
			if( $day === 0 ){
				return 'Today';
			}else if( $day === 1 ){
				return 'Yesterday';
			}else {
				return "$day days"; 
			}
		}

		function truncateText( $text, $length ) {
			$ellipsis = '...';
			if ( mb_strlen( $text ) > $length ) {
				$text = mb_strimwidth( $text, 0, $length, $ellipsis );
			}
			return $text;
		}

		function statsShortCode( $atts ){
			extract( shortcode_atts( array( 'limit' => 100 ), $atts ) );

			$author = $_GET['user'] ?? 'bplugins';

			$plugins = plugins_api( 'query_plugins', array(
				'author' => $author,
				'per_page' => $limit,
				//'fields' => $fields
			) );

			ob_start(); ?>
<html>
	<head>
		<meta name='viewport' content='width=device-width, initial-scale=1.0'>

		<link rel='stylesheet' href='https://cdn.datatables.net/2.1.3/css/dataTables.dataTables.css' />
	</head>

	<style>
		.container {
			width: 100%;
			max-width: 100%;
			padding-right: 15px;
			padding-left: 15px;
			margin-right: auto;
			margin-left: auto;
		}

		.name{
			flex: 1;
			font-size: 15px;
			font-weight: 500;
			text-decoration: none;
			color: #0000ee;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.totalActiveInstalls, .totalDownloads{
			font-size: 16px !important;
			line-height: 22px;
			padding-left: 12px;
			border-left: 4px solid #1abc9c;
			margin: 25px 0 0 0 !important;
		}

		@media only screen and (min-width: 1230px) {
			.container{
				max-width: 1260px;
			}
		}
	</style>

	<section>
		<div class='container'>
			<table id='statsTable' class='dataTable'>
				<thead>
					<tr>
						<th>Name</th>
						<th>Active Installs</th>
						<th>Yesterday ⬇️</th>
						<th>Last Update Date</th>
					</tr>
				</thead>

				<tbody>
					<?php
					$allPluginsActiveInstalls = 0;
					$allPluginsLastDayDownloads = 0;

					foreach ( $plugins->plugins as $plugin ):
						extract( $plugin );

						$allPluginsActiveInstalls = $allPluginsActiveInstalls + (int) $plugin['active_installs'];

						$lastDayDL = $this->lastDayDownloads( $slug );
						$allPluginsLastDayDownloads = $allPluginsLastDayDownloads + $lastDayDL;
					?>
					<tr>
						<td>
							<a class='name' target='_blank' href='https://wordpress.org/plugins/<?php echo esc_attr( $slug ); ?>/advanced'>
								<?php echo esc_html( $name, 30 ); ?>
							</a>
						</td>

						<td><?php echo esc_html( $plugin['active_installs'] ); ?></td>

						<td><?php echo esc_html( $lastDayDL ); ?></td>

						<td><?php echo esc_html( date_format( date_create( $last_updated ), 'Y/m/d' ) ); ?> (<?php echo esc_html( $this->lastUpdateTime( $last_updated ) ); ?>)</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p class='totalDownloads'>
				Yesterday's Total Downloads: <strong><?php echo esc_html( number_format( $allPluginsLastDayDownloads ) ); ?></strong>
			</p>

			<p class='totalActiveInstalls'>
				Total Active Installs: <strong><?php echo esc_html( number_format( $allPluginsActiveInstalls ) ); ?></strong>
			</p>
		</div>
	</section>

	<script src='https://code.jquery.com/jquery-3.7.1.min.js'></script>
	<script src='https://cdn.datatables.net/2.1.3/js/dataTables.js'></script>
	<script>
		let table = new DataTable('#statsTable', {
			paging: false
		});
	</script>
</html>
			<?php return ob_get_clean();
		}
	}
}
new BPluginsWPPluginsStatsTable();