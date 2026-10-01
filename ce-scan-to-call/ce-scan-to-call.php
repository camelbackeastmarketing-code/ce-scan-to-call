<?php
/**
 * Plugin Name:       CE Scan-to-Call
 * Description:       Desktop visitors who click a phone (tel:) link get a QR-code popup so they can scan and call from their phone. Phones and tablets keep normal tap-to-call.
 * Version:           1.2.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Camelback East Marketing
 * Author URI:        https://camelbackeast.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ce-scan-to-call
 * Update URI:         https://github.com/camelbackeastmarketing-code/ce-scan-to-call
 *
 * Bundles qrcode-generator (c) Kazuhiko Arase, MIT License,
 * and Plugin Update Checker (c) Janis Elsts, MIT License.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CESC_VERSION', '1.2.0' );
define( 'CESC_FILE', __FILE__ );
define( 'CESC_GITHUB_REPO', 'https://github.com/camelbackeastmarketing-code/ce-scan-to-call/' );
define( 'CESC_OPTION', 'cesc_settings' );

/* ------------------------------------------------------------------------
 * Updates (from GitHub releases)
 *
 * Publishing a GitHub release with a ce-scan-to-call.zip asset makes an
 * "Update available" notice appear on every site running this plugin.
 * If the repo is ever made private, add this line to wp-config.php:
 *   define( 'CESC_GITHUB_TOKEN', 'github_pat_xxx' ); // read-only, contents:read
 * --------------------------------------------------------------------- */

require_once __DIR__ . '/lib/plugin-update-checker/plugin-update-checker.php';

add_action( 'plugins_loaded', 'cesc_init_updater' );
function cesc_init_updater() {
	if ( ! class_exists( '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
		return;
	}
	$checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		CESC_GITHUB_REPO,
		CESC_FILE,
		'ce-scan-to-call'
	);
	$checker->getVcsApi()->enableReleaseAssets();
	if ( defined( 'CESC_GITHUB_TOKEN' ) && CESC_GITHUB_TOKEN ) {
		$checker->setAuthentication( CESC_GITHUB_TOKEN );
	}
}

/* ------------------------------------------------------------------------
 * Version / upgrade routine
 *
 * Runs once after the plugin is updated. Add migrations here when a setting
 * ever changes shape. Never rename the cesc_settings option.
 * --------------------------------------------------------------------- */

add_action( 'plugins_loaded', 'cesc_maybe_upgrade' );
function cesc_maybe_upgrade() {
	$from = get_option( 'cesc_db_version', '0' );
	if ( version_compare( $from, CESC_VERSION, '>=' ) ) {
		return;
	}
	// Example for a future release:
	// if ( version_compare( $from, '1.3.0', '<' ) ) { /* migrate old setting keys */ }
	// 1.2.0 added 'show_contact_box'; it needs no migration because
	// cesc_get_settings() fills missing keys from cesc_defaults().
	update_option( 'cesc_db_version', CESC_VERSION );
}

/**
 * Default settings.
 */
function cesc_defaults() {
	return array(
		'enabled'          => 1,
		'phone_override'   => '',
		'country_code'     => '1',
		'heading'          => 'Scan to call',
		'subheading'       => '',
		'callback_label'   => 'Prefer we call you?',
		'button_text'      => 'Schedule a consultation',
		'button_url'       => '',
		'button_new_tab'   => 0,
		'bg'               => '#d9c79b',
		'text'             => '#111111',
		'button_bg'        => '#ffffff',
		'button_text_color' => '#111111',
		'overlay_color'    => '#000000',
		'overlay_opacity'  => 72,
		'font_family'      => "'Playfair Display', Georgia, 'Times New Roman', serif",
		'datalayer'        => 1,
		'show_contact_box' => 1,
	);
}

