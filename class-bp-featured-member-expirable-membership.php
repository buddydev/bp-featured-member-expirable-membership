<?php
/**
 * Plugin Name: BuddyPress Featured Member Expirable Membership Addon
 * Description: An addon for featured member to unmark user as featured after certain period of time
 * Version: 1.0.0
 * Author: BuddyDev
 *
 * @package bp-featured-member-addon
 */

/**
 * Class BP_Featured_Member_Expirable_Membership
 *
 * @author Ravi Sharma
 */
class BP_Featured_Member_Expirable_Membership{

	/**
	 * Singleton Instance
	 *
	 * @var BP_Featured_Member_Expirable_Membership
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
	 * @return BP_Featured_Member_Expirable_Membership
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
	 * How much time to expire.
	 *
	 * @return int // seconds.
	 */
	private function get_interval() {
		return 7 * DAY_IN_SECONDS;  // 7 days.
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

		$interval = apply_filters( 'bpfm_expiration_interval',  $this->get_interval() );

		$sub_query = $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s  AND CAST( meta_value AS UNSIGNED ) < %d", '__marked_featured', ( time() - $interval ) );

		$where_sql = $wpdb->prepare( "( meta_key=%s OR meta_key=%s )", '_is_featured', '__marked_featured' );

		$query = "DELETE FROM {$wpdb->usermeta} WHERE {$where_sql} AND user_id IN ( $sub_query )";

		$wpdb->query( $query );
	}
}

BP_Featured_Member_Expirable_Membership::get_instance();

