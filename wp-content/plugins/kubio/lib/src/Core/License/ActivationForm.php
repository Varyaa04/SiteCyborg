<?php
namespace Kubio\Core\License;

use IlluminateAgnostic\Arr\Support\Arr;
use Kubio\Core\LodashBasic;
use Kubio\Core\License\License;
use Kubio\Core\License\Updater;
use Kubio\Flags;
use Plugin_Upgrader;
class ActivationForm {
	const FEEDBACK_URL = 'https://kubiobuilder.com/upgrade-reason-results/';

	public function __construct() {
		add_action( 'wp_ajax_kubiowp-page-builder-activate', array( $this, 'callActivateLicenseEndpoint' ) );
		add_action( 'wp_ajax_kubiowp-page-builder-upgrade-feedback', array( $this, 'callUpgradeFeedback' ) );
		add_action( 'wp_ajax_kubiowp-page-builder-maybe-install-pro', array( $this, 'maybeInstallPRO' ) );
	}

	public function printForm() {
		add_action( 'admin_notices', array( $this, 'makeActivateNotice' ) );
		$this->enqueue();
	}

	public function enqueue() {
		wp_enqueue_script( 'wp-util' );
	}

	public function makeUpgradeView( $message = '' ) {
		?>
		<div class="kubio-page-builder-upgade-view kubio-admin-panel">
			<div class="kubio-page-builder-license-notice kubio-page-builder-activate-license">
				<h3 class="notice_title"><?php esc_html_e( 'Enter a valid Kubio PRO license key to unlock all the PRO features', 'kubio' ); ?></h3>
				<?php echo $this->formHtml( $message ); ?>
			</div>
		</div>
		<?php echo $this->formUpgradeReasonPopup(); ?>
		<?php
	}

	public function makeActivateNotice( $formId = '', $classHhtml = array(), $message = '' ) {

		$screen = get_current_screen();
		global $post;
		$action          = ( $screen && $screen->action ) ? $screen->action : Arr::get( $_REQUEST, 'action', '' );
		$is_block_editor = ( $screen && $screen->is_block_editor ) || ( ! empty( $action ) && $post && use_block_editor_for_post( $post ) );
		$is_block_editor = $is_block_editor || did_filter( 'block_editor_settings_all' );

		if ( $is_block_editor ) {
			return;
		}

		if ( ! array( $classHhtml ) ) {
			$classHhtml = array( $classHhtml );
		}
		if ( $formId !== '' ) {
			$formId = ' id="' . esc_attr( $formId ) . '"';
		}
		$classHhtml = implode( ' ', $classHhtml );
		?>
		<div class="notice notice-error is-dismissible kubio-activation-wrapper <?php echo esc_attr( $classHhtml ); ?>"<?php echo $formId; ?>>
			<div class="notification-logo-wrapper">
				<div class="notification-logo">
					<?php echo wp_kses_post( KUBIO_LOGO_SVG ); ?>
				</div>
			</div>
			<div class="kubio-page-builder-license-notice kubio-page-builder-activate-license">
				<h1 class="notice_title"><?php esc_html_e( 'Activate Kubio PRO License', 'kubio' ); ?></h1>
				<h3 class="notice_sub_title"><?php esc_html_e( 'If this is a testing site you can ignore this message. If this is your live site then please insert the license key below.', 'kubio' ); ?></h3>
				<?php echo $this->formHtml( $message ); ?>
			</div>
		</div>
		<?php
	}

	public function formHtml( $message = '' ) {
		$html = '<div class="kubio-page-builder-activate-license-form_wrapper">
			<form id="kubio-page-builder-activate-license-form" class="activate-form">
				<input placeholder="6F474380-5929B874-D2E0CB90-C7282097" type="text"
					value="' . esc_attr( get_option( 'kubio_sync_data_source', '' ) ) . '"
					class="regular-text">
				<button type="submit" class="button button-primary">' . esc_html__( 'Activate License', 'kubio' ) . '</button>
			</form>
			' . $this->formMessage( $message ) . '
		</div>';

		return $html;
	}

	public function formMessage( $message = '' ) {
		$html = '';
		if ( '' === $message ) {
			$html .= '<p id="kubio-page-builder-activate-license-message" class="message" style="display: none;"></p>';
		} else {
			$html .= '<p id="kubio-page-builder-activate-license-message" class="message">' . $message . '</p>';
		}

		$html .= '<p class="description">';
		// translators: placeholders are some urls like <a href="#">My Account</a>
		$html .= sprintf( __( 'Your key was sent via email when the purchase was completed. Also you can find the key in the %1$s of your %2$s account', 'kubio' ), '<a href="' . esc_attr( License::getInstance()->getDashboardUrl() ) . '/#/my-plans" target="_blank">My plans</a>', '<a href="' . esc_attr( License::getInstance()->getDashboardUrl() ) . '" target="_blank">Kubio</a>' );
		$html .= '</p>
		<div class="spinner-holder plugin-installer-spinner" style="display: none;">
			<span class="icon">
				<span class="loader">' . kubio_get_iframe_loader(
			array(
				'size'  => '19px',
				'color' => '#2271B1',
			)
		) . '</span>
				<span class="ok"><span class="dashicons dashicons-before dashicons-yes"></span></span>
			</span>
			<span class="message"></span>
		</div>';

