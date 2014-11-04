<?php

namespace GoNear;

class App {
    public static $instance;

    public $version = '0.0.2';
    public $db_version;
    public $app_path;
    public $app_url;

    public static function instance() {
        if ( is_null( self::$instance ) )
            return self::$instance = new self;
        else
            return self::$instance;
    }

    private function __construct() {
        // Auto-load classes
        if ( function_exists( "__autoload" ) )
            spl_autoload_register( "__autoload" );

        spl_autoload_register( array( $this, 'autoload' ) );

        add_action( 'setup_theme', array( $this, 'load' ) );
    }

    public function autoload( $class ) {
        $files = array();

        $path = $this->app_path( 'classes' );

        if ( class_exists( $class ) ) {
            return;
        }

        // Lowercase the classname and grab the "unqualified" name if the class
        // is inside a namespace
        if ( strpos( $class, '\\' ) !== false )
            $class = strtolower( substr( $class, strrpos( $class, '\\' ) + 1 ) );
        else
            $class = strtolower( $class );

        $formatted_class = str_replace( '_', '-', $class );

        // Abstracts
        $file = $path . '/abstracts/' . 'abstract-' . $formatted_class . '.php';
        if ( file_exists( $file ) && is_readable( $file ) ) {
            $files[$class] = $file;
            include_once( $file );
            return;
        }

        // Classes
        $file = $path . '/class-' . $formatted_class . '.php';
        if ( file_exists( $file ) && is_readable( $file ) ) {
            $files[$class] = $file;
            include_once( $file );
            return;
        }
    }

    public function app_location( $sector = '', $base = false ) {
        if ( !$base ) {
            $base = $this->app_path();
        }

        switch ( $sector ) {
            case 'php':
                $path = $base . '/library/php';
                break;

            case 'classes':
                $path = $base . '/library/php/classes';
                break;

            case 'interfaces':
                $path = $base . '/library/php/classes/interfaces';
                break;

            case 'abstracts':
                $path = $base . '/library/php/classes/abstracts';
                break;

            case 'widgets':
                $path = $base . '/library/php/classes/widgets';
                break;

            case 'js':
                $path = $base . '/library/js';
                break;

            case 'css':
                $path = $base . '/library/css';
                break;

            case 'images':
                $path = $base . '/library/images';
                break;

            case 'vendor':
                $path = $base . '/library/vendor';
                break;

            case 'bower':
                $path = $base . '/library/bower_components';
                break;

            case 'foundation':
                $path = $base . '/library/bower_components/foundation';
                break;

            case 'mu-plugins':
                $path = $base . '/library/mu-plugins';
                break;

            default:
                $path = $base;
                break;
        }

        return $path;
    }

    public function app_path( $sector = '' ) {
        if ( ! $this->app_path )
            $this->app_path = untrailingslashit( get_stylesheet_directory() );

        return $this->app_location( $sector, $this->app_path );
    }

    public function app_url( $sector = '' ) {
        if ( ! $this->app_url )
            $this->app_url = untrailingslashit( get_stylesheet_directory_uri() );

        return $this->app_location( $sector, $this->app_url );
    }

    public function db_get( $key ) {
        return get_option( '_gonear_' . $key );
    }

    public function db_set( $key, $value ) {
        return update_option( '_gonear_' . $key, $value );
    }

    public function has_installed() {
        return ( (bool) $this->db_get( 'db_version' ) );
    }

    public function needs_upgrade() {
        return ( (bool) version_compare( $this->db_get( 'db_version' ), $this->version, '<' ) );
    }

    public function load() {
        if ( file_exists( $autoloader = $this->app_path( 'vendor' ) . '/autoload.php' ) ) {
            include_once( $autoloader );

            \Genesis\Page_Layouts::instance();
            \Genesis\Post_Layouts::instance();
        }

        add_action( 'admin_init', array( $this, 'check_upgrades' ), 5 );
        add_action( 'tgmpa_register', array( $this, 'register_plugins' ) );
    }

    public function check_upgrades() {
        if ( $this->has_installed() && $this->needs_upgrade() ) {
            $this->upgrade();
        } else {
            $this->install();
        }
    }

    public function upgrade() {

    }

