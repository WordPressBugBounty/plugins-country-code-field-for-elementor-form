<?php
/**
 * Plugin Name: Country Code For Elementor Form Telephone Field
 * Plugin URI:
 * Description:This plugin simplifies mobile number entry for users by guiding them to select their country code while entering their mobile number, ensuring accurate and properly formatted data submissions.
 * Version: 1.7.0
 * Author:  Cool Plugins
 * Author URI: https://coolplugins.net/?utm_source=ccfef_plugin&utm_medium=inside&utm_campaign=author_page&utm_content=plugins_list
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:country-code-field-for-elementor-form
 * Elementor tested up to: 4.1.1
 * Elementor Pro tested up to: 4.1.0
 *
 * @package ccfef
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
if ( ! defined( 'CCFEF_VERSION' ) ) {
	define( 'CCFEF_VERSION', '1.7.0' );
}
/*** Defined constant for later use */
define( 'CCFEF_FILE', __FILE__ );
define( 'CCFEF_PLUGIN_BASE', plugin_basename( CCFEF_FILE ) );
define( 'CCFEF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CCFEF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define('CCFEF_FEEDBACK_URL', 'https://feedback.coolplugins.net/');
define('CCFEF_MIN_ELEMENTOR_ATOMIC_FORM_VERSION', '4.0');




if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! class_exists( 'Country_Code_Field_For_Elementor_Form' ) ) {
	/**
	 * Main Class start here
	 */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class Country_Code_Field_For_Elementor_Form {
		/**
		 * Plugin instance.
		 *
		 * @var Country_Code_Field_For_Elementor_Form
		 *
		 * @access private
		 * @var null
		 */
		private static $instance = null;

		
		/**
		 * Get plugin instance.
		 *
		 * @return Country_Code_Field_For_Elementor_Form
		 * @static
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor function check compatible plugin before activating it
		 */
		private function __construct() {
			register_activation_hook( CCFEF_FILE, array( $this, 'ccfef_activate' ) );
			register_deactivation_hook( CCFEF_FILE, array( $this, 'ccfef_deactivate' ) );
			add_action( 'activated_plugin', array( $this, 'ccfef_plugin_redirection' ) );
			add_action( 'plugins_loaded', array( $this, 'ccfef_plugins_loaded' ) );
			add_action( 'init', array( $this, 'is_compatible' ) );
			add_action( 'init', array( $this, 'ccfef_load_add_on' ) );
			add_action('init', array($this, 'formdb_marketing_hello_plus'));
			add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
			add_action( 'elementor_pro/forms/actions/register', array($this,'ccfef_register_new_form_actions') );
        	
			$this->includes();
		}

		private function includes() {

			require_once CCFEF_PLUGIN_DIR . 'admin/feedback/cron/ccfef-class-cron.php';
		
		}

		public function formdb_marketing_hello_plus(){

			if ( !is_plugin_active( 'sb-elementor-contact-form-db/sb_elementor_contact_form_db.php' ) && !defined("formdb_hello_plus_marketing_editor")){
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
				define("formdb_hello_plus_marketing_editor", true);

				require_once CCFEF_PLUGIN_DIR . 'includes/helloplus_loader.php';
				new HelloPlus_Widget_Loader();
			}
			
		}

		public function ccfef_register_new_form_actions($form_actions_registrar){

			if($this->is_field_enabled('country_code')){



				if ( !is_plugin_active( 'sb-elementor-contact-form-db/sb_elementor_contact_form_db.php' ) && !defined("formdb_elementor_marketing_editor")){

					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
					define("formdb_elementor_marketing_editor", true);

					include_once( __DIR__ .  '/includes/class-form-to-sheet.php' );
					$form_actions_registrar->register( new \Sheet_Action() );

				}

			}
		}

		private function is_field_enabled($field_key) {
			$enabled_elements = get_option('cfkef_enabled_elements', array());
			return in_array(sanitize_key($field_key), array_map('sanitize_key', $enabled_elements));
		}

		/**
		 * Load plugin text domain for translation
		 */
		public function ccfef_plugins_loaded() {
			if ( ! is_plugin_active( 'elementor-pro/elementor-pro.php' ) && ! is_plugin_active( 'pro-elements/pro-elements.php' ) && !is_plugin_active( 'hello-plus/hello-plus.php' )) {
				return false;
			}

			if ( is_admin() ) {
				require_once CCFEF_PLUGIN_DIR . 'admin/feedback/ccfef-users-feedback.php';
			}


			require_once CCFEF_PLUGIN_DIR . '/includes/class-country-code-elementor-page.php';
			new Country_Code_Elementor_Page();


			
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'ccfef_plugin_get_pro_link' ) );
			
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'ccfef_plugin_setting_link' ) );

			if(!class_exists('CPFM_Feedback_Notice')){
				require_once CCFEF_PLUGIN_DIR . 'admin/feedback/cpfm-common-notice.php';
			}

			if ( did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Plugin' ) ) {

				require_once CCFEF_PLUGIN_DIR . '/admin/marketing/ccfef-marketing-common.php';
			}

			add_action('cpfm_register_notice', function () {
            
				if (!class_exists('\CPFM_Feedback_Notice') || !current_user_can('manage_options')) {
					return;
				}

            $notice = [

                'title' => __('Elementor Form Addons by Cool Plugins', 'country-code-field-for-elementor-form'),
                'message' => __('Help us make this plugin more compatible with your site by sharing non-sensitive site data.', 'country-code-field-for-elementor-form'),
                'pages' => ['cool-formkit','cfkef-entries','cool-formkit&tab=recaptcha-settings'],
                'always_show_on' => ['cool-formkit','cfkef-entries','cool-formkit&tab=recaptcha-settings'], // This enables auto-show
                'plugin_name'=>'ccfef'
            ];

            \CPFM_Feedback_Notice::cpfm_register_notice('cool_forms', $notice);

                if (!isset($GLOBALS['cool_plugins_feedback'])) {
					// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                    $GLOBALS['cool_plugins_feedback'] = [];
                }
                // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
                $GLOBALS['cool_plugins_feedback']['cool_forms'][] = $notice;
           
            });
        
			add_action('cpfm_after_opt_in_ccfef', function($category) {

					
					if ($category === 'cool_forms') {

						require_once CCFEF_PLUGIN_DIR . 'admin/feedback/cron/ccfef-class-cron.php';

						ccfef_cronjob::ccfef_send_data();
						update_option( 'cfef_usage_share_data','on' );   
					} 
			});

		}

		/**
		 * Method for creating action links for the plugin.
		 */
		function ccfef_plugin_setting_link( $links ) {
			$settings_link = '<a href="' . admin_url( 'admin.php?page=cool-formkit' ) . '">Settings</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}


		function ccfef_plugin_get_pro_link( $links ) {
			$get_pro_link = '<a href="https://coolformkit.com/pricing/?utm_source=ccfef_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=plugins_list" style="font-weight: bold; color: green;" target="_blank">Get Pro</a>';
			array_unshift( $links, $get_pro_link );
			return $links;
		}

		public function ccfef_plugin_redirection($plugin){
			if ( ! is_plugin_active( 'elementor-pro/elementor-pro.php' ) ) {
				return false;
			}
			if ( is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' ) ) {
				return false;
			}
			if ( $plugin == plugin_basename( __FILE__ ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=cool-formkit' ) );
				exit;

			}	
		}
		/**
		 * Check if Elementor Pro is installed or activated
		 */
		public function is_compatible() {
			add_action( 'admin_init', array( $this, 'is_elementor_pro_exist' ) );
		}

		/**
		 * Include country field add-on register file
		 */
		public function ccfef_load_add_on() {

			if($this->is_field_enabled('country_code')){

				include CCFEF_PLUGIN_DIR . 'includes/register/class-ccfef-country-code-register.php';
				CFEFP_COUNTRY_FIELD_REGISTER::get_instance();

				if ( is_plugin_active( 'elementor-pro/elementor-pro.php' ) || is_plugin_active( 'pro-elements/pro-elements.php' ) ) {
					// After `elementor/init`, core services (e.g. experiments) are initialized; on `elementor/loaded` they are often still null.
					if ( did_action( 'elementor/init' ) ) {
						$this->load_atomic_form_addon();
					} else {
						add_action( 'elementor/init', array( $this, 'load_atomic_form_addon' ), 20 );
					}
				}

			}

		}

		public function load_atomic_form_addon() {
			if ( ! is_plugin_active( 'elementor-pro/elementor-pro.php' ) && ! is_plugin_active( 'pro-elements/pro-elements.php' ) ) {
				return;
			}
	
			if ( ! did_action( 'elementor/init' ) || ! class_exists( '\Elementor\Plugin' ) ) {
				return;
			}

			if ( ! self::is_elementor_atomic_form_supported() ) {
				if ( is_admin()) {
					add_action( 'admin_notices', array( $this, 'admin_notice_elementor_atomic_form_version' ) );
				}
				return;
			}
	
			$elementor = \Elementor\Plugin::$instance;
			if ( ! $elementor ) {
				return;
			}
	
			$experiments = isset( $elementor->experiments ) ? $elementor->experiments : null;
			if ( ! self::are_elementor_atomic_form_experiments_active( $experiments ) ) {
				return;
			}

			require_once CCFEF_PLUGIN_DIR . 'includes/atomic-form-addon-loader.php';
			\CCFEF\Includes\Atomic_Form_Addon_Loader::get_instance();
	
		}

		/**
		 * Atomic Form extensions require Elementor 4.0+ (matches Elementor Pro atomic-form module).
		 */
		public static function is_elementor_atomic_form_supported(): bool {
			return defined( 'ELEMENTOR_VERSION' )
				&& version_compare( ELEMENTOR_VERSION, CCFEF_MIN_ELEMENTOR_ATOMIC_FORM_VERSION, '>=' );
		}
	
		/**
			 * @param mixed $experiments \Elementor\Core\Experiments\Manager|null.
			 */
			private static function are_elementor_atomic_form_experiments_active( $experiments ): bool {

				if ( ! self::is_elementor_atomic_form_supported() ) {
					return false;
				}

				if ( ! $experiments || ! is_object( $experiments ) || ! method_exists( $experiments, 'is_feature_active' ) ) {
					return false;
				}
	
				return $experiments->is_feature_active( 'e_atomic_elements' )
					&& $experiments->is_feature_active( 'e_pro_atomic_form' );
			}


			public function admin_notice_elementor_atomic_form_version() {
				if ( ! current_user_can( 'update_plugins' ) ) {
					return;
				}
		
				$file_path = 'elementor/elementor.php';
				$upgrade_link = wp_nonce_url(
					self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file_path,
					'upgrade-plugin_' . $file_path
				);
		
				printf(
					'<div class="notice notice-warning is-dismissible"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
					esc_html__( 'Country Code For Elementor Form Telephone Field:', 'country-code-field-for-elementor-form' ),
					esc_html__(
						'Atomic Form extensions require Elementor 4.0 or newer. Update Elementor to use this feature.',
						'country-code-field-for-elementor-form'
					),
					esc_url( $upgrade_link ),
					esc_html__( 'Update Elementor', 'country-code-field-for-elementor-form' )
				);
			}

		/**
		 * Function used to deactivate the plugin if Elementor Pro does not exist
		 */
		public function is_elementor_pro_exist() {
			if (
				is_plugin_active('pro-elements/pro-elements.php') || 
				is_plugin_active('elementor-pro/elementor-pro.php') ||
				is_plugin_active('hello-plus/hello-plus.php')
			) {
				return true; // At least one plugin is active, the country code plugin can run.
			}
		
			// If neither plugin is active, show an admin notice.
			add_action('admin_notices', array($this, 'admin_notice_missing_main_plugin'));
			return false;
		}

		/**
		 * Show notice to enable Elementor Pro
		 */
		public function admin_notice_missing_main_plugin() {
			$message = sprintf(
				// translators: %1$s replace with Country Code For Elementor Form Telephone Field & %2$s replace with Elementor Pro.
				esc_html__(
					'%1$s requires %2$s to be installed and activated.',
					'country-code-field-for-elementor-form'
				),
				esc_html__( 'Country Code For Elementor Form Telephone Field', 'country-code-field-for-elementor-form' ),
				esc_html__( 'Elementor Pro', 'country-code-field-for-elementor-form' ),
			);
			printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', esc_html( $message ) );
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}

		/**
		 * Add options for plugin details
		 */
		public static function ccfef_activate() {
			update_option( 'ccfef-v', CCFEF_VERSION );
			update_option( 'ccfef-type', 'free' );
			update_option( 'ccfef-installDate', gmdate( 'Y-m-d h:i:s' ) );

			if (!get_option( 'country_code_initial_version' ) ) {
                add_option( 'country_code_initial_version', CCFEF_VERSION );
            }

			if(!get_option( 'ccfef-install-date' ) ) {
				add_option( 'ccfef-install-date', gmdate('Y-m-d h:i:s') );
        	}


			$settings       = get_option('cfef_usage_share_data');

			
			if (!empty($settings) || $settings === 'on'){
				
				static::ccfef_cron_job_init();
			}
		}

		public static function ccfef_cron_job_init()
		{
			if (!wp_next_scheduled('ccfef_extra_data_update')) {
				wp_schedule_event(time(), 'every_30_days', 'ccfef_extra_data_update');
			}
		}


		/**
		 * Function run on plugin deactivate
		 */
		public static function ccfef_deactivate() {

			if (wp_next_scheduled('ccfef_extra_data_update')) {
            	wp_clear_scheduled_hook('ccfef_extra_data_update');
        	}
		}


		public function plugin_row_meta( $plugin_meta, $plugin_file ) {
			if ( CCFEF_PLUGIN_BASE === $plugin_file ) {
				$row_meta = [
					'docs' => '<a href="https://coolplugins.net/add-country-code-telephone-elementor-form/?utm_source=ccfef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=plugins_list" aria-label="' . esc_attr( esc_html__( 'Country Code Documentation', 'country-code-field-for-elementor-form' ) ) . '" target="_blank">' . esc_html__( 'Docs & FAQs', 'country-code-field-for-elementor-form' ) . '</a>'
				];

				$plugin_meta = array_merge( $plugin_meta, $row_meta );
			}

			return $plugin_meta;

		}
	}
}
$ccfef_obj = Country_Code_Field_For_Elementor_Form::get_instance();
