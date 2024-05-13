<?php

class PKBKG_BookingForm {

	const post_type = 'pkbkg_booking_form';

	private static $found_items = 0;
	private static $current = null;

	private $id;
	private $name;
	private $title;
	private $properties = array();
	private $unit_tag;
	private $responses_count = 0;
	private $scanned_form_tags;

	public static function count() {
		return self::$found_items;
	}

	public static function get_current() {
		return self::$current;
	}

	public static function register_post_type() {
		register_post_type( self::post_type, array(
			'labels' => array(
				'name' => __( 'Booking Forms', 'parking-booking' ),
				'singular_name' => __( 'Booking Form', 'parking-booking' ) ),
			'rewrite' => false,
			'query_var' => false ) );
	}

	public static function find( $args = '' ) {
		$defaults = array(
			'post_status' => 'any',
			'posts_per_page' => -1,
			'offset' => 0,
			'orderby' => 'ID',
			'order' => 'ASC' );

		$args = wp_parse_args( $args, $defaults );

		$args['post_type'] = self::post_type;

		$q = new WP_Query();
		$posts = $q->query( $args );

		self::$found_items = $q->found_posts;

		$objs = array();

		foreach ( (array) $posts as $post )
			$objs[] = new self( $post );

		return $objs;
	}

	public static function get_template( $args = '' ) {
		global $l10n;

		$defaults = array( 'locale' => null, 'title' => '' );
		$args = wp_parse_args( $args, $defaults );

		$locale = $args['locale'];
		$title = $args['title'];

		if ( $locale ) {
			$mo_orig = $l10n['parking-booking'];
			pkbkg_load_textdomain( $locale );
		}

		self::$current = $booking_form = new self;
		$booking_form->title =
			( $title ? $title : __( 'Untitled', 'parking-booking' ) );
		$booking_form->locale = ( $locale ? $locale : get_locale() );

		$properties = $booking_form->get_properties();

		foreach ( $properties as $key => $value ) {
			$properties[$key] = pkbkg_get_default_template( $key );
		}

		$booking_form->properties = $properties;

		$booking_form = apply_filters( 'pkbkg_booking_form_default_pack',
			$booking_form, $args );

		if ( isset( $mo_orig ) ) {
			$l10n['parking-booking'] = $mo_orig;
		}

		return $booking_form;
	}

	public static function get_instance( $post ) {
		$post = get_post( $post );

		if ( ! $post || self::post_type != get_post_type( $post ) ) {
			return false;
		}

		self::$current = $booking_form = new self( $post );

		return $booking_form;
	}

	private static function get_unit_tag( $id = 0 ) {
		static $global_count = 0;

		$global_count += 1;

		if ( in_the_loop() ) {
			$unit_tag = sprintf( 'pkbkg-f%1$d-p%2$d-o%3$d',
				absint( $id ), get_the_ID(), $global_count );
		} else {
			$unit_tag = sprintf( 'pkbkg-f%1$d-o%2$d',
				absint( $id ), $global_count );
		}

		return $unit_tag;
	}

	private function __construct( $post = null ) {
		$post = get_post( $post );

		if ( $post && self::post_type == get_post_type( $post ) ) {
			$this->id = $post->ID;
			$this->name = $post->post_name;
			$this->title = $post->post_title;
			$this->locale = get_post_meta( $post->ID, '_locale', true );

			$properties = $this->get_properties();

			foreach ( $properties as $key => $value ) {
				if ( metadata_exists( 'post', $post->ID, '_' . $key ) ) {
					$properties[$key] = get_post_meta( $post->ID, '_' . $key, true );
				} elseif ( metadata_exists( 'post', $post->ID, $key ) ) {
					$properties[$key] = get_post_meta( $post->ID, $key, true );
				}
			}

			$this->properties = $properties;
			$this->upgrade();
		}

		do_action( 'pkbkg_booking_form', $this );
	}

