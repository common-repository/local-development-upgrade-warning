<?php

namespace Fragen\Local_Development_Upgrade_Warning;

/*
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Settings
 *
 * @package Fragen\Local_Development_Upgrade_Warning
 */
class Settings {

	/**
	 * Holds plugin data.
	 * @var
	 */
	protected $plugins;

	/**
	 * Holds theme data.
	 * @var
	 */
	protected $themes;

	/**
	 * Holds plugin settings.
	 * @var mixed|void
	 */
	protected static $options;

	/**
	 * Holds the plugin basename.
	 *
	 * @var string
	 */
	private $plugin_slug = 'local-development-upgrade-warning/local-development-upgrade-warning.php';


	/**
	 * Settings constructor.
	 */
	public function __construct() {
		add_action( 'init', array( &$this, 'init' ) );

		self::$options = get_site_option( 'local_development_upgrade_warning' );

		add_action( is_multisite() ? 'network_admin_menu' : 'admin_menu', array( &$this, 'add_plugin_page' ) );
		add_action( 'network_admin_edit_local-development-upgrade-warning', array( &$this, 'update_network_settings' ) );
		add_action( 'admin_init', array( &$this, 'page_init' ) );
		add_action( 'admin_head', array( &$this, 'style_settings' ) );

		add_filter( is_multisite() ? 'network_admin_plugin_action_links_' . $this->plugin_slug : 'plugin_action_links_' . $this->plugin_slug, array( &$this, 'plugin_action_links' ) );
	}

	/**
	 * Initialize plugin/theme data. Needs to be called in the 'init' hook.
	 */
	public function init() {
		/*
		 * Ensure get_plugins() function is available.
		 */
		include_once( ABSPATH . '/wp-admin/includes/plugin.php' );

		$this->plugins = get_plugins();
		$this->themes  = wp_get_themes( array( 'errors' => null ) );

		foreach ( array_keys( $this->plugins ) as $slug ) {
			$plugins[ $slug ] = $this->plugins[ $slug ]['Name'];
		}
		$this->plugins = $plugins;

		foreach ( array_keys( $this->themes ) as $slug ) {
			$themes[ $slug ] = $this->themes[ $slug ]->get( 'Name' );
		}
		$this->themes = $themes;
	}

	/**
	 * Define tabs for Settings page.
	 * By defining in a method, strings can be translated.
	 *
	 * @access private
	 *
	 * @return array
	 */
	private function _settings_tabs() {
		return array(
			'local_dev_settings_plugins' => esc_html__( 'Plugins', 'local-development-upgrade-warning' ),
			'local_dev_settings_themes'  => esc_html__( 'Themes', 'local-development-upgrade-warning' ),
		);
	}

	/**
	 * Add options page.
	 */
	public function add_plugin_page() {
		if ( is_multisite() ) {
			add_submenu_page(
				'settings.php',
				esc_html__( 'Local Development Upgrade Warning Settings', 'local-development-upgrade-warning' ),
				esc_html__( 'Local Development', 'local-development-upgrade-warning' ),
				'manage_network',
				'local-development-upgrade-warning',
				array( &$this, 'create_admin_page' )
			);
		} else {
			add_options_page(
				esc_html__( 'Local Development Upgrade Warning Settings', 'local-development-upgrade-warning' ),
				esc_html__( 'Local Development', 'local-development-upgrade-warning' ),
				'manage_options',
				'local-development-upgrade-warning',
				array( &$this, 'create_admin_page' )
			);
		}
	}

