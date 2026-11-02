<?php
/** WordPress settings and frontend embed integration. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FlakFlak_WP_Plugin {
	const OPTION = 'flakflak_wp_settings';
	const HANDLE = 'flakflak-wp-embed';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_embed' ) );
		add_filter( 'script_loader_tag', array( __CLASS__, 'script_tag' ), 10, 3 );
		add_filter( 'plugin_action_links_' . plugin_basename( FLAKFLAK_WP_FILE ), array( __CLASS__, 'settings_link' ) );
	}

	public static function settings() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), array( 'embed_code' => '', 'visibility' => 'all', 'post_ids' => array() ) );
	}

	public static function admin_menu() {
		add_options_page( 'FlakFlak', 'FlakFlak', 'manage_options', 'flakflak', array( __CLASS__, 'settings_page' ) );
	}

	public static function register_settings() {
		register_setting( 'flakflak_wp', self::OPTION, array( 'type' => 'array', 'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ), 'show_in_rest' => false ) );
		add_settings_section( 'flakflak_wp_main', '', array( __CLASS__, 'section_intro' ), 'flakflak' );
		add_settings_field( 'flakflak_embed_code', __( 'Embed code (required)', 'flakflak-wp' ), array( __CLASS__, 'embed_field' ), 'flakflak', 'flakflak_wp_main', array( 'label_for' => 'flakflak-embed-code' ) );
		add_settings_field( 'flakflak_visibility', __( 'Show FlakFlak on', 'flakflak-wp' ), array( __CLASS__, 'visibility_field' ), 'flakflak', 'flakflak_wp_main' );
	}

	public static function section_intro() {
		echo '<p>' . esc_html__( 'Paste the embed code from your FlakFlak project, then choose where feedback is available.', 'flakflak-wp' ) . '</p>';
	}

	public static function embed_field() {
		$settings = self::settings();
		echo '<textarea id="flakflak-embed-code" name="' . esc_attr( self::OPTION ) . '[embed_code]" rows="5" class="large-text code" required aria-describedby="flakflak-embed-help">' . esc_textarea( $settings['embed_code'] ) . '</textarea>';
		echo '<p id="flakflak-embed-help" class="description">' . esc_html__( 'Paste the complete script tag provided by FlakFlak. Add this website’s address to the project’s allowed website addresses in FlakFlak.', 'flakflak-wp' ) . '</p>';
	}

	public static function visibility_field() {
		$settings = self::settings();
		echo '<fieldset><legend class="screen-reader-text">' . esc_html__( 'Where FlakFlak appears', 'flakflak-wp' ) . '</legend>';
		foreach ( array( 'all' => __( 'All pages', 'flakflak-wp' ), 'specific' => __( 'Specific pages', 'flakflak-wp' ) ) as $value => $label ) {
			echo '<label><input type="radio" name="' . esc_attr( self::OPTION ) . '[visibility]" value="' . esc_attr( $value ) . '" ' . checked( $settings['visibility'], $value, false ) . '> ' . esc_html( $label ) . '</label><br>';
		}
		echo '<div id="flakflak-specific-pages" style="margin-top:12px"><label for="flakflak-post-ids">' . esc_html__( 'Post IDs', 'flakflak-wp' ) . '</label><br>';
		echo '<input id="flakflak-post-ids" name="' . esc_attr( self::OPTION ) . '[post_ids]" type="text" class="regular-text" value="' . esc_attr( implode( ', ', (array) $settings['post_ids'] ) ) . '" placeholder="123, 456, 789" aria-describedby="flakflak-post-ids-help">';
		echo '<p id="flakflak-post-ids-help" class="description">' . esc_html__( 'Enter a comma-separated list of post IDs. Works with pages, posts, and custom post types.', 'flakflak-wp' ) . '</p></div></fieldset>';
	}

	public static function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'FlakFlak Settings', 'flakflak-wp' ) . '</h1>';
		settings_errors();
		echo '<form action="options.php" method="post">';
		settings_fields( 'flakflak_wp' );
		do_settings_sections( 'flakflak' );
		submit_button();
		echo '</form></div>';
	}

	public static function admin_assets( $hook ) {
		if ( 'settings_page_flakflak' === $hook ) {
			wp_enqueue_script( 'flakflak-wp-admin', plugins_url( 'assets/admin.js', FLAKFLAK_WP_FILE ), array(), FLAKFLAK_WP_VERSION, true );
		}
	}

	public static function settings_link( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=flakflak' ) ) . '">' . esc_html__( 'Settings', 'flakflak-wp' ) . '</a>' );
		return $links;
	}

	private static function valid_url( $value ) {
		if ( ! is_string( $value ) ) {
			return false;
		}
		$url   = esc_url_raw( $value, array( 'http', 'https' ) );
		$parts = wp_parse_url( $url );
		return $url && is_array( $parts ) && ! empty( $parts['host'] ) && ! isset( $parts['user'] ) && ! isset( $parts['pass'] ) && isset( $parts['scheme'] ) && in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ? $url : false;
	}

	/** Accept one external FlakFlak script; never save arbitrary inline JavaScript. */
	public static function parse_embed( $code ) {
		if ( ! is_string( $code ) || ! preg_match( '~\A\s*<script\b[^>]*>\s*</script>\s*\z~is', $code ) ) {
			return new WP_Error( 'embed', __( 'Paste one complete FlakFlak script tag with no inline JavaScript.', 'flakflak-wp' ) );
		}
		$tag = new WP_HTML_Tag_Processor( $code );
		if ( ! $tag->next_tag( 'SCRIPT' ) ) {
			return new WP_Error( 'embed', __( 'The embed code must contain a FlakFlak script tag.', 'flakflak-wp' ) );
		}
		$src     = self::valid_url( $tag->get_attribute( 'src' ) );
		$site_id = $tag->get_attribute( 'data-site-id' );
		if ( ! $src || ! is_string( $site_id ) || ! preg_match( '/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/i', $site_id ) ) {
			return new WP_Error( 'embed', __( 'The embed code needs an HTTP/HTTPS script URL and a valid FlakFlak data-site-id. Copy it again from your project.', 'flakflak-wp' ) );
		}
		$api_url = $tag->get_attribute( 'data-api-url' );
		if ( null !== $api_url && ! self::valid_url( $api_url ) ) {
			return new WP_Error( 'embed', __( 'The optional data-api-url must be an HTTP/HTTPS URL.', 'flakflak-wp' ) );
		}
		return array( 'src' => $src, 'site_id' => $site_id, 'api_url' => null === $api_url ? '' : self::valid_url( $api_url ) );
	}

	private static function embed_html( $embed ) {
		$html = '<script src="' . esc_url( $embed['src'] ) . '" data-site-id="' . esc_attr( $embed['site_id'] ) . '"';
		if ( $embed['api_url'] ) {
			$html .= ' data-api-url="' . esc_url( $embed['api_url'] ) . '"';
		}
		return $html . ' defer></script>';
	}

	public static function sanitize_settings( $input ) {
		$old   = self::settings();
		$input = is_array( $input ) ? $input : array();
		$code  = isset( $input['embed_code'] ) && is_string( $input['embed_code'] ) ? trim( $input['embed_code'] ) : '';
		$embed = self::parse_embed( $code );
		if ( is_wp_error( $embed ) ) {
			add_settings_error( self::OPTION, 'flakflak_embed', $embed->get_error_message() . ' ' . __( 'Your previous settings were kept.', 'flakflak-wp' ) );
			return $old;
		}
		$visibility = isset( $input['visibility'] ) ? $input['visibility'] : 'all';
		if ( ! in_array( $visibility, array( 'all', 'specific' ), true ) ) {
			add_settings_error( self::OPTION, 'flakflak_visibility', __( 'Choose All pages or Specific pages. Your previous settings were kept.', 'flakflak-wp' ) );
			return $old;
		}
		$ids = array();
		if ( 'specific' === $visibility ) {
			$text = isset( $input['post_ids'] ) && is_string( $input['post_ids'] ) ? trim( $input['post_ids'] ) : '';
			if ( ! preg_match( '/\A[1-9][0-9]*(?:\s*,\s*[1-9][0-9]*)*\z/', $text ) ) {
				add_settings_error( self::OPTION, 'flakflak_ids', __( 'Enter positive post IDs separated by commas, for example 123, 456. Your previous settings were kept.', 'flakflak-wp' ) );
				return $old;
			}
			foreach ( explode( ',', $text ) as $part ) {
				$id = filter_var( trim( $part ), FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
				if ( false === $id ) {
					add_settings_error( self::OPTION, 'flakflak_ids', __( 'One of the post IDs is not valid. Your previous settings were kept.', 'flakflak-wp' ) );
					return $old;
				}
				$ids[] = $id;
			}
		}
		return array( 'embed_code' => self::embed_html( $embed ), 'visibility' => $visibility, 'post_ids' => array_values( array_unique( $ids ) ) );
	}

	public static function should_load() {
		if ( is_admin() || wp_doing_ajax() || is_feed() || is_404() || is_embed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return false;
		}
		$settings = self::settings();
		if ( is_wp_error( self::parse_embed( $settings['embed_code'] ) ) ) {
			return false;
		}
		if ( 'all' === $settings['visibility'] ) {
			return true;
		}
		return 'specific' === $settings['visibility'] && ( is_singular() || is_home() || is_front_page() ) && in_array( get_queried_object_id(), (array) $settings['post_ids'], true );
	}

	public static function enqueue_embed() {
		if ( self::should_load() ) {
			$embed = self::parse_embed( self::settings()['embed_code'] );
			wp_enqueue_script( self::HANDLE, $embed['src'], array(), null, true );
		}
	}

	public static function script_tag( $html, $handle, $src ) {
		if ( self::HANDLE !== $handle ) {
			return $html;
		}
		$embed = self::parse_embed( self::settings()['embed_code'] );
		if ( is_wp_error( $embed ) ) {
			return '';
		}
		$tag = new WP_HTML_Tag_Processor( $html );
		if ( ! $tag->next_tag( 'SCRIPT' ) ) {
			return '';
		}
		$tag->set_attribute( 'data-site-id', $embed['site_id'] );
		$tag->set_attribute( 'defer', true );
		if ( $embed['api_url'] ) {
			$tag->set_attribute( 'data-api-url', $embed['api_url'] );
		}
		return $tag->get_updated_html();
	}
}