	public function __get( $name ) {
		$message = __( '<code>%1$s</code> property of a <code>PKBKG_BookingForm</code> object is <strong>no longer accessible</strong>. Use <code>%2$s</code> method instead.', 'parking-booking' );

		if ( 'id' == $name ) {
			if ( WP_DEBUG ) {
				trigger_error( sprintf( $message, 'id', 'id()' ) );
			}

			return $this->id;
		} elseif ( 'title' == $name ) {
			if ( WP_DEBUG ) {
				trigger_error( sprintf( $message, 'title', 'title()' ) );
			}

			return $this->title;
		} elseif ( $prop = $this->prop( $name ) ) {
			if ( WP_DEBUG ) {
				trigger_error(
					sprintf( $message, $name, 'prop(\'' . $name . '\')' ) );
			}

			return $prop;
		}
	}

	public function initial() {
		return empty( $this->id );
	}

	public function prop( $name ) {
		$props = $this->get_properties();
		return isset( $props[$name] ) ? $props[$name] : null;
	}

	public function get_properties() {
		$properties = (array) $this->properties;

		$properties = wp_parse_args( $properties, array(
			'form' => '',
			'mail' => array(),
			'database' => array(),
			'messages' => array(),
			'additional_settings' => '' ) );

		$properties = (array) apply_filters( 'pkbkg_booking_form_properties',
			$properties, $this );

		return $properties;
	}

	public function set_properties( $properties ) {
		$defaults = $this->get_properties();

		$properties = wp_parse_args( $properties, $defaults );
		$properties = array_intersect_key( $properties, $defaults );

		$this->properties = $properties;
	}

	public function id() {
		return $this->id;
	}

	public function name() {
		return $this->name;
	}

	public function title() {
		return $this->title;
	}

	public function set_title( $title ) {
		$title = trim( $title );

		if ( '' === $title ) {
			$title = __( 'Untitled', 'parking-booking' );
		}

		$this->title = $title;
	}

	// Return true if this form is the same one as currently POSTed.
	public function is_posted() {
		if ( ! PKBKG_Submission::get_instance() ) {
			return false;
		}

		if ( empty( $_POST['_pkbkg_unit_tag'] ) ) {
			return false;
		}

		return $this->unit_tag == $_POST['_pkbkg_unit_tag'];
	}

	/* Generating Form HTML */

	public function form_html( $atts = array() ) {
		$atts = wp_parse_args( $atts, array(
			'html_id' => '',
			'html_name' => '',
			'html_class' => '',
			'output' => 'form' ) );

		if ( 'raw_form' == $atts['output'] ) {
			return '<pre class="pkbkg-raw-form"><code>'
				. esc_html( $this->prop( 'form' ) ) . '</code></pre>';
		}

		$this->unit_tag = self::get_unit_tag( $this->id );

		$html = sprintf( '<div %s>', pkbkg_format_atts( array(
			'class' => 'pkbkg',
			'id' => $this->unit_tag,
			( get_option( 'html_type' ) == 'text/html' ) ? 'lang' : 'xml:lang'
				=> str_replace( '_', '-', $this->locale ),
			'dir' => pkbkg_is_rtl( $this->locale ) ? 'rtl' : 'ltr' ) ) ) . "\n";

		$html .= $this->screen_reader_response() . "\n";

		$url = pkbkg_get_request_uri();

		if ( $frag = strstr( $url, '#' ) )
			$url = substr( $url, 0, -strlen( $frag ) );

		$url .= '#' . $this->unit_tag;

		$url = apply_filters( 'pkbkg_form_action_url', $url );

		$id_attr = apply_filters( 'pkbkg_form_id_attr',
			preg_replace( '/[^A-Za-z0-9:._-]/', '', $atts['html_id'] ) );

		$name_attr = apply_filters( 'pkbkg_form_name_attr',
			preg_replace( '/[^A-Za-z0-9:._-]/', '', $atts['html_name'] ) );

		$class = 'pkbkg-form';

		if ( $this->is_posted() ) {
			$submission = PKBKG_Submission::get_instance();

			if ( $submission->is( 'validation_failed' ) ) {
				$class .= ' invalid';
			} elseif ( $submission->is( 'spam' ) ) {
				$class .= ' spam';
			} elseif ( $submission->is( 'mail_sent' ) ) {
				$class .= ' sent';
			} elseif ( $submission->is( 'mail_failed' ) ) {
				$class .= ' failed';
			}
		}

		if ( $atts['html_class'] ) {
			$class .= ' ' . $atts['html_class'];
		}

		if ( $this->in_demo_mode() ) {
			$class .= ' demo';
		}

		$class = explode( ' ', $class );
		$class = array_map( 'sanitize_html_class', $class );
		$class = array_filter( $class );
		$class = array_unique( $class );
		$class = implode( ' ', $class );
		$class = apply_filters( 'pkbkg_form_class_attr', $class );

		$enctype = apply_filters( 'pkbkg_form_enctype', '' );

		$novalidate = apply_filters( 'pkbkg_form_novalidate', pkbkg_support_html5() );

		$html .= sprintf( '<form %s>',
			pkbkg_format_atts( array(
				'action' => esc_url( $url ),
				'method' => 'post',
				'id' => $id_attr,
				'name' => $name_attr,
				'class' => $class,
				'enctype' => pkbkg_enctype_value( $enctype ),
				'novalidate' => $novalidate ? 'novalidate' : '' ) ) ) . "\n";

		$html .= $this->form_hidden_fields();
		$html .= $this->form_elements();

		if ( ! $this->responses_count ) {
			$html .= $this->form_response_output();
		}

		$html .= '</form>';
		$html .= '</div>';

		return $html;
	}

