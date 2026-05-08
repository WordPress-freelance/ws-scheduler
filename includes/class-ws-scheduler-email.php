<?php
/**
 * Email handling for WS Scheduler — HTML templates with Pro customization support.
 *
 * @since      3.0.0
 * @package    WS_Scheduler
 * @subpackage WS_Scheduler/includes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Scheduler_Email {

	/**
	 * Get email colors (Pro can override).
	 */
	private static function get_colors() {
		$defaults = array(
			'bg_outer'    => '#0E0C15',
			'bg_header'   => '#1A1724',
			'bg_body'     => '#14121C',
			'bg_footer'   => '#1A1724',
			'bg_row'      => '#1A1724',
			'bg_msg'      => '#221D32',
			'border'      => '#2E2B38',
			'accent'      => '#7C5CBF',
			'text_title'  => '#F0EDE8',
			'text_body'   => '#C4BFDA',
			'text_muted'  => '#9590A8',
			'text_label'  => '#9590A8',
			'text_value'  => '#A899D4',
			'text_footer' => '#6E6888',
			'link'        => '#9B8EC4',
		);
		$saved = get_option( 'wsp_email_colors', array() );
		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Get email logo (Pro can set a custom one).
	 */
	private static function get_logo_html( $c ) {
		$logo_url = get_option( 'wsp_email_logo', '' );
		if ( ! empty( $logo_url ) ) {
			return '<img src="' . esc_url( $logo_url ) . '" width="50" height="50" alt="" style="border-radius:8px;display:block;">';
		}
		// Default: colored W square
		return '<div style="width:32px;height:32px;background:' . esc_attr( $c['accent'] ) . ';border-radius:6px;text-align:center;line-height:32px;font-size:14px;font-weight:700;color:#fff;">W</div>';
	}

	/**
	 * Send confirmation email to the client.
	 */
	public static function send_client_confirmation( $appointment ) {
		$business = get_option( 'ws_business_name', get_bloginfo( 'name' ) );
		$tz       = new DateTimeZone( wp_timezone_string() );
		$dt       = new DateTime( $appointment->slot_start, $tz );
		$date_str = self::format_date_fr( $dt );
		$time_str = $dt->format( 'H:i' );
		$c        = self::get_colors();

		$subject = sprintf( __( '[%1$s] Confirmation de rendez-vous — %2$s à %3$s', 'ws-scheduler' ), $business, $dt->format( 'd/m/Y' ), $time_str );

		$details = '';
		$details .= self::detail_row( __( 'Date', 'ws-scheduler' ), $date_str, $c );
		$details .= self::detail_row( __( 'Heure', 'ws-scheduler' ), $time_str, $c );
		$details .= self::detail_row( __( 'Durée', 'ws-scheduler' ), $appointment->duration . ' ' . __( 'minutes', 'ws-scheduler' ), $c );

		if ( ! empty( $appointment->meet_link ) ) {
			$details .= self::detail_row( __( 'Google Meet', 'ws-scheduler' ), '<a href="' . esc_url( $appointment->meet_link ) . '" style="color:' . esc_attr( $c['text_value'] ) . ';">' . esc_html( $appointment->meet_link ) . '</a>', $c );
		}

		// Custom texts (Pro) or defaults
		$greeting = get_option( 'wsp_email_confirm_greeting', '' );
		if ( empty( $greeting ) ) {
			$greeting = sprintf( __( 'Bonjour %s,', 'ws-scheduler' ), $appointment->first_name );
		} else {
			$greeting = str_replace( '{prenom}', $appointment->first_name, $greeting );
			$greeting = str_replace( '{nom}', $appointment->last_name, $greeting );
		}

		$intro = get_option( 'wsp_email_confirm_intro', '' );
		if ( empty( $intro ) ) {
			$intro = __( 'Votre rendez-vous est confirmé. Voici les détails :', 'ws-scheduler' );
		}

		$footer_text = get_option( 'wsp_email_confirm_footer', '' );
		if ( empty( $footer_text ) ) {
			$footer_text = __( 'Si vous devez annuler ou reporter, contactez-nous directement en répondant à cet email.', 'ws-scheduler' );
		}

		$body  = '<p style="margin:0 0 16px;color:' . esc_attr( $c['text_body'] ) . ';font-size:15px;line-height:1.7;">' . esc_html( $greeting ) . '</p>';
		$body .= '<p style="margin:0 0 20px;color:' . esc_attr( $c['text_muted'] ) . ';font-size:14px;line-height:1.7;">' . esc_html( $intro ) . '</p>';
		$body .= $details;
		$body .= '<p style="margin:20px 0 0;color:' . esc_attr( $c['text_muted'] ) . ';font-size:13px;line-height:1.7;">' . esc_html( $footer_text ) . '</p>';

		$html = self::wrap_template( $business, $body, $c );
		self::send( $appointment->email, $subject, $html );
	}

	/**
	 * Send notification email to the admin.
	 */
	public static function send_admin_notification( $appointment ) {
		$admin_email = get_option( 'ws_admin_email', '' );
		if ( empty( $admin_email ) ) {
			$admin_email = get_option( 'admin_email' );
		}
		$business = get_option( 'ws_business_name', get_bloginfo( 'name' ) );
		$tz       = new DateTimeZone( wp_timezone_string() );
		$dt       = new DateTime( $appointment->slot_start, $tz );
		$date_str = self::format_date_fr( $dt );
		$time_str = $dt->format( 'H:i' );
		$c        = self::get_colors();

		$subject = sprintf( __( '[%1$s] Nouveau RDV — %2$s %3$s — %4$s', 'ws-scheduler' ), $business, $appointment->first_name, $appointment->last_name, $dt->format( 'd/m/Y' ) );

		$details = '';
		$details .= self::detail_row( __( 'Client', 'ws-scheduler' ), esc_html( $appointment->first_name . ' ' . $appointment->last_name ), $c );
		if ( ! empty( $appointment->company ) ) {
			$details .= self::detail_row( __( 'Société', 'ws-scheduler' ), esc_html( $appointment->company ), $c );
		}
		$details .= self::detail_row( __( 'Email', 'ws-scheduler' ), '<a href="mailto:' . esc_attr( $appointment->email ) . '" style="color:' . esc_attr( $c['text_value'] ) . ';">' . esc_html( $appointment->email ) . '</a>', $c );
		if ( ! empty( $appointment->phone ) ) {
			$details .= self::detail_row( __( 'Téléphone', 'ws-scheduler' ), esc_html( $appointment->phone ), $c );
		}
		$details .= self::detail_row( __( 'Date', 'ws-scheduler' ), $date_str, $c );
		$details .= self::detail_row( __( 'Heure', 'ws-scheduler' ), $time_str, $c );
		$details .= self::detail_row( __( 'Durée', 'ws-scheduler' ), $appointment->duration . ' min', $c );

		if ( ! empty( $appointment->meet_link ) ) {
			$details .= self::detail_row( __( 'Meet', 'ws-scheduler' ), '<a href="' . esc_url( $appointment->meet_link ) . '" style="color:' . esc_attr( $c['text_value'] ) . ';">' . esc_html__( 'Lien', 'ws-scheduler' ) . '</a>', $c );
		}

		$title = esc_html__( 'Nouveau rendez-vous reçu !', 'ws-scheduler' );
		$body  = '<p style="margin:0 0 20px;color:' . esc_attr( $c['text_body'] ) . ';font-size:15px;line-height:1.7;">' . $title . '</p>';
		$body .= $details;

		if ( ! empty( $appointment->message ) ) {
			$msg_label = esc_html__( 'Message du client', 'ws-scheduler' );
			$body .= '<div style="margin:20px 0 0;background:' . esc_attr( $c['bg_msg'] ) . ';border:1px solid ' . esc_attr( $c['border'] ) . ';border-radius:8px;padding:16px;">';
			$body .= '<p style="margin:0 0 6px;font-size:11px;color:' . esc_attr( $c['text_footer'] ) . ';text-transform:uppercase;letter-spacing:0.05em;">' . $msg_label . '</p>';
			$body .= '<p style="margin:0;color:' . esc_attr( $c['text_body'] ) . ';font-size:14px;line-height:1.7;white-space:pre-wrap;">' . esc_html( $appointment->message ) . '</p>';
			$body .= '</div>';
		}

		$html = self::wrap_template( $business, $body, $c );
		self::send( $admin_email, $subject, $html );
	}

	/**
	 * Send cancellation email to the client.
	 */
	public static function send_client_cancellation( $appointment ) {
		$business = get_option( 'ws_business_name', get_bloginfo( 'name' ) );
		$tz       = new DateTimeZone( wp_timezone_string() );
		$dt       = new DateTime( $appointment->slot_start, $tz );
		$date_str = self::format_date_fr( $dt );
		$time_str = $dt->format( 'H:i' );
		$c        = self::get_colors();

		$subject = sprintf( __( '[%1$s] Rendez-vous annulé — %2$s à %3$s', 'ws-scheduler' ), $business, $dt->format( 'd/m/Y' ), $time_str );

		// Custom texts (Pro) or defaults
		$greeting = get_option( 'wsp_email_cancel_greeting', '' );
		if ( empty( $greeting ) ) {
			$greeting = sprintf( __( 'Bonjour %s,', 'ws-scheduler' ), $appointment->first_name );
		} else {
			$greeting = str_replace( '{prenom}', $appointment->first_name, $greeting );
			$greeting = str_replace( '{nom}', $appointment->last_name, $greeting );
		}

		$cancel_msg = get_option( 'wsp_email_cancel_message', '' );
		if ( empty( $cancel_msg ) ) {
			$cancel_msg = sprintf( __( 'Votre rendez-vous prévu le %1$s à %2$s a été annulé.', 'ws-scheduler' ), $date_str, $time_str );
		} else {
			$cancel_msg = str_replace( '{date}', $date_str, $cancel_msg );
			$cancel_msg = str_replace( '{heure}', $time_str, $cancel_msg );
			$cancel_msg = str_replace( '{prenom}', $appointment->first_name, $cancel_msg );
		}

		$rebook = get_option( 'wsp_email_cancel_footer', '' );
		if ( empty( $rebook ) ) {
			$rebook = __( 'Si vous souhaitez reprendre rendez-vous, réservez un nouveau créneau sur notre site.', 'ws-scheduler' );
		}

		$body  = '<p style="margin:0 0 16px;color:' . esc_attr( $c['text_body'] ) . ';font-size:15px;line-height:1.7;">' . esc_html( $greeting ) . '</p>';
		$body .= '<p style="margin:0 0 20px;color:' . esc_attr( $c['text_muted'] ) . ';font-size:14px;line-height:1.7;">' . esc_html( $cancel_msg ) . '</p>';
		$body .= '<p style="margin:0;color:' . esc_attr( $c['text_muted'] ) . ';font-size:13px;line-height:1.7;">' . esc_html( $rebook ) . '</p>';

		$html = self::wrap_template( $business, $body, $c );
		self::send( $appointment->email, $subject, $html );
	}

	/**
	 * HTML email wrapper — supports custom logo and colors.
	 */
	public static function wrap_template( $business, $body_content, $c = null ) {
		if ( ! $c ) $c = self::get_colors();

		$logo_html   = self::get_logo_html( $c );
		$hide_powered = (bool) get_option( 'wsp_email_hide_powered', false );

		$footer_html = '';
		if ( ! $hide_powered ) {
			$powered = sprintf(
				esc_html__( '%1$s · Propulsé par %2$s', 'ws-scheduler' ),
				esc_html( $business ),
				'<a href="https://wordpress-freelance.com" style="color:' . esc_attr( $c['link'] ) . ';text-decoration:none;">WebStrategy</a>'
			);
			$footer_html = '<p style="margin:0;font-size:11px;color:' . esc_attr( $c['text_footer'] ) . ';">' . $powered . '</p>';
		} else {
			$footer_html = '<p style="margin:0;font-size:11px;color:' . esc_attr( $c['text_footer'] ) . ';">' . esc_html( $business ) . '</p>';
		}

		return '<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:' . esc_attr( $c['bg_outer'] ) . ';font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:' . esc_attr( $c['bg_outer'] ) . ';">
<tr><td align="center" style="padding:32px 16px;">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">
  <tr><td style="background:' . esc_attr( $c['bg_header'] ) . ';border:1px solid ' . esc_attr( $c['border'] ) . ';border-radius:12px 12px 0 0;padding:24px 32px;text-align:center;">
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
    <tr>
      <td style="vertical-align:middle;">' . $logo_html . '</td>
      <td style="padding-left:12px;font-size:16px;font-weight:600;color:' . esc_attr( $c['text_title'] ) . ';letter-spacing:-0.01em;vertical-align:middle;">' . esc_html( $business ) . '</td>
    </tr>
    </table>
  </td></tr>
  <tr><td style="background:' . esc_attr( $c['bg_body'] ) . ';border-left:1px solid ' . esc_attr( $c['border'] ) . ';border-right:1px solid ' . esc_attr( $c['border'] ) . ';padding:32px;">
    ' . $body_content . '
  </td></tr>
  <tr><td style="background:' . esc_attr( $c['bg_footer'] ) . ';border:1px solid ' . esc_attr( $c['border'] ) . ';border-radius:0 0 12px 12px;padding:20px 32px;text-align:center;">
    ' . $footer_html . '
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>';
	}

	/**
	 * Render a detail row for the email body.
	 */
	public static function detail_row( $label, $value, $c = null ) {
		if ( ! $c ) $c = self::get_colors();
		return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:1px;">
<tr>
  <td style="background:' . esc_attr( $c['bg_row'] ) . ';border:1px solid ' . esc_attr( $c['border'] ) . ';border-radius:6px 0 0 6px;padding:10px 16px;width:110px;font-size:11px;color:' . esc_attr( $c['text_label'] ) . ';text-transform:uppercase;letter-spacing:0.04em;vertical-align:middle;">' . esc_html( $label ) . '</td>
  <td style="background:' . esc_attr( $c['bg_row'] ) . ';border:1px solid ' . esc_attr( $c['border'] ) . ';border-left:none;border-radius:0 6px 6px 0;padding:10px 16px;font-size:14px;color:' . esc_attr( $c['text_value'] ) . ';vertical-align:middle;">' . $value . '</td>
</tr>
</table>';
	}

	/**
	 * Format a DateTime in localized format.
	 */
	public static function format_date_fr( $dt ) {
		$days   = array(
			__( 'Dimanche', 'ws-scheduler' ), __( 'Lundi', 'ws-scheduler' ), __( 'Mardi', 'ws-scheduler' ),
			__( 'Mercredi', 'ws-scheduler' ), __( 'Jeudi', 'ws-scheduler' ), __( 'Vendredi', 'ws-scheduler' ),
			__( 'Samedi', 'ws-scheduler' ),
		);
		$months = array( '',
			__( 'janvier', 'ws-scheduler' ), __( 'février', 'ws-scheduler' ), __( 'mars', 'ws-scheduler' ),
			__( 'avril', 'ws-scheduler' ), __( 'mai', 'ws-scheduler' ), __( 'juin', 'ws-scheduler' ),
			__( 'juillet', 'ws-scheduler' ), __( 'août', 'ws-scheduler' ), __( 'septembre', 'ws-scheduler' ),
			__( 'octobre', 'ws-scheduler' ), __( 'novembre', 'ws-scheduler' ), __( 'décembre', 'ws-scheduler' ),
		);
		return $days[ (int) $dt->format( 'w' ) ] . ' ' . $dt->format( 'd' ) . ' ' . $months[ (int) $dt->format( 'n' ) ] . ' ' . $dt->format( 'Y' );
	}

	/**
	 * Send HTML email via wp_mail.
	 */
	public static function send( $to, $subject, $html ) {
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		wp_mail( $to, $subject, $html, $headers );
	}
}
