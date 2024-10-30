<?php

namespace Fragen\Local_Development_Upgrade_Warning;

/*
 * Exit if called directly.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Init
 *
 * @package Fragen\Local_Development_Upgrade_Warning
 */
class Init {

	/**
	 * Init constructor.
	 */
	public function __construct() {

		$config = get_site_option( 'local_development_upgrade_warning' );
		new Settings();

		/*
		 * Skip on hearbeat or if no saved settings.
		 */
		if ( ( isset( $_POST['action'] ) && 'heartbeat' === $_POST['action'] ) || ! $config ) {
			return false;
		}
		new Base( $config );
	}
}
