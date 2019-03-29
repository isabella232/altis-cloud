<?php

namespace HM\Platform\Cloud;

use const HM\Platform\ROOT_DIR;
use function HM\Platform\get_config;
use function HM\Platform\get_environment_architecture;
use function HM\Platform\register_module;
use HM\Platform\XRay;

require_once __DIR__ . '/inc/namespace.php';

add_action( 'hm-platform.modules.init', function () {
	$default_settings = [
		'enabled'            => in_array( get_environment_architecture(), [ 'ec2', 'ecs', 'local-server' ] ),
		'cavalcade'          => true,
		's3-uploads'         => true,
		'aws-ses-wp-mail'    => true,
		'batcache'           => true,
		'redis'              => true,
		'ludicrousdb'        => true,
		'healthcheck'        => true,
		'xray'               => true,
		'email-from-address' => 'no-reply@humanmade.com',
	];

	register_module( 'cloud', __DIR__, 'Cloud', $default_settings, function () {
		$config = get_config()['modules']['cloud'];

		if (
			$config['xray']
			&& function_exists( 'xhprof_sample_enable' )
			&& ( ! defined( 'WP_CLI' ) || ! WP_CLI )
			&& ! class_exists( 'HM\\Cavalcade\\Runner\\Runner' )
		) {
			require_once ROOT_DIR . '/vendor/humanmade/aws-xray/inc/namespace.php';
			require_once ROOT_DIR . '/vendor/humanmade/aws-xray/plugin.php';
			XRay\bootstrap();
		}

		add_filter( 'wp_mail_from', function ( string $email ) use ( $config ) : string {
			return $config['email-from-address'];
		}, 1 );

		// Load the platform as soon as WP is loaded.
		add_action( 'enable_wp_debug_mode_checks', __NAMESPACE__ . '\\bootstrap' );

		if ( class_exists( 'HM\\Cavalcade\\Runner\\Runner' ) && $config['cavalcade'] ) {
			boostrap_cavalcade_runner();
		}
	} );
} );