function cesc_get_settings() {
	$saved = get_option( CESC_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args( $saved, cesc_defaults() );
}

/* ------------------------------------------------------------------------
 * Front end
 * --------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', 'cesc_enqueue' );
function cesc_enqueue() {
	$s = cesc_get_settings();
	if ( empty( $s['enabled'] ) ) {
		return;
	}

	$base = plugin_dir_url( __FILE__ ) . 'assets/';
	$args = array(
		'in_footer' => true,
		'strategy'  => 'defer',
	);

	wp_register_script( 'cesc-qrcode', $base . 'qrcode.min.js', array(), CESC_VERSION, $args );
	wp_register_script( 'cesc-popup', $base . 'ce-scan-to-call.js', array( 'cesc-qrcode' ), CESC_VERSION, $args );

	list( $r, $g, $b ) = cesc_hex_to_rgb( $s['overlay_color'] );
	$alpha             = max( 0, min( 100, (int) $s['overlay_opacity'] ) ) / 100;

	$config = array(
		'phoneOverride'      => $s['phone_override'],
		'defaultCountryCode' => $s['country_code'],
		'heading'            => $s['heading'],
		'subheading'         => $s['subheading'],
		'callbackLabel'      => $s['callback_label'],
		'buttonText'         => $s['button_text'],
		'buttonUrl'          => $s['button_url'],
		'buttonNewTab'       => (bool) $s['button_new_tab'],
		'bg'                 => $s['bg'],
		'text'               => $s['text'],
		'buttonBg'           => $s['button_bg'],
		'buttonTextColor'    => $s['button_text_color'],
		'overlay'            => sprintf( 'rgba(%d,%d,%d,%s)', $r, $g, $b, $alpha ),
		'fontFamily'         => $s['font_family'],
		'dataLayerEvents'    => (bool) $s['datalayer'],
	);

	/**
	 * Filter the popup config (handy for per-site overrides in a child theme).
	 */
	$config = apply_filters( 'cesc_config', $config );

	wp_add_inline_script( 'cesc-popup', 'window.CESC_CONFIG=' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP ) . ';', 'before' );
	wp_enqueue_script( 'cesc-popup' );
}

function cesc_hex_to_rgb( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( ! preg_match( '/^[0-9a-fA-F]{6}$/', $hex ) ) {
		return array( 0, 0, 0 );
	}
	return array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
}

/* ------------------------------------------------------------------------
 * Settings page
 * --------------------------------------------------------------------- */

add_action( 'admin_menu', 'cesc_menu' );
function cesc_menu() {
	add_options_page( 'Scan-to-Call', 'Scan-to-Call', 'manage_options', 'ce-scan-to-call', 'cesc_render_page' );
}

add_action( 'admin_init', 'cesc_register' );
function cesc_register() {
	register_setting(
		'cesc_group',
		CESC_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'cesc_sanitize',
			'default'           => cesc_defaults(),
		)
	);
}

function cesc_sanitize( $in ) {
	$d   = cesc_defaults();
	$in  = is_array( $in ) ? $in : array();
	$out = array();

	$out['enabled']        = empty( $in['enabled'] ) ? 0 : 1;
	$out['button_new_tab'] = empty( $in['button_new_tab'] ) ? 0 : 1;
	$out['datalayer']      = empty( $in['datalayer'] ) ? 0 : 1;
	$out['show_contact_box'] = empty( $in['show_contact_box'] ) ? 0 : 1;

	$out['phone_override'] = isset( $in['phone_override'] ) ? preg_replace( '/[^0-9+]/', '', $in['phone_override'] ) : '';
	$out['country_code']   = isset( $in['country_code'] ) ? preg_replace( '/\D/', '', $in['country_code'] ) : '1';
	if ( '' === $out['country_code'] ) {
		$out['country_code'] = '1';
	}

	foreach ( array( 'heading', 'subheading', 'callback_label', 'button_text' ) as $k ) {
		$out[ $k ] = isset( $in[ $k ] ) ? sanitize_text_field( $in[ $k ] ) : $d[ $k ];
	}
	$out['button_url'] = isset( $in['button_url'] ) ? esc_url_raw( trim( $in['button_url'] ) ) : '';

	foreach ( array( 'bg', 'text', 'button_bg', 'button_text_color', 'overlay_color' ) as $k ) {
		$c         = isset( $in[ $k ] ) ? sanitize_hex_color( $in[ $k ] ) : '';
		$out[ $k ] = $c ? $c : $d[ $k ];
	}

	$out['overlay_opacity'] = isset( $in['overlay_opacity'] ) ? max( 0, min( 100, (int) $in['overlay_opacity'] ) ) : $d['overlay_opacity'];

	$font              = isset( $in['font_family'] ) ? preg_replace( '/[^A-Za-z0-9 ,\'"\-]/', '', $in['font_family'] ) : '';
	$out['font_family'] = '' !== trim( $font ) ? $font : $d['font_family'];

	return $out;
}

