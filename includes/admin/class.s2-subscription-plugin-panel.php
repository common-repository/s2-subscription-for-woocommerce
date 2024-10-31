<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements admin plugin panel features of S2 Subscription
 *
 * @class   S2_Subscription_Plugin_Panel
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription_Plugin_Panel' ) ) {

	class S2_Subscription_Plugin_Panel {

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Subscription_Plugin_Panel
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @since  1.0.0
		 * @return \S2_Subscription_Plugin_Panel
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @param array $args
		 *
		 * @since  1.0.0
		 */
		public function __construct() {

			$this->create_menu_items();

			// Add action links.
			add_filter( 'plugin_action_links_' . plugin_basename( S2_WS_DIR . '/' . basename( S2_WS_FILE ) ), [ $this, 'action_links' ] );

		}

		/**
		 * Create Menu Items
		 *
		 * Print admin menu items
		 *
		 * @since  1.0.0
		 */
		private function create_menu_items() {

			// Add a panel or menu pages
			add_action( 'admin_menu', [ $this, 'register_panel' ], 5 );

		}

		/**
		 * Add a panel or menu pages
		 *
		 * @since  1.0.0
		 */
		public function register_panel() {

			global $submenu, $pagenow;

			$args = [
				'page_title'    => 'Home',
				'menu_title'    => 'S2 Plugins',
				'capability'    => 'manage_options',
				'menu_slug'     => 's2-admin',
				'function_name' => '',
				'icon_url'      => 'dashicons-heart',
				'position'      => null,
			];
			if( empty( $submenu['s2-admin'] ) ) $this->add_menu_page( $args );

			$args = [
				'parent_slug'   => 's2-admin',
				'page_title'    => 'Subscription',
				'menu_title'    => 'Subscription',
				'capability'    => 'manage_options',
				'menu_slug'     => 's2-subscription',
				'function_name' => 'show_subscription',
				'position'      => null,
			];
			$this->add_submenu_page( $args );

			unset( $submenu['s2-admin'][0] );

			// set the parent_file, submenu_file
	        if ( $pagenow == 'post.php' && ! empty( $_GET['post'] ) ) {

	        	$post = get_post( sanitize_text_field( $_GET['post'] ) );
	        	if( ! empty( $post ) && $post->post_type == 's2_subscription' ) {
	            	add_filter( 'parent_file', [ $this, 'change_parent_file' ] );
	            	add_filter( 'submenu_file', [ $this, 'change_submenu_file' ], 10, 2 );
       			}

       		}

		}

		/**
		 * Change parent file to display plugin menu active
		 */
		public function change_parent_file( $parent_file ) {
			$parent_file = 's2-admin';

			return $parent_file;
		}

		/**
		 * Change submenu file to display plugin menu active
		 */
		public function change_submenu_file( $parent_file, $submenu_file ) {
			$submenu_file = 's2-subscription';

			return $submenu_file;
		}

		/**
		 * Add Menu page link
		 *
		 * @param array $args
		 *
		 * @since  1.0.0
		 */
		public function add_menu_page( $args ) {

			add_menu_page( $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], [ $this, $args['function_name'] ], $args['icon_url'], $args['position'] );

		}

		/**
		 * Add Menu page link
		 *
		 * @param array $args
		 *
		 * @since  1.0.0
		 */
		public function add_submenu_page( $args ) {

			add_submenu_page( $args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], [ $this, $args['function_name'] ], $args['position'] );

		}

		/**
		 * Show subscription admin page
		 *
		 * @since  1.0.0
		 */
		public function show_subscription() {

			$args = [
				'page'			=> 's2-subscription',
				'admin_tabs' 	=> [
										'subscriptions' => __( 'Subscriptions', 's2-subscription' ),
										'settings' 		=> __( 'Settings', 's2-subscription' ),
									],
			];
			$this->print_tabs_nav( $args );

			$current_tab = 'subscriptions';
            if( ! empty( $_GET['tab'] ) ) {
            	$current_tab = sanitize_text_field( $_GET['tab'] );
        	}

			if( $current_tab == 'subscriptions' ) $this->show_subscription_list_page();
			elseif( $current_tab == 'settings' ) $this->show_subscription_setting_page();

		}

		/**
         * Print the tabs navigation
         *
         * @param array $args
         *
         * @since  1.0.0
         */
        public function print_tabs_nav( $args = [] ) {

            /**
             * @var string $admin_tabs
             * @var string $page
             */
            extract( $args );

            $current_tab = 'subscriptions';
            if( ! empty( $_GET['tab'] ) ) {
            	$current_tab = sanitize_text_field( $_GET['tab'] );
        	}

            $tabs = '';

            foreach ( $admin_tabs as $tab => $tab_value ) {

                $active_class  = ( $current_tab == $tab ) ? ' nav-tab-active' : '';

                $url = $this->get_nav_url( $tab, $page );

                $tabs .= '<a class="nav-tab' . esc_attr( $active_class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $tab_value ) . '</a>';

            }

            echo sprintf( '<h2 class="nav-tab-wrapper">%s</h2>', $tabs );

        }

        /**
         * Get tab nav url
         *
         * @param string $tab
         *
         * @since  1.0.0
         */
        public function get_nav_url( $tab, $page ) {

            $url = "?page={$page}&tab={$tab}";
            $url = admin_url( "admin.php{$url}" );

            return $url;

        }

        /**
		 * Action Links
		 *
		 * add the action links to plugin admin page
		 *
		 * @param $links | links plugin array
		 *
		 * @since  1.0.0
		 *
		 * @return mixed | array
		 * @use    plugin_action_links_{$plugin_file_name}
		 */
		public function action_links( $links ) {

			$links = is_array( $links ) ? $links : [];

			$links[] = sprintf( '<a href="%s">%s</a>', admin_url( "admin.php?page=s2-subscription&tab=settings" ), _x( 'Settings', 'Action links',  's2-subscription' ) );

			return $links;

		}

		/**
		 * Show subscription list admin page
		 *
		 * @since  1.0.0
		 */
		public function show_subscription_list_page() {

			$subscription_list = new S2_Subscription_List();

			wc_get_template(
				'admin/list-page.php',
				[ 'subscription_list' => $subscription_list ],
				'',
				S2_WS_TEMPLATE_PATH . '/'
			);

		}

		/**
		 * Show subscription setting admin page
		 *
		 * @since  1.0.0
		 */
		public function show_subscription_setting_page() {

			$plugin_setting = S2_Subscription_Plugin_Setting();

			wc_get_template(
				'admin/settings-page.php',
				[ 'plugin_setting' => $plugin_setting ],
				'',
				S2_WS_TEMPLATE_PATH . '/'
			);

		}

	}

}

/**
 * Unique access to instance of S2_Subscription_Plugin_Panel class
 */
S2_Subscription_Plugin_Panel::get_instance();
