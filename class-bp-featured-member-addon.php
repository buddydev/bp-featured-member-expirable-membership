<?php
/**
 * Plugin Name: BuddyPress Featured Member Addon
 * Description: An addon for featured member to unmark user as featured after certain period of time
 * Version: 1.0.0
 * Author: BuddyDev
 *
 * @package bp-featured-member-addon
 */

/**
 * Class BP_Featured_Member_Addon
 */
class BP_Featured_Member_Addon {

	/**
	 * Singleton Instance
	 *
	 * @var BP_Featured_Member_Addon
	 */
	private static $instance = null;

	/**
	 * BP_Featured_Member_Addon constructor.
	 */
	private function __construct() {
		$this->setup();
	}

	/**
	 * Get class instance
	 *
	 * @return BP_Featured_Member_Addon
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Setup callback on necessaries hooks
	 */
	private function setup() {

		register_activation_hook( __FILE__, array( $this, 'on_activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'on_deactivation' ) );

		add_action( 'bp_featured_members_user_added', array( $this, 'save_date' ) );
		add_action( 'bp_fma_remove_featured', array( $this, 'remove_featured' ) );
	}

	/**
	 * Setup hourly cronjob to remove featured members
	 */
	public function on_activation() {

		if ( ! wp_next_scheduled( 'bp_fma_remove_featured' ) ) {
			wp_schedule_event( time(), 'hourly', 'bp_fma_remove_featured' );
		}
	}

	/**
	 * Remove cronjob
	 */
	public function on_deactivation() {
		wp_clear_scheduled_hook( 'bp_fma_remove_featured' );
	}

	/**
	 * Save datetime when a user marked as featured
	 *
	 * @param int $user_id User id.
	 */
	public function save_date( $user_id ) {
		update_user_meta( $user_id, '__marked_featured', time() );
	}

	/**
	 * Removed featured members
	 *
	 * @return mixed
	 */
	public function remove_featured() {

		global $wpdb;

		if ( ! function_exists( 'bp_featured_members' ) ) {
			return;
		}

		$interval = apply_filters( 'bp_fma_remove_interval', ( 7 * 24 * 60 * 60 ) );

		$sub_query = $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s  AND meta_value < %d", '__marked_featured', ( time() - $interval ) );

		$where_sql = $wpdb->prepare( "( meta_key=%s OR meta_key=%s )", '_is_featured', '__marked_featured' );

		$query = "DELETE FROM {$wpdb->usermeta} WHERE {$where_sql} AND user_id IN ( $sub_query )";

		$wpdb->query( $query );
	}
}

BP_Featured_Member_Addon::get_instance();