add_action( 'admin_enqueue_scripts', 'cesc_admin_assets' );
function cesc_admin_assets( $hook ) {
	if ( 'settings_page_ce-scan-to-call' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".cesc-color").wpColorPicker();});' );

	// The contact box draws its QR code in the browser with the bundled
	// library, so the settings page makes no external requests either.
	$s = cesc_get_settings();
	if ( ! empty( $s['show_contact_box'] ) && cesc_contact_box() ) {
		wp_enqueue_script( 'cesc-qrcode-admin', plugin_dir_url( CESC_FILE ) . 'assets/qrcode.min.js', array(), CESC_VERSION, true );
		wp_add_inline_script(
			'cesc-qrcode-admin',
			'(function(){var el=document.getElementById("cesc-contact-qr");if(!el||!window.CESQR){return;}var q=window.CESQR(0,"M");q.addData(el.getAttribute("data-tel"));q.make();el.innerHTML=q.createSvgTag({cellSize:4,margin:0,scalable:true});})();'
		);
	}
}

/**
 * Contact details shown in the settings-page sidebar.
 *
 * Filter `cesc_contact_box` to change them, or return an empty value to
 * remove the box entirely.
 */
function cesc_contact_box() {
	$box = array(
		'name'    => 'Camelback East Marketing',
		'url'     => 'https://camelbackeast.com',
		'phone'   => '+16027301024',
		'display' => '(602) 730-1024',
		'heading' => 'Questions about your site\'s phone links?',
		'text'    => 'Scan with your phone or call.',
	);
	$box = apply_filters( 'cesc_contact_box', $box );
	if ( empty( $box ) || ! is_array( $box ) ) {
		return array();
	}
	$box          = wp_parse_args( $box, array( 'name' => '', 'url' => '', 'phone' => '', 'display' => '', 'heading' => '', 'text' => '' ) );
	$box['phone'] = preg_replace( '/[^0-9+]/', '', (string) $box['phone'] );
	if ( '' === $box['phone'] ) {
		return array();
	}
	return $box;
}

function cesc_field_text( $key, $s, $placeholder = '', $desc = '' ) {
	printf(
		'<input type="text" class="regular-text" name="%1$s[%2$s]" value="%3$s" placeholder="%4$s" />',
		esc_attr( CESC_OPTION ),
		esc_attr( $key ),
		esc_attr( $s[ $key ] ),
		esc_attr( $placeholder )
	);
	if ( $desc ) {
		echo '<p class="description">' . esc_html( $desc ) . '</p>';
	}
}

function cesc_field_color( $key, $s ) {
	printf(
		'<input type="text" class="cesc-color" name="%1$s[%2$s]" value="%3$s" data-default-color="%3$s" />',
		esc_attr( CESC_OPTION ),
		esc_attr( $key ),
		esc_attr( $s[ $key ] )
	);
}

function cesc_field_check( $key, $s, $label ) {
	printf(
		'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s /> %4$s</label>',
		esc_attr( CESC_OPTION ),
		esc_attr( $key ),
		checked( ! empty( $s[ $key ] ), true, false ),
		esc_html( $label )
	);
}