	private function form_hidden_fields() {
		$hidden_fields = array(
			'_pkbkg' => $this->id,
			'_pkbkg_version' => PKBKG_VERSION,
			'_pkbkg_locale' => $this->locale,
			'_pkbkg_unit_tag' => $this->unit_tag );

		if ( PKBKG_VERIFY_NONCE )
			$hidden_fields['_wpnonce'] = pkbkg_create_nonce( $this->id );

		$content = '';

		foreach ( $hidden_fields as $name => $value ) {
			$content .= '<input type="hidden"'
				. ' name="' . esc_attr( $name ) . '"'
				. ' value="' . esc_attr( $value ) . '" />' . "\n";
		}

		return '<div style="display: none;">' . "\n" . $content . '</div>' . "\n";
	}

	public function form_response_output() {
		$class = 'pkbkg-response-output';
		$role = '';
		$content = '';

		if ( $this->is_posted() ) { // Post response output for non-AJAX
			$role = 'alert';

			$submission = PKBKG_Submission::get_instance();
			$content = $submission->get_response();

			if ( $submission->is( 'validation_failed' ) ) {
				$class .= ' pkbkg-validation-errors';
			} elseif ( $submission->is( 'spam' ) ) {
				$class .= ' pkbkg-spam-blocked';
			} elseif ( $submission->is( 'mail_sent' ) ) {
				$class .= ' pkbkg-mail-sent-ok';
			} elseif ( $submission->is( 'mail_failed' ) ) {
				$class .= ' pkbkg-mail-sent-ng';
			}
		} else {
			$class .= ' pkbkg-display-none';
		}

		$atts = array(
			'class' => trim( $class ),
			'role' => trim( $role ) );

		$atts = pkbkg_format_atts( $atts );

		$output = sprintf( '<div %1$s>%2$s</div>',
			$atts, esc_html( $content ) );

		$output = apply_filters( 'pkbkg_form_response_output',
			$output, $class, $content, $this );

		$this->responses_count += 1;

		return $output;
	}

