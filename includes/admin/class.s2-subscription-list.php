<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements admin report list features of S2 Subscription List
 *
 * @class   S2_Subscription_List
 * @package S2 Subscription
 * @since   1.0.30
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription_List' ) ) {

	if ( ! class_exists( 'WP_List_Table' ) ) require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

	class S2_Subscription_List extends WP_List_Table {

		/**
		 * @var string
		 */
		private $post_type;
		private $valid_status_to_trash;

		/**
		 * Subscriptions_List_Table constructor.
		 *
		 * @param array $args
		 */
		public function __construct( $args = [] ) {
			parent::__construct(
				[
					'singular' => __( 'subscription', 's2-subscription' ),
					'plural'   => __( 'subscriptions', 's2-subscription' ),
					'ajax'     => false,
				]
			);

			$this->valid_status_to_trash = apply_filters( 's2_valid_status_to_trash', [ 'pending', 'cancelled', 'expired' ] );

			$this->post_type = 's2_subscription';

		}

		/**
		 * @return array
		 */
		function get_columns() {
			$columns = [
				// 'cb'      		=> '<input type="checkbox" />',
				'id'         	=> __( 'ID', 's2-subscription' ),
				'status'  		=> __( 'Status', 's2-subscription' ),
				// 'recurring'  => __( 'Recurring', 's2-subscription' ),
				'order'      	=> __( 'Order', 's2-subscription' ),
				// 'user'       => __( 'User', 's2-subscription' ),
				// 'started'    => __( 'Started', 's2-subscription' ),
				// 'paymentdue' => __( 'Payment Due', 's2-subscription' ),
				// 'enddate'    => __( 'End Date', 's2-subscription' ),
				'expired' 		=> __( 'Expires', 's2-subscription' ),
				// 'renewals'   => __( 'Renewals', 's2-subscription' ),
				'payment_type' 	=> __( 'Payment Type', 's2-subscription' ),
				'payment' 		=> __( 'Payment Method', 's2-subscription' ),
				// 'failed'     => __( 'Failed attempts', 's2-subscription' ),
			];

			return apply_filters( $this->post_type . '_table_list_columns', $columns );
		}

		/**
		 * @return array
		 */
		function get_sortable_columns() {
			$sortable_columns = [
				'id'           => [ 'ID', false ],
				'status'       => [ 'status', false ],
				'order'        => [ 'order', false ],
				'started'      => [ 'started', false ],
				'paymentdue'   => [ 'paymentdue', false ],
				'expired'      => [ 'expired', false ],
				'enddate'      => [ 'enddate', false ],
				'payment_type' => [ 'payment', false ],
				'payment'      => [ 'payment', false ],
				'recurring'    => [ 'recurring', false ],
				'renewals'     => [ 'renewals', false ],
			];

			return $sortable_columns;
		}

		/**
		 * Prepares the list of items for displaying.
		 */
		function prepare_items() {
			global $wpdb, $_wp_column_headers;

			$screen = get_current_screen();

			$columns               = $this->get_columns();
			$hidden                = [];
			$sortable              = $this->get_sortable_columns();
			$this->_column_headers = [ $columns, $hidden, $sortable ];

			$orderby = 'ID';
	        if( ! empty( $_GET['orderby'] ) ) {
	        	$orderby = sanitize_text_field( $_GET['orderby'] );
	    	}

			$order = 'DESC';
	        if( ! empty( $_GET['order'] ) ) {
	        	$order = sanitize_text_field( $_GET['order'] );
	    	}

			// ($_REQUEST); die();
			$where        = '';
			$order_string = '';
			if ( ! empty( $orderby ) & ! empty( $order ) ) {
				$order_string = 'ORDER BY s2_pm.meta_value ' . $order;
				switch ( $orderby ) {
					case 'status':
						$where = " AND ( s2_pm.meta_key = 'status' ) ";
						break;
					case 'started':
						$where = " AND ( s2_pm.meta_key = 'start_date' ) ";
						break;
					case 'renewals':
						$where = " AND ( s2_pm.meta_key = 'rates_payed' ) ";
						break;
					case 'paymentdue':
						$where = " AND ( s2_pm.meta_key = 'payment_due_date' ) ";
						break;
					case 'expired':
						$where = " AND ( s2_pm.meta_key = 'expired_date' ) ";
						break;
					case 'payment':
						$where = " AND ( s2_pm.meta_key = 'payment_method_title' ) ";
						break;
					case 'enddate':
						$where = " AND ( s2_pm.meta_key = 'end_date' ) ";
						break;
					case 'recurring':
						$order_string = 'ORDER BY s2_pm.meta_value+0 ' . $order;
						$where        = " AND ( s2_pm.meta_key = 'line_total' ) ";
						break;
					default:
						$order_string = ' ORDER BY s2_p.' . $orderby . ' ' . $order;
				}
			}

			$join = 'INNER JOIN ' . $wpdb->prefix . 'postmeta as s2_pm ON ( s2_p.ID = s2_pm.post_id ) ';

			// FILTERS
			// by user
			if ( isset( $_REQUEST['_customer_user'] ) && ! empty( $_REQUEST['_customer_user'] ) ) {
				$_customer_user = sanitize_text_field( $_REQUEST['_customer_user'] );

				$join  .= 'INNER JOIN ' . $wpdb->prefix . 'postmeta as s2_pm2 ON ( s2_p.ID = s2_pm2.post_id ) ';
				$where .= " AND ( s2_pm2.meta_key = 'user_id' AND s2_pm2.meta_value = '" . $_customer_user . "' )";
			}

			if ( isset( $_REQUEST['subscription_payment_method'] ) && ! empty( $_REQUEST['subscription_payment_method'] ) && $_REQUEST['subscription_payment_method'] != 'all' ) {
				$subscription_payment_method = sanitize_text_field( $_REQUEST['subscription_payment_method'] );

				$join  .= 'INNER JOIN ' . $wpdb->prefix . 'postmeta as s2_pm4 ON ( s2_p.ID = s2_pm4.post_id ) ';
				$where .= " AND ( s2_pm4.meta_key = 'payment_method' AND s2_pm4.meta_value = '" . $subscription_payment_method . "' )";
			}

			if ( isset( $_REQUEST['status'] ) && ! empty( $_REQUEST['status'] ) && $_REQUEST['status'] != 'all' && $_REQUEST['status'] != 'trash' ) {
				$status = sanitize_text_field( $_REQUEST['status'] );

				$join  .= 'INNER JOIN ' . $wpdb->prefix . 'postmeta as s2_pm3 ON ( s2_p.ID = s2_pm3.post_id ) ';
				$where .= " AND ( s2_pm3.meta_key = 'status' AND s2_pm3.meta_value = '" . $status . "' )";
			}

			if ( isset( $_REQUEST['status'] ) && $_REQUEST['status'] == 'trash' ) {
				$where .= " AND s2_p.post_status = 'trash' ";
			} else {
				$where .= " AND s2_p.post_status = 'publish' ";
			}

			if ( isset( $_REQUEST['m'] ) ) {
				// The "m" parameter is meant for months but accepts datetimes of varying specificity
				$request_m = sanitize_text_field( $_REQUEST['m'] );
				if ( $request_m ) {
					$where .= ' AND YEAR(s2_p.post_date)=' . substr( $request_m, 0, 4 );
					if ( strlen( $request_m ) > 5 ) {
						$where .= ' AND MONTH(s2_p.post_date)=' . substr( $request_m, 4, 2 );
					}
					if ( strlen( $request_m ) > 7 ) {
						$where .= ' AND DAYOFMONTH(s2_p.post_date)=' . substr( $request_m, 6, 2 );
					}
					if ( strlen( $request_m ) > 9 ) {
						$where .= ' AND HOUR(s2_p.post_date)=' . substr( $request_m, 8, 2 );
					}
					if ( strlen( $request_m ) > 11 ) {
						$where .= ' AND MINUTE(s2_p.post_date)=' . substr( $request_m, 10, 2 );
					}
					if ( strlen( $request_m ) > 13 ) {
						$where .= ' AND SECOND(s2_p.post_date)=' . substr( $request_m, 12, 2 );
					}
				}
			}

			$join  = apply_filters( 's2_subscription_list_table_join', $join );
			$where = apply_filters( 's2_subscription_list_table_where', $where );

			// Check if the request came from search form
			$query_search = '';
			if( ! empty( $_REQUEST['s'] ) ) {
	        	$query_search = sanitize_text_field( $_REQUEST['s'] );
	    	}
			if ( $query_search ) {
				$search = " AND ( s2_p.ID LIKE '%$query_search%' OR ( s2_pm.meta_key='product_name' AND  s2_pm.meta_value LIKE '%$query_search%' ) ) ";
			} else {
				$search = '';
			}
			$where .= apply_filters( 's2_subscription_list_table_search', $search, $_REQUEST );

			$query      = "SELECT s2_p.* FROM $wpdb->posts as s2_p  $join
	                WHERE 1=1 $where
	                AND s2_p.post_type = 's2_subscription'
	                GROUP BY s2_p.ID $order_string";
			$totalitems = $wpdb->query( $query );

			$perpage = 20;
			// Which page is this?
			$paged = '';
			if( ! empty( $_GET['paged'] ) ) {
	        	$paged = sanitize_text_field( $_GET['paged'] );
	    	}
			// Page Number
			if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
				$paged = 1;
			}
			// How many pages do we have in total?
			$totalpages = ceil( $totalitems / $perpage );
			// adjust the query to take pagination into account
			if ( ! empty( $paged ) && ! empty( $perpage ) ) {
				$offset = ( $paged - 1 ) * $perpage;
				$query .= ' LIMIT ' . (int) $offset . ',' . (int) $perpage;
			}

			// -- Register the pagination --
			$this->set_pagination_args(
				[
					'total_items' => $totalitems,
					'total_pages' => $totalpages,
					'per_page'    => $perpage,
				]
			);
			// The pagination links are automatically built according to those parameters

			$_wp_column_headers[ $screen->id ] = $columns;
			$this->items                       = $wpdb->get_results( $query );

		}

		/**
		 * Handles the default column output.
		 *
		 * @param WP_Post $post        The current WP_Post object.
		 * @param string  $column_name The current column name.
		 */
		public function column_default( $post, $column_name ) {
			$id = $post->ID;
			$column_value = '';

	    	switch ( $column_name ) {
	    		case 'status':
					$column_value = esc_html( get_post_meta( $id, 'status', true ) );
					break;

				case 'order':
					$column_value = get_post_meta( $id, 'order_id', true );
					$column_value = "<a href='" . esc_url( get_edit_post_link( $column_value ) ) . "'>". esc_html( $column_value ) . "</a>";
					break;

				case 'payment':
					$column_value = esc_html( get_post_meta( $id, 'payment_method_title', true ) );
					break;

				case 'expired':
					$subscription_status = get_post_meta( $id, 'status', true );
					if( $subscription_status != 'paused' ) {

						$column_value = get_post_meta( $id, 'expired_date', true );
						if ( ! empty( $column_value ) ) {
							$column_value = esc_html( date_i18n( wc_date_format(), $column_value ) );
						}

					}
					break;

				case 'payment_type':
					$column_value 	= get_post_meta( $id, 'payment_type', true );
					$options 		= s2_get_payment_type_options();
					$column_value 	= esc_html( $options[ $column_value ] );
					break;

				default:
					$column_value = esc_html( get_post_meta( $id, $column_name, true ) );
					break;
			}

			echo ! empty( $column_value ) ? $column_value : '-N/A-';
		}

		/**
		 * Handles the checkbox column output.
		 *
		 * @param WP_Post $post The current WP_Post object.
		 */
		public function column_cb( $post ) {
			if ( in_array( get_post_meta( $item->ID, 'status', true ), $this->valid_status_to_trash ) ) {
				return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item->ID );
			}
		}

		/**
		 * Handles the ID column output.
		 *
		 * @param $item
		 *
		 * @return string
		 */
		function column_id( $item ) {

			$product_name = get_post_meta( $item->ID, 'product_name', true );
			$quantity     = get_post_meta( $item->ID, 'quantity', true );
			$status       = get_post_meta( $item->ID, 'status', true );

			$qty = ( $quantity > 1 ) ? ' x ' . $quantity : '';

			$actions['edit'] = '<a href="' . admin_url( 'post.php?post=' . $item->ID . '&action=edit' ) . '">' . __( 'Edit', 's2-subscription' ) . '</a>';

			$post_type_object = get_post_type_object( $this->post_type );

			/*if ( 'trash' == $item->post_status ) {
				$actions['untrash'] = '<a title="' . esc_attr__( 'Restore this item from the Trash', 's2-subscription' ) . '" href="' . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $item->ID ) ), 'untrash-post_' . $item->ID ) . '">' . __( 'Restore', 's2-subscription' ) . '</a>';
			} elseif ( EMPTY_TRASH_DAYS && in_array( $status, $this->valid_status_to_trash ) ) {
				$actions['trash'] = '<a title="' . esc_attr( __( 'Move this item to the Trash', 's2-subscription' ) ) . '" href="' . get_delete_post_link( $item->ID ) . '">' . __( 'Trash', 's2-subscription' ) . '</a>';
			}
			if ( 'trash' == $item->post_status || ! EMPTY_TRASH_DAYS ) {
				$actions['delete'] = '<a title="' . esc_attr( __( 'Delete this item permanently', 's2-subscription' ) ) . '" href="' . get_delete_post_link( $item->ID, '', true ) . '">' . __( 'Delete Permanently', 's2-subscription' ) . '</a>';
			}*/

			return sprintf( '<strong><a href="' . admin_url( 'post.php?post=' . $item->ID . '&action=edit' ) . '">#%1$s</a></strong> - %2$s %3$s', $item->ID, $product_name . $qty, $this->row_actions( $actions ) );
		}

		/**
		 * Extra controls to be displayed between bulk actions and pagination, which
		 * includes our Filters: Customers, Products, Availability Dates
		 *
		 * @see   WP_List_Table::extra_tablenav();
		 *
		 * @param string $which the placement, one of 'top' or 'bottom'
		 */
		public function extra_tablenav( $which ) {
			if ( 'top' == $which ) {

				?>
				
				<div class="alignleft actions">
					<?php $this->months_dropdown( $this->post_type ); ?>
				</div>

				<?php
				$this->restrict_by_payment_method();
				$this->restrict_by_product();
				$this->restrict_by_customer();
				?>

				<div class="alignleft actions">
					<?php submit_button( __( 'Filter', 's2-subscription' ), 'button', false, false, [ 'id' => 'post-query-submit' ] ); ?>
				</div>

				<?php

			}
		
		}

		/**
		 * Displays the dropdown for the product filter
		 * @return string the html dropdown element
		 */
		public function restrict_by_product() {
			$product_id = '';
			$product_string = '';

			if ( ! empty( $_GET['subscription_product'] ) ) {
				$product_id     = absint( sanitize_text_field( $_GET['subscription_product'] ) );
				$product_string = wc_get_product( $product_id )->get_formatted_name();
			}
			?>

			<div class="alignleft actions">
				<select style="width: 240px;" class="wc-product-search" name="subscription_product" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce-subscriptions' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-allow_clear="true">
					<option value="<?php echo esc_attr( $product_id ); ?>" selected="selected"><?php echo wp_kses_post( $product_string ); ?></option>
				</select>
			</div>

			<?php
		}

	    /**
		 * Displays the dropdown for the payment method filter.
		 */
		public function restrict_by_payment_method() {
			$selected_gateway_id = '';
			if( ! empty( $_GET['subscription_payment_method'] ) ) {
	        	$selected_gateway_id = sanitize_text_field( $_GET['subscription_payment_method'] );
	    	}
		?>

			<div class="alignleft actions">
				<select name="subscription_payment_method" id="subscription_payment_method">
					<option value=""><?php esc_html_e( 'Any Payment Method', 'woocommerce-subscriptions' ) ?></option>

				<?php
					foreach ( WC()->payment_gateways->get_available_payment_gateways() as $gateway_id => $gateway ) {
						echo '<option value="' . esc_attr( $gateway_id ) . '"' . ( $selected_gateway_id == $gateway_id  ? 'selected' : '' ) . '>' . esc_html( $gateway->title ) . '</option>';
					}
				?>

				</select>
			</div>

		<?php
		}

		/**
		 * Renders the dropdown for the customer filter.
		 */
		public function restrict_by_customer() {
			$user_string = '';
			$user_id     = '';

			if ( ! empty( $_GET['_customer_user'] ) ) {
				$user_id = absint( sanitize_text_field( $_GET['_customer_user'] ) );
				$user    = get_user_by( 'id', $user_id );

				$user_string = sprintf(
					/* translators: 1: user display name 2: user ID 3: user email */
					esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'woocommerce-subscriptions' ),
					$user->display_name,
					absint( $user->ID ),
					$user->user_email
				);
			}
			?>

			<div class="alignleft actions">
				<select style="width: 240px;" class="wc-customer-search" name="_customer_user" data-placeholder="<?php esc_attr_e( 'Search for a customer&hellip;', 'woocommerce-subscriptions' ); ?>" data-allow_clear="true">
					<option value="<?php echo esc_attr( $user_id ); ?>" selected="selected"><?php echo wp_kses_post( $user_string ); ?></option>
				</select>
			</div>

			<?php
		}

		/**
		 * Get an associative array ( id => link ) with the list
		 * of views available on this table.
		 *
		 * @access protected
		 *
		 * @return array
		 */
		protected function get_views() {
			global $wpdb;

			$links  = [];
			$status = s2_get_status();

			// count all subscriptions
			$q = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) as counter FROM $wpdb->posts as s2_p WHERE s2_p.post_type = 's2_subscription' AND s2_p.post_type = '%s' AND s2_p.post_status= 'publish' ", $this->post_type ) );

			if ( $q ) {
				$links['all'] = '<a class="' . ( empty( $_GET['status'] ) || ( ! empty( $_GET['status'] ) && 'all' == $_GET['status'] ) ? 'current' : '' ) . '" href="' . add_query_arg( 'status', 'all' ) . '">' . __( 'All', 's2-subscription' ) . ' (' . $q . ')</a>';
			}

			foreach ( $status as $key => $value ) {
				$q = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT count(*) as counter FROM $wpdb->posts as s2_p INNER JOIN " . $wpdb->prefix . "postmeta as s2_pm ON ( s2_p.ID = s2_pm.post_id )
	                  WHERE s2_p.post_type = 's2_subscription' AND s2_p.post_status= 'publish' AND s2_pm.meta_key = 'status' AND s2_pm.meta_value = '%s'",
						$key
					)
				);

				if ( $q ) {
					$links[ $key ] = '<a class="' . ( ! empty( $_GET['status'] ) && $key == $_GET['status'] ? 'current' : '' ) . '" href="' . add_query_arg( 'status', $key ) . '">' . ucfirst( $value ) . ' (' . $q . ')</a>';
				}
			}

			// check if there are subscription in trash
			$q = $wpdb->get_var( $wpdb->prepare( "SELECT count(*) as counter FROM $wpdb->posts as s2_p WHERE s2_p.post_type = 's2_subscription' AND s2_p.post_type = '%s' AND s2_p.post_status= 'trash' ", $this->post_type ) );
			if ( $q ) {
				$links['trash'] = '<a href="' . add_query_arg( 'status', 'trash' ) . '">' . __( 'Trash', 's2-subscription' ) . ' (' . $q . ')</a>';
			}

			return $links;
		}

		/**
		 * Display the search box.
		 *
		 * @access public
		 *
		 * @param string $text     The search button text
		 * @param string $input_id The search input id
		 */
		public function search_box( $text, $input_id ) {

			$input_id = $input_id . '-search-input';
			$input_id = esc_attr( $input_id );

			if ( ! empty( $_REQUEST['orderby'] ) ) {
				echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
			}
			if ( ! empty( $_REQUEST['order'] ) ) {
				echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
			}

			?>
			<p class="search-box">
				<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
				<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" placeholder="<?php _e( 'Search', 's2-subscription' ); ?>" />
				<?php submit_button( $text, 'button', '', false, [ 'id' => 'search-submit' ] ); ?>
			</p>
			<?php
		}

	}

}