function cesc_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s        = cesc_get_settings();
	$test_url = add_query_arg( 'cesc_test', '1', home_url( '/' ) );
	?>
	<div class="wrap">
		<h1>Scan-to-Call <small style="font-size:13px;color:#646970;">v<?php echo esc_html( CESC_VERSION ); ?></small></h1>
		<p>When a desktop visitor clicks any phone (<code>tel:</code>) link on your site, a popup shows a QR code they can scan with their phone to place the call. Phones and tablets keep normal tap-to-call.</p>
		<p><a class="button" href="<?php echo esc_url( $test_url ); ?>" target="_blank" rel="noopener">Open site in test mode</a>
		<span class="description">Test mode forces the popup on any device. Click a phone link on the page that opens.</span></p>

		<style>
			.cesc-layout { display: flex; gap: 24px; align-items: flex-start; }
			.cesc-main { flex: 1 1 auto; min-width: 0; }
			.cesc-side { flex: 0 0 300px; max-width: 300px; }
			.cesc-card { background: #fff; border: 1px solid #c3c4c7; padding: 4px 16px 14px; margin-bottom: 16px; }
			.cesc-card h2 { font-size: 14px; margin: 14px 0 6px; padding: 0; }
			.cesc-card ul { margin: 0 0 8px 18px; list-style: disc; }
			.cesc-card li { margin-bottom: 6px; }
			.cesc-card .cesc-note { background: #f6f7f7; border-left: 4px solid #2271b1; margin: 12px 0 8px; padding: 8px 10px; font-weight: 600; }
			.cesc-card .cesc-fine { color: #646970; font-size: 12px; margin: 8px 0 0; }
			.cesc-qr { width: 140px; height: 140px; margin: 10px 0 6px; }
			.cesc-qr svg { width: 100%; height: 100%; display: block; }
			@media ( max-width: 1100px ) {
				.cesc-layout { display: block; }
				.cesc-side { max-width: 480px; }
			}
		</style>

		<div class="cesc-layout">
		<div class="cesc-main">
		<form method="post" action="options.php">
			<?php settings_fields( 'cesc_group' ); ?>

			<h2 class="title">General</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Status</th><td><?php cesc_field_check( 'enabled', $s, 'Enable the popup site-wide' ); ?></td></tr>
				<tr><th scope="row">Popup number override (display only)</th><td><?php cesc_field_text( 'phone_override', $s, '+16027301024', 'Leave blank to use whichever phone link the visitor clicked. If filled in, the popup always shows this number. It does not change your pages or what a phone dials, so fix the phone links on your site first.' ); ?></td></tr>
				<tr><th scope="row">Default country code</th><td><?php cesc_field_text( 'country_code', $s, '1', 'Added to 10-digit numbers with no country code. US/Canada = 1.' ); ?></td></tr>
				<tr><th scope="row">Analytics</th><td><?php cesc_field_check( 'datalayer', $s, 'Push events to the GTM/GA4 dataLayer (scan_to_call_open, scan_to_call_close, scan_to_call_schedule_click)' ); ?></td></tr>
				<tr><th scope="row">Contact box</th><td><?php cesc_field_check( 'show_contact_box', $s, 'Show the Camelback East contact box in the sidebar of this page' ); ?></td></tr>
			</table>

			<h2 class="title">Text &amp; button</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Heading</th><td><?php cesc_field_text( 'heading', $s ); ?></td></tr>
				<tr><th scope="row">Line under heading</th><td><?php cesc_field_text( 'subheading', $s, '', 'Optional.' ); ?></td></tr>
				<tr><th scope="row">Button label above button</th><td><?php cesc_field_text( 'callback_label', $s ); ?></td></tr>
				<tr><th scope="row">Button text</th><td><?php cesc_field_text( 'button_text', $s ); ?></td></tr>
				<tr><th scope="row">Button link</th><td><?php cesc_field_text( 'button_url', $s, '/contact/', 'Leave blank to hide the button and the label above it.' ); ?><br /><?php cesc_field_check( 'button_new_tab', $s, 'Open in a new tab' ); ?></td></tr>
			</table>

			<h2 class="title">Design</h2>
			<table class="form-table" role="presentation">
				<tr><th scope="row">Popup background</th><td><?php cesc_field_color( 'bg', $s ); ?></td></tr>
				<tr><th scope="row">Text color</th><td><?php cesc_field_color( 'text', $s ); ?></td></tr>
				<tr><th scope="row">Button background</th><td><?php cesc_field_color( 'button_bg', $s ); ?></td></tr>
				<tr><th scope="row">Button text color</th><td><?php cesc_field_color( 'button_text_color', $s ); ?></td></tr>
				<tr><th scope="row">Page dim color</th><td><?php cesc_field_color( 'overlay_color', $s ); ?></td></tr>
				<tr><th scope="row">Page dim opacity (0-100)</th><td><input type="number" min="0" max="100" name="<?php echo esc_attr( CESC_OPTION ); ?>[overlay_opacity]" value="<?php echo esc_attr( $s['overlay_opacity'] ); ?>" /></td></tr>
				<tr><th scope="row">Font family</th><td><?php cesc_field_text( 'font_family', $s, '', 'CSS font stack. Uses a font only if your theme already loads it (Playfair Display falls back to Georgia).' ); ?></td></tr>
			</table>

			<?php submit_button(); ?>
		</form>
		<p class="description">Updates arrive through the normal Plugins screen. Use "Check for updates" next to this plugin there to check right now.</p>
		</div>

		<aside class="cesc-side">
			<div class="cesc-card">
				<h2>What it does</h2>
				<ul>
					<li>On a desktop, clicking a phone (<code>tel:</code>) link opens a popup with a QR code. The visitor scans it and calls from their phone.</li>
					<li>Phones and tablets keep normal tap-to-call.</li>
					<li>Optionally sends events to GTM/GA4 when the popup opens, closes, or its button is clicked.</li>
				</ul>
				<h2>What it doesn't do</h2>
				<ul>
					<li>It doesn't fix a broken or partial phone link. On a phone, the button still dials whatever the link says.</li>
					<li>It doesn't change the numbers on your pages. The number override only changes the number shown in the popup.</li>
					<li>It doesn't choose your number. If the site shows several numbers and you want one, fix them on the site itself.</li>
				</ul>
				<p class="cesc-note">Fix the site first. This plugin is a safety net, not a substitute.</p>
				<p class="cesc-fine">Free software under the GPL-2.0-or-later, provided as-is with no warranty.</p>
			</div>
			<?php
			$box = ! empty( $s['show_contact_box'] ) ? cesc_contact_box() : array();
			if ( $box ) :
				?>
				<div class="cesc-card">
					<h2><?php echo $box['url'] ? '<a href="' . esc_url( $box['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $box['name'] ) . '</a>' : esc_html( $box['name'] ); ?></h2>
					<?php if ( $box['heading'] ) : ?>
						<p style="margin:6px 0;"><?php echo esc_html( $box['heading'] ); ?></p>
					<?php endif; ?>
					<div id="cesc-contact-qr" class="cesc-qr" role="img" aria-label="<?php echo esc_attr( 'QR code to call ' . ( $box['display'] ? $box['display'] : $box['phone'] ) ); ?>" data-tel="<?php echo esc_attr( 'tel:' . $box['phone'] ); ?>"></div>
					<p style="margin:0 0 4px;"><a href="<?php echo esc_attr( 'tel:' . $box['phone'] ); ?>"><?php echo esc_html( $box['display'] ? $box['display'] : $box['phone'] ); ?></a></p>
					<?php if ( $box['text'] ) : ?>
						<p class="cesc-fine" style="margin-top:0;"><?php echo esc_html( $box['text'] ); ?></p>
					<?php endif; ?>
					<p class="cesc-fine">You can hide this box under General settings.</p>
				</div>
			<?php endif; ?>
		</aside>
		</div>
	</div>
	<?php
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'cesc_action_links' );
function cesc_action_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=ce-scan-to-call' ) ) . '">Settings</a>' );
	return $links;
}