		return $html;
	}

	public function callActivateLicenseEndpoint() {
		$key = isset( $_REQUEST['key'] ) ? sanitize_text_field( $_REQUEST['key'] ) : false;

		if ( ! $key ) {
			wp_send_json_error( esc_html__( 'License key is empty', 'kubio' ), 403 );
		}

		License::getInstance()->setLicenseKey( $key );
		$response = Endpoint::activate();

		if ( $response->isError() ) {
			License::getInstance()->setLicenseKey( null );
		}

		wp_send_json(
			array(
				'data'    => $response->getMessage( true ),
				'success' => $response->isSuccess(),
			),
			$response->getResponseCode()
		);
	}

	public function maybeInstallPRO() {
		add_filter(
			'kubio/companion/update_remote_data',
			function ( $data ) {
				$data['args'] = array(
					'product' => 'kubio-pro',
					'key'     => License::getInstance()->getLicenseKey(),
				);

				$data['plugin_path'] = 'kubio-pro/plugin.php';

				return $data;
			},
			PHP_INT_MAX
		);

		$status = (array) Updater::getInstance()->isUpdateAvailable();
		$url    = LodashBasic::array_get_value( $status, 'package_url', false );

		if ( $url ) {

			if ( ! function_exists( 'plugins_api' ) ) {
				include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' ); //for plugins_api..
			}

			if ( ! class_exists( 'Plugin_Upgrader' ) ) {
				/** Plugin_Upgrader class */
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}

			$upgrader = new Plugin_Upgrader( new \Automatic_Upgrader_Skin() );
			$result   = $upgrader->install( $url );

			if ( $result !== true && $result !== null ) {
				wp_send_json_error();
			}

			$ac   = get_option( 'active_plugins' );
			$ac   = array_diff( $ac, array( 'kubio/plugin.php' ) );
			$ac[] = 'kubio-pro/plugin.php';
			update_option( 'active_plugins', $ac );

			// set the kubio pro plugin activation time
			if ( ! Flags::get( 'kubio_pro_activation_time', false ) ) {
				Flags::set( 'kubio_pro_activation_time', time() );
			}

			wp_send_json_success( array( 'message' => esc_html__( 'No error', 'kubio' ) ) );
		}

		wp_send_json_success( $status );
	}

	public function formUpgradeReasonPopup() {
		$upgrade_reasons = array(
			'ai-features'           => __( 'AI Features', 'kubio' ),
			'more-sections'         => __( 'More sections and blocks', 'kubio' ),
			'edit-footer'           => __( 'Footer editing', 'kubio' ),
			'multiple-page-headers' => __( 'Multiple page headers', 'kubio' ),
			'other'                 => __( 'Other', 'kubio' ),
		);
		ob_start();
		?>
		<div class="kubio--modal kubio--modal-hidden">
			<div class="kubio--modal__content">
				<div class="kubio--popup__wrapper">
					<div class="kubio--popup__close">
						<button type="button" class="button"></button>
					</div>
					<!-- /.kubio--popup__close -->
					<div class="kubio--popup__content">
						<div class="kubio--popup__left">
							<img src="<?php echo kubio_url( 'static/admin-pages/upgrade-reason.png' ); ?>" alt="">
						</div>
						<!-- /.kubio--popup__left -->
						<div class="kubio--popup__right">
							<p><?php _e( 'Thank you for choosing Kubio PRO!', 'kubio' ); ?></p>
							<h2>
							<?php
							_e(
								'Could you tell us what inspired your decision to upgrade?',
								'kubio'
							);
							?>
								</h2>

							<div class="kubio--popup__form">
								<?php
								foreach ( $upgrade_reasons as $reason => $label ) :
									?>
									<label>
										<input type="radio" name="upgrade__reason" value="<?php echo $reason; ?>">										
										<em></em>
										<span><?php echo $label; ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
						<!-- /.kubio--popup__right -->
					</div>
				</div>
				<!-- /.kubio--popup__wrapper -->
			</div>
			<!-- /.kubio--modal__content -->
		</div>
		<!-- /.kubio--modal -->

		<?php
		$str = ob_get_clean();
		return $str;
	}

	public function callUpgradeFeedback() {
		$license = isset( $_REQUEST['license'] ) ? sanitize_text_field( $_REQUEST['license'] ) : '';
		$reason  = isset( $_REQUEST['reason'] ) ? sanitize_text_field( $_REQUEST['reason'] ) : '';

		$response = wp_remote_post(
			self::FEEDBACK_URL,
			array(
				'sslverify' => false,
				'body'      => (
					array(
						'license' => $license,
						'reason'  => $reason,
					)
				),
			)
		);

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		wp_send_json_success(
			array(
				'data' => isset( $body->status ) ? $body->status : 'error',
			)
		);

	}
}