	/**
	 * Renders setting tabs.
	 *
	 * Walks through the object's tabs array and prints them one by one.
	 * Provides the heading for the settings page.
	 *
	 * @access private
	 */
	private function _options_tabs() {
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'local_dev_settings_plugins';
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $this->_settings_tabs() as $key => $name ) {
			$active = ( $current_tab == $key ) ? 'nav-tab-active' : '';
			echo '<a class="nav-tab ' . $active . '" href="?page=local-development-upgrade-warning&tab=' . $key . '">' . $name . '</a>';
		}
		echo '</h2>';
	}

	/**
	 * Options page callback.
	 */
	public function create_admin_page() {
		$action = is_multisite() ? 'edit.php?action=local-development-upgrade-warning' : 'options.php';
		$tab    = isset( $_GET['tab'] ) ? $_GET['tab'] : 'local_dev_settings_plugins';
		?>
		<div class="wrap">
			<h2>
				<?php esc_html_e( 'Local Development Upgrade Warning Settings', 'local-development-upgrade-warning' ); ?>
			</h2>
			<?php $this->_options_tabs(); ?>
			<?php if ( isset( $_GET['updated'] ) && true == $_GET['updated'] ): ?>
				<div class="updated"><p><strong><?php esc_html_e( 'Saved.', 'local-development-upgrade-warning' ); ?></strong></p></div>
			<?php endif; ?>
			<?php if ( 'local_dev_settings_plugins' === $tab ) : ?>
				<form method="post" action="<?php esc_attr_e( $action ); ?>">
					<?php
					settings_fields( 'local_development_settings' );
					do_settings_sections( 'local_dev_plugins' );
					submit_button();
					?>
				</form>
			<?php endif; ?>

			<?php if ( 'local_dev_settings_themes' === $tab ) : ?>
				<?php $action = add_query_arg( 'tab', $tab, $action ); ?>
				<form method="post" action="<?php esc_attr_e( $action ); ?>">
					<?php
					settings_fields( 'local_development_settings' );
					do_settings_sections( 'local_dev_themes' );
					submit_button();
					?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Register and add settings.
	 */
	public function page_init() {

		/*
		 * Plugin settings.
		 */
		register_setting(
			'local_development_settings',
			'local_dev_plugins',
			array( &$this, 'sanitize' )
		);

		add_settings_section(
			'local_dev_plugins',
			esc_html__( 'Plugins', 'local-development-upgrade-warning' ),
			array( &$this, 'print_section_plugins' ),
			'local_dev_plugins'
		);

		foreach ( $this->plugins as $id => $name ) {
			add_settings_field(
				$id,
				esc_html__( $name ),
				array( &$this, 'token_callback_checkbox' ),
				'local_dev_plugins',
				'local_dev_plugins',
				array( 'id' => $id, 'type' => 'plugins' )
			);
		}

		/*
		 * Theme settings.
		 */
		register_setting(
			'local_development_settings',
			'local_dev_themes',
			array( &$this, 'sanitize' )
		);

		add_settings_section(
			'local_dev_themes',
			esc_html__( 'Themes', 'local-development-upgrade-warning' ),
			array( &$this, 'print_section_themes' ),
			'local_dev_themes'
		);

		foreach ( $this->themes as $id => $name ) {
			add_settings_field(
				$id,
				esc_html__( $name ),
				array( &$this, 'token_callback_checkbox' ),
				'local_dev_themes',
				'local_dev_themes',
				array( 'id' => $id, 'type' => 'themes' )
			);
		}

		$this->update_settings();
	}

	/**s
	 * Print the plugin text.
	 */
	public function print_section_plugins() {
		esc_html_e( 'Select the locally developed plugins.', 'local-development-upgrade-warning' );
	}

	/**
	 * Print the theme text.
	 */
	public function print_section_themes() {
		esc_html_e( 'Select the locally developed themes.', 'local-development-upgrade-warning' );
	}

	/**
	 * Sanitize each setting field as needed.
	 *
	 * @param array $input Contains all settings fields as array keys
	 *
	 * @return array
	 */
	public static function sanitize( $input ) {
		$new_input = array();
		foreach ( array_keys( (array) $input ) as $id ) {
			$new_input[ sanitize_text_field( $id ) ] = sanitize_text_field( $input[ $id ] );
		}

		return $new_input;
	}

	/**
	 * Get the settings option array and print one of its values.
	 *
	 * @param $args
	 */
	public function token_callback_checkbox( $args ) {
		self::$options[ $args['type'] ][ $args['id'] ] = isset( self::$options[ $args['type'] ][ $args['id'] ] ) ? esc_attr( self::$options[ $args['type'] ][ $args['id'] ] ) : null;
		?>
		<label for="<?php esc_attr_e( $args['id'] ); ?>">
			<input type="checkbox" name="local_dev[<?php esc_attr_e( $args['id'] ); ?>]" value="1" <?php checked('1', self::$options[ $args['type'] ][ $args['id'] ], true); ?> >
		</label>
		<?php
	}

	/**
	 * Update settings for single install.
	 */
	public function update_settings() {

		if ( ! isset( $_POST['_wp_http_referer'] ) ) {
			return false;
		}
		$query =  parse_url( $_POST['_wp_http_referer'], PHP_URL_QUERY );
		parse_str( $query, $arr );
		if ( empty( $arr['tab'] ) ) {
			$arr['tab'] = 'local_dev_settings_plugins';
		}

		if ( ( isset( $_POST['option_page'] ) && ( 'local_development_settings' === $_POST['option_page'] ) ) &&
		     isset( $_POST['local_dev'] ) && ! is_multisite()
		) {
			if ( 'local_dev_settings_plugins' === $arr['tab'] ) {
				self::$options['plugins'] = self::sanitize( $_POST['local_dev'] );
			}
			if ( 'local_dev_settings_themes' === $arr['tab'] ) {
				self::$options['themes'] = self::sanitize( $_POST['local_dev'] );
			}
			update_site_option( 'local_development_upgrade_warning', self::$options );
		}
	}

	/**
	 * Update network settings.
	 * Used when plugin is network activated to save settings.
	 *
	 * @link http://wordpress.stackexchange.com/questions/64968/settings-api-in-multisite-missing-update-message
	 * @link http://benohead.com/wordpress-network-wide-plugin-settings/
	 */
	public function update_network_settings() {

		$query = parse_url( $_POST['_wp_http_referer'], PHP_URL_QUERY );
		parse_str( $query, $arr );
		if ( empty( $arr['tab'] ) ) {
			$arr['tab'] = 'local_dev_settings_plugins';
		}

		if ( 'local_development_settings' === $_POST['option_page'] ) {
			if ( 'local_dev_settings_plugins' === $arr['tab'] ) {
				self::$options['plugins'] = self::sanitize( $_POST['local_dev'] );
			}
			if ( 'local_dev_settings_themes' === $arr['tab'] ) {
				self::$options['themes'] = self::sanitize( $_POST['local_dev'] );
			}
			update_site_option( 'local_development_upgrade_warning', self::$options );
		}

		$location = add_query_arg(
			array(
				'page'    => 'local-development-upgrade-warning',
				'updated' => 'true',
				'tab'     => $arr['tab'],
			),
			network_admin_url( 'settings.php' )
		);
		wp_redirect( $location );
		exit;
	}

	/**
	 * Add setting link to plugin page.
	 * Applied to the list of links to display on the plugins page (beside the activate/deactivate links).
	 *
	 * @link http://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings_page = is_multisite() ? 'settings.php' : 'options-general.php';
		$link          = array( '<a href="' . esc_url( network_admin_url( $settings_page ) ) . '?page=local-development-upgrade-warning">' . esc_html__( 'Settings', 'local-development-upgrade-warning' ) . '</a>' );

		return array_merge( $links, $link );
	}

	/**
	 * Style settings.
	 */
	public function style_settings() {
		?>
		<style>.form-table th { width: 40%; }</style>
		<?php
	}

}