    public function install() {
        if ( $this->db_get( 'db_version' ) )
            return;

        $this->db_set( 'db_version', $this->version );
    }

    public function register_plugins() {
        $config = array(
            'id'           => 'tgmpa',
            'default_path' => '',
            'menu'         => 'tgmpa-install-plugins',
            'has_notices'  => true,
            'dismissable'  => true,
            'dismiss_msg'  => '',
            'is_automatic' => false,
            'message'      => '',
            'strings'      => array(
                'page_title'                      => __( 'Install Required Plugins', 'tgmpa' ),
                'menu_title'                      => __( 'Install Plugins', 'tgmpa' ),
                'installing'                      => __( 'Installing Plugin: %s', 'tgmpa' ),
                'oops'                            => __( 'Something went wrong with the plugin API.', 'tgmpa' ),
                'notice_can_install_required'     => _n_noop( 'This theme requires the following plugin: %1$s.', 'This theme requires the following plugins: %1$s.', 'tgmpa' ),
                'notice_can_install_recommended'  => _n_noop( 'This theme recommends the following plugin: %1$s.', 'This theme recommends the following plugins: %1$s.', 'tgmpa' ),
                'notice_cannot_install'           => _n_noop( 'Sorry, but you do not have the correct permissions to install the %s plugin. Contact the administrator of this site for help on getting the plugin installed.', 'Sorry, but you do not have the correct permissions to install the %s plugins. Contact the administrator of this site for help on getting the plugins installed.', 'tgmpa' ),
                'notice_can_activate_required'    => _n_noop( 'The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.', 'tgmpa' ),
                'notice_can_activate_recommended' => _n_noop( 'The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.', 'tgmpa' ),
                'notice_cannot_activate'          => _n_noop( 'Sorry, but you do not have the correct permissions to activate the %s plugin. Contact the administrator of this site for help on getting the plugin activated.', 'Sorry, but you do not have the correct permissions to activate the %s plugins. Contact the administrator of this site for help on getting the plugins activated.', 'tgmpa' ),
                'notice_ask_to_update'            => _n_noop( 'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.', 'tgmpa' ),
                'notice_cannot_update'            => _n_noop( 'Sorry, but you do not have the correct permissions to update the %s plugin. Contact the administrator of this site for help on getting the plugin updated.', 'Sorry, but you do not have the correct permissions to update the %s plugins. Contact the administrator of this site for help on getting the plugins updated.', 'tgmpa' ),
                'install_link'                    => _n_noop( 'Begin installing plugin', 'Begin installing plugins', 'tgmpa' ),
                'activate_link'                   => _n_noop( 'Begin activating plugin', 'Begin activating plugins', 'tgmpa' ),
                'return'                          => __( 'Return to Required Plugins Installer', 'tgmpa' ),
                'plugin_activated'                => __( 'Plugin activated successfully.', 'tgmpa' ),
                'complete'                        => __( 'All plugins installed and activated successfully. %s', 'tgmpa' ),
                'nag_type'                        => 'updated'
            )
        );

        $plugins = array(
            array(
                'name'               => 'GitHub Updater',
                'slug'               => 'github-updater-develop',
                'source'             => 'https://github.com/afragen/github-updater/archive/develop.zip',
                'required'           => true,
                'force_activation'   => true,
                'external_url'       => 'https://github.com/afragen/github-updater',
            ),
            array(
                'name'             => 'MailChimp for WordPress Lite',
                'slug'             => 'mailchimp-for-wp',
                'required'         => true,
                'force_activation' => true,
            ),
            array(
                'name'             => 'Limit Image Size',
                'slug'             => 'limit-image-size',
                'required'         => true,
                'force_activation' => true,
            ),
            array(
                'name'             => 'Jetpack',
                'slug'             => 'jetpack',
                'required'         => true,
                'force_activation' => true,
            ),
            array(
                'name'             => 'Akismet',
                'slug'             => 'akismet',
                'required'         => true,
                'force_activation' => true,
            ),
            array(
                'name'             => 'WYSIWYG Widgets / Widget Blocks',
                'slug'             => 'wysiwyg-widgets',
                'required'         => true,
                'force_activation' => true,
            ),
        );

        tgmpa( $plugins, $config );
    }
}

App::instance();
function app() {
    return App::instance();
}