	public function screen_reader_response() {
		$class = 'screen-reader-response';
		$role = '';
		$content = '';

		if ( $this->is_posted() ) { // Post response output for non-AJAX
			$role = 'alert';

			$submission = PKBKG_Submission::get_instance();

			if ( $response = $submission->get_response() ) {
				$content = esc_html( $response );
			}

			if ( $invalid_fields = $submission->get_invalid_fields() ) {
				$content .= "\n" . '<ul>' . "\n";

				foreach ( (array) $invalid_fields as $name => $field ) {
					if ( $field['idref'] ) {
						$link = sprintf( '<a href="#%1$s">%2$s</a>',
							esc_attr( $field['idref'] ),
							esc_html( $field['reason'] ) );
						$content .= sprintf( '<li>%s</li>', $link );
					} else {
						$content .= sprintf( '<li>%s</li>',
							esc_html( $field['reason'] ) );
					}

					$content .= "\n";
				}

				$content .= '</ul>' . "\n";
			}
		}

		$atts = array(
			'class' => trim( $class ),
			'role' => trim( $role ) );

		$atts = pkbkg_format_atts( $atts );

		$output = sprintf( '<div %1$s>%2$s</div>',
			$atts, $content );

		return $output;
	}

	public function validation_error( $name ) {
		$error = '';

		if ( $this->is_posted() ) {
			$submission = PKBKG_Submission::get_instance();

			if ( $invalid_field = $submission->get_invalid_field( $name ) ) {
				$error = trim( $invalid_field['reason'] );
			}
		}

		if ( ! $error ) {
			return $error;
		}

		$error = sprintf(
			'<span role="alert" class="pkbkg-not-valid-tip">%s</span>',
			esc_html( $error ) );

		return apply_filters( 'pkbkg_validation_error', $error, $name, $this );
	}

	/* Form Elements */

	public function form_do_shortcode() {
		$manager = PKBKG_ShortcodeManager::get_instance();
		$form = $this->prop( 'form' );

		if ( PKBKG_AUTOP ) {
			$form = $manager->normalize_shortcode( $form );
			$form = pkbkg_autop( $form );
		}

		$form = $manager->do_shortcode( $form );
		$this->scanned_form_tags = $manager->get_scanned_tags();

		return $form;
	}

	public function form_scan_shortcode( $cond = null ) {
		$manager = PKBKG_ShortcodeManager::get_instance();

		if ( ! empty( $this->scanned_form_tags ) ) {
			$scanned = $this->scanned_form_tags;
		} else {
			$scanned = $manager->scan_shortcode( $this->prop( 'form' ) );
			$this->scanned_form_tags = $scanned;
		}

		if ( empty( $scanned ) )
			return null;

		if ( ! is_array( $cond ) || empty( $cond ) )
			return $scanned;

		for ( $i = 0, $size = count( $scanned ); $i < $size; $i++ ) {

			if ( isset( $cond['type'] ) ) {
				if ( is_string( $cond['type'] ) && ! empty( $cond['type'] ) ) {
					if ( $scanned[$i]['type'] != $cond['type'] ) {
						unset( $scanned[$i] );
						continue;
					}
				} elseif ( is_array( $cond['type'] ) ) {
					if ( ! in_array( $scanned[$i]['type'], $cond['type'] ) ) {
						unset( $scanned[$i] );
						continue;
					}
				}
			}

			if ( isset( $cond['name'] ) ) {
				if ( is_string( $cond['name'] ) && ! empty( $cond['name'] ) ) {
					if ( $scanned[$i]['name'] != $cond['name'] ) {
						unset ( $scanned[$i] );
						continue;
					}
				} elseif ( is_array( $cond['name'] ) ) {
					if ( ! in_array( $scanned[$i]['name'], $cond['name'] ) ) {
						unset( $scanned[$i] );
						continue;
					}
				}
			}
		}

		return array_values( $scanned );
	}

	public function form_elements() {
		return apply_filters( 'pkbkg_form_elements', $this->form_do_shortcode() );
	}

