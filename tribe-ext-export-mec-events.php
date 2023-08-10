<?php
/**
 * Plugin Name:       The Events Calendar Extension: Export Events from Modern Events Calendar
 * Plugin URI:        https://theeventscalendar.com/extensions/migrating-events-from-modern-events-calendar
 * Description:       Export Events from Modern Events Calendar
 * Version:           1.0.1
 * Extension Class:   Tribe__Extension__Export_Events_Modern_Events_Calendar
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-export-mec-events
 * Author:            Modern Tribe, Inc.
 * Author URI:        http://m.tri.be/1971
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tribe-ext-export-mec-events
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

// Do not load unless Tribe Common is fully loaded and our class does not yet exist.
use Tribe\Events\Admin\Settings;

if ( class_exists( 'Tribe__Extension' ) && ! class_exists( 'Tribe__Extension__Export_Events_Modern_Events_Calendar' ) ) {
	/**
	 * Extension main class, class begins loading on init() function.
	 */
	class Tribe__Extension__Export_Events_Modern_Events_Calendar extends Tribe__Extension {

		/**
		 * Setup the Extension's properties.
		 *
		 * This always executes even if the required plugins are not present.
		 */
		public function construct() {
			$this->add_required_plugin( 'Tribe__Events__Main', '5.15.0' );
			$this->set_url( 'https://theeventscalendar.com/extensions/' );
		}

		/**
		 * Extension initialization and hooks.
		 */
		public function init() {

			// Loads the extension’s translated strings
			load_plugin_textdomain( 'tribe-ext-export-mec-events', false, basename( dirname( __FILE__ ) ) . '/languages/' );

			add_action( 'admin_init', array( $this, 'add_admin_settings' ) );
			add_action( 'load-tribe_events_page_' . Settings::$settings_page_id, array(
				$this,
				'listen_for_export_button',
			), 10, 0 );
		}

		/**
		 * Add the Admin Settings
		 */
		public function add_admin_settings() {
			if ( ! class_exists( 'Tribe__Extension__Settings_Helper' ) ) {
				require_once dirname( __FILE__ ) . '/src/Tribe/Settings_Helper.php';
			}

			$setting_helper = new Tribe__Settings_Helper();

			$setting_helper->add_field( 'export-defaults-mec', array(
				'type' => 'html',
				'html' => '<h3 id="tribe-ext-export-mec-events">'
                    . esc_html__( 'Migration Tools - Modern Events Calendar', 'tribe-ext-export-mec-events' )
                    . '</h3>',
			), 'imports', 'tribe_aggregator_disable', false );

			$setting_helper->add_field( 'mec_export_events', array(
				'type' => 'html',
				'html' => '<fieldset class="tribe-field tribe-field-html"><legend>'
                    . esc_html__( 'Events', 'tribe-ext-export-mec-events' )
                    . '</legend><div class="tribe-field-wrap">'
                    . $this->export_button( 'export_events', esc_html__( 'Export Events', 'tribe-ext-export-mec-events' ) )
                    . '<p class="tribe-field-indent description">'
                    . esc_html__(
                        'Export your events from Modern Event Calendar and use the CSV file to import them to The Event Calendar. Before exporting your data, please make sure that Modern Event Calendar is enabled on your site.',
                        'tribe-ext-export-mec-events'
                    )
                    . sprintf(
                        '<a href="%1$s" target="_blank">%2$s</a>',
                        esc_attr( 'https://theeventscalendar.com/extensions/migrating-events-from-modern-events-calendar/' ),
                        esc_html__( 'Learn more.', 'tribe-ext-export-mec-events' )
                    )
                    . '</p></div></fieldset><div class="clear"></div>',
			), 'imports', 'tribe_aggregator_disable', false );

			$setting_helper->add_field( 'mec_export_organizers', array(
				'type' => 'html',
				'html' => '<fieldset class="tribe-field tribe-field-html"><legend>'
                    . esc_html__( 'Organizers', 'tribe-ext-export-mec-events' )
                    . '</legend><div class="tribe-field-wrap">'
                    . $this->export_button( 'export_organizers', esc_html__( 'Export Organizers', 'tribe-ext-export-mec-events' ) )
                    . '<p class="tribe-field-indent description">'
                    . esc_html__( 'Export your organizers from Modern Events Calendar.', 'tribe-ext-export-mec-events' )
                    . '</p></div></fieldset><div class="clear"></div>',
			), 'imports', 'tribe_aggregator_disable', false );

			$setting_helper->add_field( 'mec_export_venues', array(
				'type' => 'html',
				'html' => '<fieldset class="tribe-field tribe-field-html"><legend>'
                    . esc_html__( 'Venues', 'tribe-ext-export-mec-events' )
                    . '</legend><div class="tribe-field-wrap">'
                    . $this->export_button( 'export_venues', esc_html__( 'Export Venues', 'tribe-ext-export-mec-events' ) )
                    . '<p class="tribe-field-indent description">'
                    . esc_html__( 'Export your venues from Modern Events Calendar.', 'tribe-ext-export-mec-events' )
                    . '</p></div></fieldset><div class="clear"></div>',
			), 'imports', 'tribe_aggregator_disable', false );
		}

		/**
		 * Add a button to trigger the CSV creation process
		 *
		 * @param string $text
		 *
		 * @return string
		 */
		public function export_button( $type, $text = '' ) {
			$text     = $text ? $text : __( 'Export Events', 'tribe-ext-export-mec-events' );

			// get the base settings page url
			$url = apply_filters( 'tribe_settings_url', add_query_arg( array(
				'post_type' => Tribe__Events__Main::POSTTYPE,
				'page'      => Settings::$settings_page_id,
				'tab'       => 'imports',
			), admin_url( 'edit.php' ) ) );

			$url = add_query_arg( array( $type => '1' ), $url );
			$url = wp_nonce_url( $url, $type );

			return sprintf( '<a href="%s" class="button">%s</a>', $url, $text );
		}

		/**
		 * If the button is clicked, start working
		 */
		public function listen_for_export_button() {

			/**
			 * Don't run the script if Modern Events Calendar is deactivated.
			 */
			if ( ! class_exists( 'MEC' ) ) {
				return;
			}

			if ( empty( $_REQUEST['export_events'] ) && empty( $_REQUEST['export_organizers'] ) && empty( $_REQUEST['export_venues'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'export_events' ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'export_organizers' ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'export_venues' ) ) {
				return;
			}

			if ( ! empty( $_REQUEST['export_events'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'export_events' ) ) {
				$this->events_csv_setup();
			} elseif ( ! empty( $_REQUEST['export_organizers'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'export_organizers' ) ) {
				$this->organizers_csv_setup();
			} elseif ( ! empty( $_REQUEST['export_venues'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'export_venues' ) ) {
				$this->venues_csv_setup();
			}
		}

		/**
		 * Get the event data from Modern Events Calendar
		 *
		 * @return array $event_data
		 */
		public function get_event_data() {

			$event_data = array();
			$event_tag  = array();
			$event_cat  = array();

			global $wpdb;

			$events = $wpdb->get_results( "
				  SELECT t1.ID,
					t1.post_title,
       				t1.post_content,
       				t2.start,
       				t2.end,
       				t2.time_start,
       				t2.time_end,
       				t3.meta_value AS organizer_id,
       				t4.meta_value AS venue_id,

  					(SELECT sum(meta_value)
   						FROM {$wpdb->postmeta} AS t5
   						WHERE t1.ID = t5.post_id
						AND t5.meta_key = 'mec_cost') AS cost,
					(SELECT meta_value
						FROM {$wpdb->postmeta} AS t5
						WHERE t1.ID = t5.post_id
						AND t5.meta_key = 'mec_read_more') AS website
				  FROM {$wpdb->posts} AS t1
				  INNER JOIN {$wpdb->prefix}mec_events AS t2
				  INNER JOIN {$wpdb->postmeta} AS t3
				  INNER JOIN {$wpdb->postmeta} AS t4
				  WHERE t1.ID = t2.post_id
  					AND t1.ID = t3.post_id
  					AND t1.ID = t4.post_id
  					AND t3.meta_key = 'mec_organizer_id'
  					AND t4.meta_key = 'mec_location_id'
  			" );

			$events_taxonomy = $wpdb->get_results( "
				SELECT t1.object_id AS post_id,
					GROUP_CONCAT(DISTINCT IF(t3.taxonomy = 'post_tag', t2.name, NULL), '') AS post_tag,
					GROUP_CONCAT(DISTINCT IF(t3.taxonomy = 'mec_category', t2.name, NULL), '') AS post_category
				FROM {$wpdb->term_relationships} AS t1
				INNER JOIN {$wpdb->terms} AS t2 ON t1.term_taxonomy_id = t2.term_id
				INNER JOIN {$wpdb->term_taxonomy} AS t3 ON t2.term_id = t3.term_id
				WHERE t3.taxonomy IN ('post_tag', 'mec_category')
				GROUP BY t1.object_id
			" );

			foreach ( $events_taxonomy as $event_taxonomy ) {
				$event_tag[ $event_taxonomy->post_id ] = $event_taxonomy->post_tag;
				$event_cat[ $event_taxonomy->post_id ] = $event_taxonomy->post_category;
			}

			foreach ( $events as $event ) {
				$row     = array();
				$row[0]  = $event->post_title;
				$row[1]  = $event->post_content;
				$row[2]  = ( $event->start == '0000-00-00' ? '' : $event->start );
				$row[3]  = ( $event->end == '0000-00-00' ? $event->start : $event->end );
				$row[4]  = gmdate( 'H:i:s', $event->time_start );
				$row[5]  = gmdate( 'H:i:s', $event->time_end );
				$row[6]  = ( get_term( $event->venue_id )->name != 'Uncategorized' ? get_term( $event->venue_id )->name : '' );
				$row[7]  = ( get_term( $event->organizer_id )->name != 'Uncategorized' ? get_term( $event->organizer_id )->name : '' );
				$row[8]  = $event->cost;
				$row[9]  = $event->website;
				$row[10] = ( isset( $event_tag[ $event->ID ] ) ? $event_tag[ $event->ID ] : '' );
				$row[11] = ( isset( $event_cat[ $event->ID ] ) ? $event_cat[ $event->ID ] : '' );

				$event_data[] = $row;
			}

			return $event_data;
		}

		/**
		 * Get the organizer data from Modern Events Calendar
		 *
		 * @return array $organizer_data
		 */
		public function get_organizer_data() {
			$organizer_data = array();

			global $wpdb;

			/**
			 * Get the organizers data from Modern Events Calendar and stores it in a variable.
			 */

			$organizers = $wpdb->get_results( "
				SELECT DISTINCT term_id, description
				FROM {$wpdb->term_taxonomy}
				WHERE taxonomy = 'mec_organizer'
			" );

			foreach ( $organizers as $organizer ) {
				$row    = array();
				$row[0] = get_term( $organizer->term_id )->name;
				$row[1] = $organizer->description;
				$row[2] = get_term_meta( $organizer->term_id, 'email', true );
				$row[3] = get_term_meta( $organizer->term_id, 'tel', true );
				$row[4] = get_term_meta( $organizer->term_id, 'url', true );
				$row[5] = get_term_meta( $organizer->term_id, 'thumbnail', true );

				$organizer_data[] = $row;
			}

			return $organizer_data;
		}

		/**
		 * Get the venue data from Modern Events Calendar
		 *
		 * @return array $venue_data
		 */
		public function get_venue_data() {
			$venue_data = array();

			global $wpdb;
			/**
			 * Get the venues data from Modern Events Calendar and stores it in a variable.
			 */
			$venues = $wpdb->get_results( "
				SELECT DISTINCT term_id, description
				FROM {$wpdb->term_taxonomy}
				WHERE taxonomy = 'mec_location'
			" );

			foreach ( $venues as $venue ) {
				$row    = array();
				$row[0] = get_term( $venue->term_id )->name;
				$row[1] = $venue->description;
				$row[2] = get_term_meta( $venue->term_id, 'address', true );
				$row[3] = ( get_term_meta( $venue->term_id, 'latitude', true ) == 0 ? '' : get_term_meta( $venue->term_id, 'latitude', true ) );
				$row[4] = ( get_term_meta( $venue->term_id, 'longitude', true ) == 0 ? '' : get_term_meta( $venue->term_id, 'longitude', true ) );
				$row[5] = get_term_meta( $venue->term_id, 'thumbnail', true );

				$venue_data[] = $row;
			}

			return $venue_data;
		}

		/**
		 * Configure the CSV file for events
		 */
		public function events_csv_setup() {

			$data = $this->get_event_data();

			/**
			 * The name of the CSV file.
			 */
			$csv_file_name = 'tribe-ext-export-mec-events.csv';

			/**
			 * The name of the columns in the CSV file.
			 */
			$header = array(
				0  => 'Event_Name',
				1  => 'Event Description',
				2  => 'Event Start Date',
				4  => 'Event End Date',
				3  => 'Event Start Time',
				5  => 'Event End Time',
				6  => 'Event Venue Name',
				7  => 'Event Organizer Name',
				8  => 'Event Cost',
				9  => 'Event Website',
				10 => 'Event Tags',
				11 => 'Event Category',
			);

			/**
			 * Generates the CSV file.
			 */
			$this->generate_csv( $csv_file_name, $header, $data );
		}

		/**
		 * Configure the CSV file for organizers
		 */
		public function organizers_csv_setup() {

			$data = $this->get_organizer_data();

			/**
			 * The name of the CSV file.
			 */
			$csv_file_name = 'tribe-ext-export-mec-organizers.csv';

			/**
			 * The name of the columns in the CSV file.
			 */
			$header = array(
				0 => 'Organizer_Name',
				1 => 'Organizer Description',
				2 => 'Organizer Email',
				3 => 'Organizer Phone',
				4 => 'Organizer Website',
				5 => 'Organizer Featured Image',
			);

			/**
			 * Generate the CSV file.
			 */
			$this->generate_csv( $csv_file_name, $header, $data );
		}

		/**
		 * Configure the CSV file for venues
		 */
		public function venues_csv_setup() {

			$data = $this->get_venue_data();

			/**
			 * The name of the CSV file.
			 */
			$csv_file_name = 'tribe-ext-export-mec-venues.csv';

			/**
			 * The name of the columns in the CSV file.
			 */
			$header = array(
				0 => 'Venue_Name',
				1 => 'Venue Description',
				2 => 'Venue Address',
				3 => 'Venue Latitude',
				4 => 'Venue Longitude',
				5 => 'Venue Featured Image',
			);

			/**
			 * Generate the CSV file.
			 */
			$this->generate_csv( $csv_file_name, $header, $data );
		}

		/**
		 * Generate the CSV files.
		 *
		 * @since TBD Fixed warning from order of header output.
		 *
		 * @param string $csv_file_name - The name of the CSV file to be created
		 * @param array  $csv_header    The name of the columns
		 * @param array  $data          Event entries.
		 */
		public function generate_csv( $csv_file_name, $csv_header, $data ) {
			$fh = fopen( 'php://output', 'w' );

			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Content-Description: File Transfer' );
			header( 'Content-type: text/csv' );
			header( "Content-Disposition: attachment; filename={$csv_file_name}" );
			header( 'Expires: 0' );
			header( 'Pragma: public' );

			// Write the file header for correct encoding ( UTF8 ).
			fprintf( $fh, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

			// Output CSV data.
			fputcsv( $fh, $csv_header );
			foreach ( $data as $data_row ) {
				fputcsv( $fh, $data_row );
			}
			fclose( $fh );

			die();
		}
	}
}
