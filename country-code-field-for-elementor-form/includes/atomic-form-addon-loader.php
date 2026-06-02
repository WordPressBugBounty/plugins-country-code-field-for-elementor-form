<?php

namespace CCFEF\Includes;

use CCFEF\Includes\AtomicForm\Input\Input;
use Elementor\Widgets_Manager;
use Elementor\Plugin as Elementor_Plugin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Atomic_Form_Addon_Loader
{


    private static $instance = null;

    protected $version;

    protected $error_map;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function is_field_enabled($field_key) {
        $enabled_elements = get_option('cfkef_enabled_elements', array());
        return in_array(sanitize_key($field_key), array_map('sanitize_key', $enabled_elements));
    }

    public function __construct()
    {
        if ( ! $this->are_atomic_form_experiments_active() ) {
            return;
        }

        $this->version = CCFEF_VERSION;

        $this->error_map = [
            __("The phone number you entered is not valid. Please check the format and try again.", "country-code-field-for-elementor-form"),
            __("The country code you entered is not recognized. Please ensure it is correct and try again.", "country-code-field-for-elementor-form"),
            __("The phone number you entered is too short. Please enter a complete phone number, including the country code.", "country-code-field-for-elementor-form"),
            __("The phone number you entered is too long. Please ensure it is in the correct format and try again.", "country-code-field-for-elementor-form"),
            __("The phone number you entered is not valid. Please check the format and try again.", "country-code-field-for-elementor-form")
        ];

        add_filter('elementor/widgets/register', [$this, 'register_widgets'], 999);
        add_action('elementor/frontend/before_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('elementor/editor/after_enqueue_scripts', [$this, 'enqueue_editor_scripts']);

    }

    public function enqueue_editor_scripts() {

        if($this->is_field_enabled('country_code')){

            wp_register_script('ccfef-atomic-form-handle-country-editor', CCFEF_PLUGIN_URL . 'assets/atomic-form/js/handle-country-editor.js', array( 'jquery', 'elementor-editor'), $this->version, true);

            wp_localize_script('ccfef-atomic-form-handle-country-editor', 'ccfefCountryEditorData', array(
                'controlDescriptions' => array(
                    __('Default Country (e.g. in, us)', 'extensions-for-elementor-form') => __('Set default country code in tel field, like "in" for India.', 'extensions-for-elementor-form'),
                    __('Only Countries (comma separated)', 'extensions-for-elementor-form') => __('Display only these countries as comma separated values, e.g. ca,in,us,gb.', 'extensions-for-elementor-form'),
                    __('Exclude Countries', 'extensions-for-elementor-form') => __('Exclude countries using comma separated values, e.g. af,pk.', 'extensions-for-elementor-form'),
                    __('Strict Mode', 'extensions-for-elementor-form') => __('Allow only numeric characters and an optional leading plus while typing, and cap input length at the maximum valid number length.', 'extensions-for-elementor-form'),
                ),
            ));

            if (! wp_script_is('ccfef-atomic-form-handle-country-editor', 'enqueued') && ! wp_script_is('ccfef-atomic-form-handle-country-editor', 'done')) {
                wp_enqueue_script('ccfef-atomic-form-handle-country-editor');
            }

            wp_register_style('ccfef-atomic-form-country-editor-style', CCFEF_PLUGIN_URL . 'assets/atomic-form/css/country-editor-style.css', array(), CCFEF_VERSION, 'all');
            if (! wp_style_is('ccfef-atomic-form-country-editor-style', 'enqueued') && ! wp_style_is('ccfef-atomic-form-country-editor-style', 'done')) {
                wp_enqueue_style('ccfef-atomic-form-country-editor-style');
            }
        }
    }

    private function are_atomic_form_experiments_active(): bool {

        if ( ! defined( 'ELEMENTOR_VERSION' ) || ! version_compare( ELEMENTOR_VERSION, CCFEF_MIN_ELEMENTOR_ATOMIC_FORM_VERSION, '>=' ) ) {
            return false;
        }

        $experiments = Elementor_Plugin::$instance->experiments ?? null;
        if ( ! $experiments || ! method_exists( $experiments, 'is_feature_active' ) ) {
            return false;
        }

        return $experiments->is_feature_active( 'e_atomic_elements' )
            && $experiments->is_feature_active( 'e_pro_atomic_form' );
    }

    public function register_widgets(Widgets_Manager $widgets_manager)
    {

        if ( ! $this->are_atomic_form_experiments_active() ) {
            return;
        }

        if ($this->is_field_enabled('country_code')) {

            $widgets_manager->unregister('e-form-input');

            require_once CCFEF_PLUGIN_DIR . 'includes/atomic-form/input/input.php';
            $widgets_manager->register(new Input());
        }
    }

    private function ensure_atomic_form_country_code_assets_registered()
    {
        wp_register_script('frontend-country-handle-js', CCFEF_PLUGIN_URL . 'assets/atomic-form/js/frontend-country-handle.js', array('jquery'), $this->version, true);
        wp_enqueue_script('frontend-country-handle-js');

        wp_register_script('cfl-country-code-library-script', CCFEF_PLUGIN_URL . 'assets/intl-tel-input/js/intlTelInput.js', array(), CCFEF_VERSION, true);
        wp_register_style('cfl-country-code-library-style', CCFEF_PLUGIN_URL . 'assets/intl-tel-input/css/intlTelInput.min.css', array(), CCFEF_VERSION, 'all');
        wp_register_style('cfl-atomic-form-country-code-style', CCFEF_PLUGIN_URL . 'assets/atomic-form/css/atomic-form-country-code-style.min.css', array(), CCFEF_VERSION, 'all');

        wp_localize_script(
            'frontend-country-handle-js',
            'CCFEFCustomData',
            array(
                'pluginDir' => CCFEF_PLUGIN_URL,
                'errorMap'  => $this->error_map,
            )
        );

        if (! wp_script_is('cfl-country-code-library-script', 'enqueued') && ! wp_script_is('cfl-country-code-library-script', 'done')) {
            wp_enqueue_script('cfl-country-code-library-script');
        }

        if (! wp_style_is('cfl-country-code-library-style', 'enqueued') && ! wp_style_is('cfl-country-code-library-style', 'done')) {
            wp_enqueue_style('cfl-country-code-library-style');
        }

        if (! wp_style_is('cfl-atomic-form-country-code-style', 'enqueued') && ! wp_style_is('cfl-atomic-form-country-code-style', 'done')) {
            wp_enqueue_style('cfl-atomic-form-country-code-style');
        }
    }


    public function enqueue_frontend_scripts()
    {

        if ( ! $this->are_atomic_form_experiments_active() ) {
            return;
        }

        if ($this->is_field_enabled('country_code')) {
            $this->ensure_atomic_form_country_code_assets_registered();
        }
    }

    public function get_version()
    {
        return $this->version;
    }
}