	public function submit( $ajax = false ) {
		$submission = PKBKG_Submission::get_instance( $this );

		$result = array(
			'status' => $submission->get_status(),
			'message' => $submission->get_response(),
			'demo_mode' => $this->in_demo_mode() );

		if ( $submission->is( 'validation_failed' ) ) {
			$result['invalid_fields'] = $submission->get_invalid_fields();
		}

		if ( $submission->is( 'mail_sent' ) ) {
			if ( $ajax ) {
				$on_sent_ok = $this->additional_setting( 'on_sent_ok', false );

				if ( ! empty( $on_sent_ok ) ) {
					$result['scripts_on_sent_ok'] = array_map(
						'pkbkg_strip_quote', $on_sent_ok );
				}
			}
		}

		if ( $ajax ) {
			$on_submit = $this->additional_setting( 'on_submit', false );

			if ( ! empty( $on_submit ) ) {
				$result['scripts_on_submit'] = array_map(
					'pkbkg_strip_quote', $on_submit );
			}
		}

		do_action( 'pkbkg_submit', $this, $result );

		return $result;
	}

	/* Message */

	public function message( $status, $filter = true ) {
		$messages = $this->prop( 'messages' );
		$message = isset( $messages[$status] ) ? $messages[$status] : '';

		if ( $filter ) {
			$message = pkbkg_mail_replace_tags( $message, array( 'html' => true ) );
			$message = apply_filters( 'pkbkg_display_message', $message, $status );
		}

		return $message;
	}

	/* Additional settings */

	public function additional_setting( $name, $max = 1 ) {
		$tmp_settings = (array) explode( "\n", $this->prop( 'additional_settings' ) );

		$count = 0;
		$values = array();

		foreach ( $tmp_settings as $setting ) {
			if ( preg_match('/^([a-zA-Z0-9_]+)[\t ]*:(.*)$/', $setting, $matches ) ) {
				if ( $matches[1] != $name )
					continue;

				if ( ! $max || $count < (int) $max ) {
					$values[] = trim( $matches[2] );
					$count += 1;
				}
			}
		}

		return $values;
	}

	public function is_true( $name ) {
		$settings = $this->additional_setting( $name, false );

		foreach ( $settings as $setting ) {
			if ( in_array( $setting, array( 'on', 'true', '1' ) ) )
				return true;
		}

		return false;
	}

	public function in_demo_mode() {
		return $this->is_true( 'demo_mode' );
	}

	/* Upgrade */

	private function upgrade() {
		$mail = $this->prop( 'mail' );

		if ( is_array( $mail ) && ! isset( $mail['recipient'] ) ) {
			$mail['recipient'] = get_option( 'admin_email' );
		}

		$this->properties['mail'] = $mail;

		$messages = $this->prop( 'messages' );

		if ( is_array( $messages ) ) {
			foreach ( pkbkg_messages() as $key => $arr ) {
				if ( ! isset( $messages[$key] ) ) {
					$messages[$key] = $arr['default'];
				}
			}
		}

		$this->properties['messages'] = $messages;
	}

	/* Save */

	public function save() {
		$props = $this->get_properties();

		$post_content = implode( "\n", pkbkg_array_flatten( $props ) );

		if ( $this->initial() ) {
			$post_id = wp_insert_post( array(
				'post_type' => self::post_type,
				'post_status' => 'publish',
				'post_title' => $this->title,
				'post_content' => trim( $post_content ) ) );
		} else {
			$post_id = wp_update_post( array(
				'ID' => (int) $this->id,
				'post_status' => 'publish',
				'post_title' => $this->title,
				'post_content' => trim( $post_content ) ) );
		}

		if ( $post_id ) {
			foreach ( $props as $prop => $value ) {
				update_post_meta( $post_id, '_' . $prop,
					pkbkg_normalize_newline_deep( $value ) );
			}

			if ( pkbkg_is_valid_locale( $this->locale ) ) {
				update_post_meta( $post_id, '_locale', $this->locale );
			}

			if ( $this->initial() ) {
				$this->id = $post_id;
				do_action( 'pkbkg_after_create', $this );
			} else {
				do_action( 'pkbkg_after_update', $this );
			}

			do_action( 'pkbkg_after_save', $this );
		}

		return $post_id;
	}

