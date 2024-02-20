<?php

use IlluminateAgnostic\Arr\Support\Arr;
use Kubio\Core\Utils;
use Kubio\FileLog;
use Kubio\Flags;

add_filter(
	'kubio/admin-page/info_page_tabs',
	function( $tabs ) {
		if ( ! Flags::getSetting( 'enableAICapabilities', false ) || ( Flags::getSetting( 'aiStage2' ) && kubio_is_free() ) ) {
			return $tabs;
		}

		$tabs = array_merge(
			$tabs,
			array(
				'ai-info' => array(
					'type'        => 'page',
					'label'       => __( 'Kubio AI', 'kubio' ),
					'tab-partial' => __DIR__ . '/partials/main.php',
					'subtitle'    => __( 'Build smarter & faster with Kubio AI', 'kubio' ),
				),
			)
		);

		if ( defined( 'KUBIO_AI_LOG' ) && KUBIO_AI_LOG ) {
			$tabs = array_merge(
				$tabs,
				array(
					'ai-logs' => array(
						'type'        => 'page',
						'label'       => 'Kubio AI Logs',
						'tab-partial' => __DIR__ . '/partials/log.php',
						'subtitle'    => 'Kubio AI internal logs' . '<br/>' . sprintf( 'Cloud URL: %s', preg_replace( '#\?(.*)#', '', Utils::getCloudURL() ) ),
					),
				)
			);
		}

		return $tabs;
	}
);

add_action(
	'wp_ajax_kubio_clear_ai_logs',
	function() {

		$nonce = Arr::get( $_REQUEST, '_wpnonce', '' );
		if ( ! wp_verify_nonce( $nonce, 'kubio_clear_ai_logs' ) ) {
			wp_die(
				__( 'Unauthorized', 'kubio' ),
				__( 'Unauthorized', 'kubio' ),
				array(
					'response' => 401,
				)
			);
		}

		$logs_file = FileLog::get_log_files( 'AI' );

		foreach ( $logs_file as $file ) {
			unlink( $file );
		}

		wp_redirect( wp_get_referer() );
		exit();
	}
);
