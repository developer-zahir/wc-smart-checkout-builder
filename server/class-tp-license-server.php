<?php
/**
 * TP License Server — Server-Side Reference (v2.0)
 *
 * This file is EXTERNAL SERVER-SIDE REFERENCE CODE, not part of the client
 * plugin. The licensing server runs on app.developerzahir.com and is maintained
 * separately from the wc-smart-checkout-builder plugin repository.
 *
 * Place this file in your mu-plugins or active theme's functions directory
 * (or enqueue via a separate server-side plugin) on the licensing server host.
 *
 * Handles:
 *   - Custom Post Type 'tp_license' for license management
 *   - Admin UI meta boxes (Customer Name, Website URL, License Key, Status)
 *   - Custom API Endpoint: /tp-server/v1/check (no wp-json required)
 *   - Universal DB Lookup across post_title and postmeta (any status except trash)
 *   - Domain normalization with strict subdomain isolation
 *   - Instant revocation webhook to client sites on license deactivation/deletion
 *
 * @package      TP License Server
 * @version      2.0
 * @server_url   https://app.developerzahir.com
 * @client_url   https://{client-domain}/tp-client/v1/update-license
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TP_License_Server {

	const POST_TYPE = 'tp_license';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta_boxes' ), 10, 3 );
		add_action( 'before_delete_post', array( __CLASS__, 'on_delete_or_trash_post' ) );
		add_action( 'wp_trash_post', array( __CLASS__, 'on_delete_or_trash_post' ) );

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'register_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_columns' ), 10, 2 );

		// Custom API Endpoint without wp-json
		add_action( 'init', array( __CLASS__, 'handle_api_request' ), 1 );
	}

	/**
	 * 1. Register 'tp_license' Custom Post Type with native Title support.
	 *
	 * Verified: 'menu_icon' => 'dashicons-key' renders correctly in the
	 * WordPress admin menu.
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => __( 'Licenses', 'tp-license-server' ),
			'singular_name'      => __( 'License', 'tp-license-server' ),
			'menu_name'          => __( 'Licenses', 'tp-license-server' ),
			'add_new'            => __( 'Add New License', 'tp-license-server' ),
			'add_new_item'       => __( 'Add New License', 'tp-license-server' ),
			'edit_item'          => __( 'Edit License', 'tp-license-server' ),
			'new_item'           => __( 'New License', 'tp-license-server' ),
			'view_item'          => __( 'View License', 'tp-license-server' ),
			'search_items'       => __( 'Search Licenses', 'tp-license-server' ),
			'not_found'          => __( 'No Licenses Found', 'tp-license-server' ),
			'not_found_in_trash' => __( 'No Licenses Found in Trash', 'tp-license-server' ),
		);

		register_post_type( self::POST_TYPE, array(
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'   => true,
			'menu_position'   => 30,
			'menu_icon'      => 'dashicons-key',
			'capability_type' => 'post',
			'hierarchical'    => false,
			'supports'        => array( 'title' ),
			'has_archive'     => false,
			'rewrite'         => false,
			'query_var'       => false,
		) );
	}

	/**
	 * 2. Add Meta Box.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'tp_license_details',
			__( 'License Details', 'tp-license-server' ),
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render Meta Box (1-Column standard form).
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'tp_license_save_meta', 'tp_license_meta_nonce' );

		$customer_name  = get_post_meta( $post->ID, '_client_name', true );
		$client_domain  = get_post_meta( $post->ID, '_client_domain', true );
		$license_key    = get_post_meta( $post->ID, '_license_key', true );
		if ( empty( $license_key ) && ! empty( $post->post_title ) ) {
			$license_key = $post->post_title;
		}
		$license_status = get_post_meta( $post->ID, '_license_status', true );
		if ( empty( $license_status ) ) {
			$license_status = 'active';
		}
		?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="tp_client_name"><?php esc_html_e( 'Customer Name', 'tp-license-server' ); ?></label>
					</th>
					<td>
						<input type="text" name="tp_client_name" id="tp_client_name" value="<?php echo esc_attr( $customer_name ); ?>" class="regular-text" placeholder="e.g. John Doe" />
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="tp_client_domain"><?php esc_html_e( 'Website URL', 'tp-license-server' ); ?></label>
					</th>
					<td>
						<input type="text" name="tp_client_domain" id="tp_client_domain" value="<?php echo esc_attr( $client_domain ); ?>" class="regular-text" placeholder="e.g. clientdomain.com (Leave blank to auto-bind)" />
						<p class="description"><?php esc_html_e( 'Leave blank to auto-bind on the first activation request from the client site.', 'tp-license-server' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="tp_license_key"><?php esc_html_e( 'License Key', 'tp-license-server' ); ?></label>
					</th>
					<td>
						<input type="text" name="tp_license_key" id="tp_license_key" value="<?php echo esc_attr( $license_key ); ?>" class="regular-text" placeholder="e.g. TP-XXXX-XXXX-XXXX-XXXX" />
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="tp_license_status"><?php esc_html_e( 'Status', 'tp-license-server' ); ?></label>
					</th>
					<td>
						<select name="tp_license_status" id="tp_license_status">
							<option value="active" <?php selected( $license_status, 'active' ); ?>><?php esc_html_e( 'Active', 'tp-license-server' ); ?></option>
							<option value="inactive" <?php selected( $license_status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'tp-license-server' ); ?></option>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * 3. Save Meta Box Data with auto-sync between Title and Meta fields.
	 */
	public static function save_meta_boxes( $post_id, $post, $update ) {
		if ( ! isset( $_POST['tp_license_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['tp_license_meta_nonce'] ), 'tp_license_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$old_status  = get_post_meta( $post_id, '_license_status', true );
		$old_domain  = get_post_meta( $post_id, '_client_domain', true );

		$license_key = isset( $_POST['tp_license_key'] ) ? sanitize_text_field( trim( wp_unslash( $_POST['tp_license_key'] ) ) ) : '';
		if ( empty( $license_key ) && ! empty( $post->post_title ) ) {
			$license_key = sanitize_text_field( trim( $post->post_title ) );
		}

		$new_client_name = isset( $_POST['tp_client_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tp_client_name'] ) ) : '';
		$raw_domain      = isset( $_POST['tp_client_domain'] ) ? sanitize_text_field( wp_unslash( $_POST['tp_client_domain'] ) ) : '';
		$new_domain      = self::normalize_domain( $raw_domain );
		$new_status      = isset( $_POST['tp_license_status'] ) ? sanitize_text_field( wp_unslash( $_POST['tp_license_status'] ) ) : 'active';

		// Save both with and without underscore for 100% compatibility
		update_post_meta( $post_id, '_client_name', $new_client_name );
		update_post_meta( $post_id, 'client_name', $new_client_name );

		update_post_meta( $post_id, '_client_domain', $new_domain );
		update_post_meta( $post_id, 'client_domain', $new_domain );

		update_post_meta( $post_id, '_license_key', $license_key );
		update_post_meta( $post_id, 'license_key', $license_key );

		update_post_meta( $post_id, '_license_status', $new_status );
		update_post_meta( $post_id, 'license_status', $new_status );

		// If status changed to inactive, purge client cache via webhook
		if ( 'active' === $old_status && 'inactive' === $new_status ) {
			$target_domain = ! empty( $new_domain ) ? $new_domain : $old_domain;
			$key_for_hook  = ! empty( $license_key ) ? $license_key : get_the_title( $post_id );
			if ( ! empty( $target_domain ) && ! empty( $key_for_hook ) ) {
				self::send_webhook_revoke( $target_domain, $key_for_hook );
			}
		}
	}

	public static function on_delete_or_trash_post( $post_id ) {
		if ( self::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}

		$domain = get_post_meta( $post_id, '_client_domain', true );
		$key    = get_post_meta( $post_id, '_license_key', true );
		if ( empty( $key ) ) {
			$key = get_the_title( $post_id );
		}

		if ( ! empty( $domain ) && ! empty( $key ) ) {
			self::send_webhook_revoke( $domain, $key );
		}
	}

	/**
	 * 4. Subdomain & Domain Normalizer.
	 *
	 * Verified: Hyphens in domains (e.g. my-cool-site.com, sub-domain.site.co.uk)
	 * are preserved correctly. Only protocol, path, and port are stripped.
	 * Subdomains are treated as distinct entities from the root domain.
	 */
	public static function normalize_domain( $domain ) {
		$domain = strtolower( trim( (string) $domain ) );
		$domain = preg_replace( '#^https?://#i', '', $domain );
		$domain = preg_replace( '#/.*$#', '', $domain );
		$domain = preg_replace( '#:\d+$#', '', $domain );
		return trim( $domain );
	}

	/**
	 * 5. Send instant revocation webhook to client site.
	 */
	public static function send_webhook_revoke( $domain, $license_key ) {
		$clean_domain = self::normalize_domain( $domain );
		if ( empty( $clean_domain ) ) {
			return;
		}

		$client_endpoint = 'https://' . $clean_domain . '/tp-client/v1/update-license';

		wp_remote_post( $client_endpoint, array(
			'timeout'   => 5,
			'sslverify' => false,
			'headers'   => array( 'Content-Type' => 'application/json' ),
			'body'      => wp_json_encode( array( 'key' => $license_key ) ),
		) );
	}

	/**
	 * 6. Admin Columns.
	 */
	public static function register_columns( $columns ) {
		$new_cols = array();
		$new_cols['cb']             = $columns['cb'];
		$new_cols['title']          = __( 'Title', 'tp-license-server' );
		$new_cols['client_name']    = __( 'Customer Name', 'tp-license-server' );
		$new_cols['client_domain']  = __( 'Website URL', 'tp-license-server' );
		$new_cols['license_key']    = __( 'License Key', 'tp-license-server' );
		$new_cols['license_status'] = __( 'Status', 'tp-license-server' );
		$new_cols['date']           = $columns['date'];
		return $new_cols;
	}

	public static function render_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'client_name':
				$name = get_post_meta( $post_id, '_client_name', true );
				echo ! empty( $name ) ? esc_html( $name ) : '—';
				break;

			case 'client_domain':
				$domain = get_post_meta( $post_id, '_client_domain', true );
				echo ! empty( $domain ) ? esc_html( $domain ) : '—';
				break;

			case 'license_key':
				$key = get_post_meta( $post_id, '_license_key', true );
				if ( empty( $key ) ) {
					$key = get_the_title( $post_id );
				}
				echo ! empty( $key ) ? '<code>' . esc_html( $key ) . '</code>' : '—';
				break;

			case 'license_status':
				$status = get_post_meta( $post_id, '_license_status', true );
				if ( 'active' === $status ) {
					echo '<span style="color: #15803d; font-weight: 600;">Active</span>';
				} else {
					echo '<span style="color: #b91c1c; font-weight: 600;">Inactive</span>';
				}
				break;
		}
	}

	/**
	 * 7. Universal License API Verification Endpoint.
	 * Listens to: /tp-server/v1/check OR ?tp_action=check_license
	 *
	 * Handles both 'check' and 'activate' actions identically — the act of
	 * verifying with a valid key + matching/blank domain auto-binds the domain
	 * on first activation. The client determines 'activate' vs 'check' locally.
	 */
	public static function handle_api_request() {
		$request_uri    = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$is_path_match  = false !== strpos( $request_uri, '/tp-server/v1/check' );
		$is_query_match = ( isset( $_GET['tp_action'] ) && 'check_license' === $_GET['tp_action'] );

		if ( ! $is_path_match && ! $is_query_match ) {
			return;
		}

		$raw_input = file_get_contents( 'php://input' );
		$data      = json_decode( $raw_input, true );
		if ( ! is_array( $data ) ) {
			$data = $_POST;
		}

		$key    = isset( $data['key'] ) ? sanitize_text_field( trim( $data['key'] ) ) : '';
		$url    = isset( $data['url'] ) ? sanitize_text_field( trim( $data['url'] ) ) : '';
		$action = isset( $data['action'] ) ? sanitize_text_field( trim( $data['action'] ) ) : 'check';

		// Health check / ping test
		if ( 'ping' === $action || 'TP-PING-TEST' === $key ) {
			wp_send_json( array(
				'status'  => 'online',
				'message' => 'License server is online and reachable.',
			), 200 );
		}

		if ( empty( $key ) ) {
			wp_send_json( array(
				'status'  => 'invalid',
				'message' => 'License key is missing.',
			), 400 );
		}

		global $wpdb;
		$clean_key = trim( $key );

		// Universal Direct Database Query:
		// Checks post_title AND meta_values across any post_status (except trash)
		$post_id = $wpdb->get_var( $wpdb->prepare( "
			SELECT p.ID
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key IN ('_license_key', 'license_key', '_tp_license_key', 'tp_license_key', 'key', '_key'))
			WHERE p.post_status != 'trash'
			  AND (
			      LOWER(TRIM(p.post_title)) = LOWER(TRIM(%s))
			      OR LOWER(TRIM(pm.meta_value)) = LOWER(TRIM(%s))
			  )
			ORDER BY (LOWER(TRIM(p.post_title)) = LOWER(TRIM(%s))) DESC
			LIMIT 1
		", $clean_key, $clean_key, $clean_key ) );

		if ( ! $post_id ) {
			wp_send_json( array(
				'status'  => 'invalid',
				'message' => 'License key does not exist.',
			), 200 );
		}

		// Retrieve Status
		$status = get_post_meta( $post_id, '_license_status', true );
		if ( empty( $status ) ) $status = get_post_meta( $post_id, 'license_status', true );
		if ( empty( $status ) ) $status = get_post_meta( $post_id, 'status', true );
		if ( empty( $status ) ) $status = 'active';

		if ( 'active' !== $status ) {
			wp_send_json( array(
				'status'  => 'inactive',
				'message' => 'License key is inactive.',
			), 200 );
		}

		// Retrieve Domain
		$bound_domain = get_post_meta( $post_id, '_client_domain', true );
		if ( empty( $bound_domain ) ) $bound_domain = get_post_meta( $post_id, 'client_domain', true );
		if ( empty( $bound_domain ) ) $bound_domain = get_post_meta( $post_id, 'domain', true );

		$normalized_incoming = self::normalize_domain( $url );
		$normalized_bound    = self::normalize_domain( $bound_domain );

		// Auto-bind on first activate if bound domain is blank
		if ( empty( $normalized_bound ) && ! empty( $normalized_incoming ) ) {
			update_post_meta( $post_id, '_client_domain', $normalized_incoming );
			update_post_meta( $post_id, 'client_domain', $normalized_incoming );
			$normalized_bound = $normalized_incoming;
		}

		// Strict Subdomain / Domain Matching (Allowing www.site.com == site.com)
		$compare_incoming = preg_replace( '#^www\.#i', '', $normalized_incoming );
		$compare_bound    = preg_replace( '#^www\.#i', '', $normalized_bound );

		if ( ! empty( $compare_bound ) && ! empty( $compare_incoming ) && $compare_incoming !== $compare_bound ) {
			wp_send_json( array(
				'status'  => 'inactive',
				'message' => sprintf(
					'Domain mismatch! License is registered to %s, but requested from %s. Subdomains require separate licenses.',
					$normalized_bound,
					$normalized_incoming
				),
			), 200 );
		}

		wp_send_json( array(
			'status'  => 'active',
			'message' => 'License is verified and active.',
		), 200 );
	}
}

TP_License_Server::init();