	public function copy() {
		$new = new self;
		$new->title = $this->title . '_copy';
		$new->locale = $this->locale;
		$new->properties = $this->properties;

		return apply_filters( 'pkbkg_copy', $new, $this );
	}

	public function delete() {
		if ( $this->initial() )
			return;

		if ( wp_delete_post( $this->id, true ) ) {
			$this->id = 0;
			return true;
		}

		return false;
	}
}

function pkbkg_booking_form( $id ) {
	return PKBKG_BookingForm::get_instance( $id );
}

function pkbkg_get_booking_form_by_old_id( $old_id ) {
	global $wpdb;

	$q = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_old_cf7_unit_id'"
		. $wpdb->prepare( " AND meta_value = %d", $old_id );

	if ( $new_id = $wpdb->get_var( $q ) )
		return pkbkg_booking_form( $new_id );
}

function pkbkg_get_booking_form_by_title( $title ) {
	$page = get_page_by_title( $title, OBJECT, PKBKG_BookingForm::post_type );

	if ( $page )
		return pkbkg_booking_form( $page->ID );

	return null;
}

function pkbkg_get_current_booking_form() {
	if ( $current = PKBKG_BookingForm::get_current() ) {
		return $current;
	}
}

function pkbkg_is_posted() {
	if ( ! $booking_form = pkbkg_get_current_booking_form() )
		return false;

	return $booking_form->is_posted();
}

function pkbkg_get_hangover( $name, $default = null ) {
	if ( ! pkbkg_is_posted() ) {
		return $default;
	}

	$submission = PKBKG_Submission::get_instance();

	if ( ! $submission || $submission->is( 'mail_sent' ) ) {
		return $default;
	}

	return isset( $_POST[$name] ) ? wp_unslash( $_POST[$name] ) : $default;
}

function pkbkg_get_validation_error( $name ) {
	if ( ! $booking_form = pkbkg_get_current_booking_form() )
		return '';

	return $booking_form->validation_error( $name );
}

function pkbkg_get_message( $status ) {
	if ( ! $booking_form = pkbkg_get_current_booking_form() )
		return '';

	return $booking_form->message( $status );
}

function pkbkg_scan_shortcode( $cond = null ) {
	if ( ! $booking_form = pkbkg_get_current_booking_form() )
		return null;

	return $booking_form->form_scan_shortcode( $cond );
}

function pkbkg_form_controls_class( $type, $default = '' ) {
	$type = trim( $type );
	$default = array_filter( explode( ' ', $default ) );

	$classes = array_merge( array( 'pkbkg-form-control' ), $default );

	$typebase = rtrim( $type, '*' );
	$required = ( '*' == substr( $type, -1 ) );

	$classes[] = 'pkbkg-' . $typebase;

	if ( $required )
		$classes[] = 'pkbkg-validates-as-required';

	$classes = array_unique( $classes );

	return implode( ' ', $classes );
}

function pkbkg_get_default_template( $prop = 'form' ) {
	if ( 'form' == $prop )
		$template = pkbkg_default_form_template();
	elseif ( 'mail' == $prop )
		$template = pkbkg_default_mail_template();
	elseif ( 'database' == $prop )
		$template = pkbkg_default_database_template();
	elseif ( 'messages' == $prop )
		$template = pkbkg_default_messages_template();
	else
		$template = null;

	return apply_filters( 'pkbkg_default_template', $template, $prop );
}

function pkbkg_default_form_template() {
	$template =
		'<p>' . __( 'Your Name', 'parking-booking' ) . ' ' . __( '(required)', 'parking-booking' ) . '<br />' . "\n"
		. '    [text* your-name] </p>' . "\n\n"
		. '<p>' . __( 'Your Email', 'parking-booking' ) . ' ' . __( '(required)', 'parking-booking' ) . '<br />' . "\n"
		. '    [email* your-email] </p>' . "\n\n"
		. '<p>' . __( 'Subject', 'parking-booking' ) . '<br />' . "\n"
		. '    [text your-subject] </p>' . "\n\n"
		. '<p>' . __( 'Your Message', 'parking-booking' ) . '<br />' . "\n"
		. '    [textarea your-message] </p>' . "\n\n"
		. '<p>[submit "' . __( 'Send', 'parking-booking' ) . '"]</p>';

	return $template;
}

function pkbkg_default_mail_template() {
	$subject = '[your-subject]';
	$sender = '[your-name] <[your-email]>';
	$body = sprintf( __( 'From: %s', 'parking-booking' ), '[your-name] <[your-email]>' ) . "\n"
		. sprintf( __( 'Subject: %s', 'parking-booking' ), '[your-subject]' ) . "\n\n"
		. __( 'Message Body:', 'parking-booking' ) . "\n" . '[your-message]' . "\n\n" . '--' . "\n"
		. sprintf( __( 'This e-mail was sent from a contact form on %1$s (%2$s)', 'parking-booking' ),
			get_bloginfo( 'name' ), get_bloginfo( 'url' ) );
	$recipient = get_option( 'admin_email' );
	$additional_headers = '';
	$attachments = '';
	$use_html = 0;
	$exclude_blank = 0;
	return compact( 'subject', 'sender', 'body', 'recipient', 'additional_headers', 'attachments', 'use_html', 'exclude_blank' );
}

function pkbkg_default_database_template() {
	$dbname = "database Name";
	$dbhost = "database Host";
	$dbuser = "database User"; 
	$dbpassword = "database password";
	$dbport = 3600;
	$table_reservation = 'table_reservation';
	$table_services = 'table_services';
	$table_tarifs_basse = 'table_tarifs_basse';
	$table_tarif_resa = 'table_tarif_resa';
	$table_tarifs_int = 'table_tarifs_int';
	$start_date_price_save = 'start_date_price_save';
	$end_date_price_save = 'end_date_price_save';
	$table_tarifs_ext = 'table_tarifs_ext';
	$table_tarifs_eco = 'table_tarifs_eco';
	$table_users = 'table_users';
	return compact( 'dbname','dbhost','dbuser','dbpassword',
	'dbport','table_reservation','table_services','table_tarifs_basse',
	'table_tarif_resa','table_tarifs_int','start_date_price_save','end_date_price_save',
	'table_tarifs_ext','table_tarifs_eco','table_users' );
}

function pkbkg_default_messages_template() {
	$messages = array();

	foreach ( pkbkg_messages() as $key => $arr ) {
		$messages[$key] = $arr['default'];
	}

	return $messages;
}

function pkbkg_messages() {
	$messages = array(
		'mail_sent_ok' => array(
			'description' => __( "Sender's message was sent successfully", 'parking-booking' ),
			'default' => __( 'Your message was sent successfully. Thanks.', 'parking-booking' )
		),

		'mail_sent_ng' => array(
			'description' => __( "Sender's message was failed to send", 'parking-booking' ),
			'default' => __( 'Failed to send your message. Please try later or contact the administrator by another method.', 'parking-booking' )
		),

		'validation_error' => array(
			'description' => __( "Validation errors occurred", 'parking-booking' ),
			'default' => __( 'Validation errors occurred. Please confirm the fields and submit it again.', 'parking-booking' )
		),

		'spam' => array(
			'description' => __( "Submission was referred to as spam", 'parking-booking' ),
			'default' => __( 'Failed to send your message. Please try later or contact the administrator by another method.', 'parking-booking' )
		),

		'accept_terms' => array(
			'description' => __( "There are terms that the sender must accept", 'parking-booking' ),
			'default' => __( 'Please accept the terms to proceed.', 'parking-booking' )
		),

		'invalid_required' => array(
			'description' => __( "There is a field that the sender must fill in", 'parking-booking' ),
			'default' => __( 'Please fill the required field.', 'parking-booking' )
		)
	);

	return apply_filters( 'pkbkg_messages', $messages );
}

?>