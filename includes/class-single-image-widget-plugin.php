<?php
/**
 * single image Widget
 *
 * @package   SingleImageWidget
 * @copyright Copyright (c) 2016 Saucer Web Solution
 * @license   GPL-2.0+
 * @since     3.0.0
 */

/**
 * The main plugin class for loading the widget and attaching hooks.
 *
 * @package SingleImageWidget
 * @since   3.0.0
 */
class Single_Image_Widget_Plugin {
	/**
	 * Set up the widget.
	 *
	 * @since 3.0.0
	 */
	public function load() {
		self::load_textdomain();
		add_action( 'widgets_init', array( $this, 'register_widget' ) );

		$compat = new Single_Image_Widget_Legacy();
		$compat->load();

		if ( is_Single_Image_Widget_legacy() ) {
			return;
		}

		add_action( 'init', array( $this, 'register_assets' ) );
		add_action( 'sidebar_admin_setup', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'screen_settings', array( $this, 'widgets_screen_settings' ), 10, 2 );
		add_action( 'wp_ajax_Single_Image_Widget_find_posts', array( $this, 'ajax_find_posts' ) );
		add_action( 'wp_ajax_Single_Image_Widget_preferences', array( $this, 'ajax_save_user_preferences' ) );
	}

	/**
	 * Localize the plugin strings.
	 *
	 * @since 3.0.0
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'single-image-widget' );
	}

	/**
	 * Register the image widget.
	 *
	 * @since 3.0.0
	 */
	public function register_widget() {
		register_widget( 'Single_Image_Widget' );
	}

	/**
	 * Register and localize generic scripts and styles.
	 *
	 * A preliminary attempt has been made to abstract the
	 * 'single-image-widget-control' script a bit in order to allow it to be
	 * re-used anywhere a similiar media selection feature is needed.
	 *
	 * Custom image size labels need to be added using the
	 * 'image_size_names_choose' filter.
	 *
	 * @since 3.0.0
	 */
	public function register_assets() {
		wp_register_style(
			'single-image-widget-admin',
			dirname( plugin_dir_url( __FILE__ ) ) . '/assets/css/single-image-widget.css'
		);

		wp_register_script(
			'single-image-widget-admin',
			dirname( plugin_dir_url( __FILE__ ) ) . '/assets/js/single-image-widget.js',
			array( 'media-upload', 'media-views', 'wp-backbone', 'wp-util' )
		);

		wp_localize_script(
			'single-image-widget-admin',
			'SingleImageWidget',
			array(
				'l10n' => array(
					'frameTitle'      => __( 'Choose an Attachment', 'single-image-widget' ),
					'frameUpdateText' => __( 'Update Attachment', 'single-image-widget' ),
					'fullSizeLabel'   => __( 'Full Size', 'single-image-widget' ),
					'imageSizeNames'  => self::get_image_size_names(),
					'responseError'   => __( 'An error has occurred. Please reload the page and try again.', 'single-image-widget' ),
				),
				'screenOptionsNonce' => wp_create_nonce( 'save-siw-preferences' ),
			)
		);

		add_action( 'customize_controls_print_footer_scripts', array( $this, 'print_find_posts_templates' ) );
		add_action( 'admin_footer', array( $this, 'print_find_posts_templates' ) );
	}

	/**
	 * Add checkboxes to the screen options tab on the Widgets screen for
	 * togglable fields.
	 *
	 * @since 4.1.0
	 *
	 * @param string    $settings Screen options output.
	 * @param WP_Screen $screen   Current screen.
	 * @return string
	 */
	public function widgets_screen_settings( $settings, $screen ) {
		if ( 'widgets' !== $screen->id ) {
			return $settings;
		}

		$settings .= sprintf( '<h5>%s</h5>', __( 'single image Widget', 'single-image-widget' ) );

		$fields = array(
			'image_size'   => __( 'Image Size', 'single-image-widget' ),
			'link'         => __( 'Link', 'single-image-widget' ),
			'link_classes' => __( 'Link Classes', 'single-image-widget' ),
			'link_text'    => __( 'Link Text', 'single-image-widget' ),
			'new_window'   => __( 'New Window', 'single-image-widget' ),
			'text'         => __( 'Text', 'single-image-widget' ),
		);

		/**
		 * List of hideable fields.
		 *
		 * @since 4.1.0
		 *
		 * @param array $fields List of fields with ids as keys and labels as values.
		 */
		$fields = apply_filters( 'Single_Image_Widget_hideable_fields', $fields );
		$hidden_fields = $this->get_hidden_fields();

		foreach ( $fields as $id => $label ) {
			$settings .= sprintf(
				'<label><input type="checkbox" value="%1$s"%2$s class="single-image-widget-field-toggle"> %3$s</label>',
				esc_attr( $id ),
				checked( in_array( $id, $hidden_fields ), false, false ),
				esc_html( $label )
			);
		}

		return $settings;
	}

	/**
	 * Enqueue scripts needed for selecting media.
	 *
	 * @since 3.0.0
	 *
	 * @param string $hook_suffix Screen id.
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_media();
		wp_enqueue_script( 'single-image-widget-admin' );
		wp_enqueue_script( 'single-image-widget-find-posts' );
		wp_enqueue_style( 'single-image-widget-admin' );
	}

	/**
	 * Get localized image size names.
	 *
	 * The 'image_size_names_choose' filter exists in core and should be
	 * hooked by plugin authors to provide localized labels for custom image
	 * sizes added using add_image_size().
	 *
	 * @see image_size_input_fields()
	 * @see http://core.trac.wordpress.org/ticket/20663
	 *
	 * @since 3.0.0
	 *
	 * @return array Array of thumbnail sizes.
	 */
	public static function get_image_size_names() {
		return apply_filters(
			'image_size_names_choose',
			array(
				'thumbnail' => __( 'Thumbnail', 'single-image-widget' ),
				'medium'    => __( 'Medium', 'single-image-widget' ),
				'large'     => __( 'Large', 'single-image-widget' ),
				'full'      => __( 'Full Size', 'single-image-widget' ),
			)
		);
	}

	/**
	 * Retrieve a list of hidden fields.
	 *
	 * @since 4.1.0
	 *
	 * @return array List of field ids.
	 */
	public static function get_hidden_fields() {
		$hidden_fields = get_user_option( 'siw_hidden_fields', get_current_user_id() );

		// Fields that are hidden by default.
		if ( false === $hidden_fields ) {
			$hidden_fields = array( 'link_classes' );
		}

		/**
		 * List of hidden field ids.
		 *
		 * @since 4.1.0
		 *
		 * @param array $hidden_fields List of hidden field ids.
		 */
		return (array) apply_filters( 'Single_Image_Widget_hidden_fields', $hidden_fields );
	}

	/**
	 * Ajax handler for finding posts.
	 *
	 * @since 4.2.0
	 *
	 * @see wp_ajax_find_posts()
	 */
	public function ajax_find_posts() {
		check_ajax_referer( 'siw-find-posts', 'nonce' );

		$post_types = array();

		if ( ! empty( $_POST['post_types'] ) ) {
			foreach ( $_POST['post_types'] as $post_type ) {
				$post_types[ $post_type ] = get_post_type_object( $post_type );
			}
		}

		if ( empty( $post_types ) ) {
			$post_types['post'] = get_post_type_object( 'post' );
		}

		$args = array(
			'post_type'      => array_keys( $post_types ),
			'post_status'    => 'any',
			'posts_per_page' => 50,
		);

		if ( ! empty( $_POST['s'] ) ) {
			$args['s'] = wp_unslash( $_POST['s'] );
		}

		$posts = get_posts( $args );

		if ( ! $posts ) {
			wp_send_json_error( __( 'No items found.', 'single-image-widget' ) );
		}

		$html = $this->get_found_posts_html( $posts );

		wp_send_json_success( $html );
	}

	/**
	 * AJAX callback to save the user's hidden fields.
	 *
	 * @since 4.1.0
	 */
	public function ajax_save_user_preferences() {
		$nonce_action = 'save-siw-preferences';
		check_ajax_referer( $nonce_action, 'nonce' );
		$data = array( 'nonce' => wp_create_nonce( $nonce_action ) );

		if ( ! $user = wp_get_current_user() ) {
			wp_send_json_error( $data );
		}

		$hidden = isset( $_POST['hidden'] ) ? explode( ',', $_POST['hidden'] ) : array();
		if ( is_array( $hidden ) ) {
			update_user_option( $user->ID, 'siw_hidden_fields', $hidden );
		}

		wp_send_json_success( $data );
	}

	/**
	 * Retrieve HTML for displaying a list of posts.
	 *
	 * @since 4.2.0
	 *
	 * @param array $posts Array of post objects.
	 * @return string
	 */
	protected function get_found_posts_html( $posts ) {
		$html = sprintf(
			'<table class="widefat"><thead><tr><th>%1$s</th><th class="no-break">%2$s</th><th class="no-break">%3$s</th><th class="no-break">%4$s</th></tr></thead><tbody>',
			__( 'Title', 'single-image-widget' ),
			__( 'Type', 'single-image-widget' ),
			__( 'Date', 'single-image-widget' ),
			__( 'Status', 'single-image-widget' )
		);

		foreach ( $posts as $post ) {
			$title     = trim( $post->post_title ) ? $post->post_title : __( '(no title)', 'single-image-widget' );
			$post_link = 'attachment' == get_post_type( $post->ID ) ? wp_get_attachment_url( $post->ID ) : get_permalink( $post->ID );
			$status    = '';

			switch ( $post->post_status ) {
				case 'publish' :
				case 'private' :
					$status = __( 'Published', 'single-image-widget' );
					break;
				case 'future' :
					$status = __( 'Scheduled', 'single-image-widget' );
					break;
				case 'pending' :
					$status = __( 'Pending Review', 'single-image-widget' );
					break;
				case 'draft' :
					$status = __( 'Draft', 'single-image-widget' );
					break;
			}

			if ( '0000-00-00 00:00:00' == $post->post_date ) {
				$time = '';
			} else {
				/* translators: date format in table columns, see http://php.net/date */
				$time = mysql2date( __( 'Y/m/d', 'single-image-widget' ), $post->post_date );
			}

			$post_type_label = get_post_type_object( $post->post_type )->labels->singular_name;
			if ( 'attachment' == get_post_type( $post->ID ) ) {
				if ( preg_match( '/^.*?\.(\w+)$/', get_attached_file( $post->ID ), $matches ) ) {
					$post_type_label = esc_html( strtoupper( $matches[1] ) );
				} else {
					$post_type_label = strtoupper( str_replace( 'image/', '', get_post_mime_type( $post->ID ) ) );
				}
			}

			$html .= sprintf(
				'<tr class="found-posts"><td>%1$s <input type="hidden" value="%2$s"></td><td class="no-break">%3$s</td><td class="no-break">%4$s</td><td class="no-break">%5$s</td></tr>',
				esc_html( $title ),
				esc_url( apply_filters( 'Single_Image_Widget_find_posts_post_link', $post_link ), $post->ID ),
				esc_html( $post_type_label ),
				esc_html( $time ),
				esc_html( $status )
			);
		}

		$html .= '</tbody></table>';

		return $html;
	}

	/**
	 * Print JavaScript templates in the Customizer footer.
	 *
	 * @since 4.2.0
	 */
	public function print_find_posts_templates() {
		?>
		<script type="text/html" id="tmpl-single-image-widget-modal">
			<div class="single-image-widget-modal-head find-box-head">
				<?php _e( 'Find Post', 'single-image-widget' ); ?>
				<div class="single-image-widget-modal-close js-close"></div>
			</div>
			<div class="single-image-widget-modal-inside find-box-inside">
				<div class="single-image-widget-modal-search find-box-search">
					<?php wp_nonce_field( 'siw-find-posts', 'siw-find-posts-ajax-nonce', false ); ?>
					<input type="text" name="s" value="" class="single-image-widget-modal-search-field">
					<span class="spinner"></span>
					<input type="button" value="<?php esc_attr_e( 'Search', 'single-image-widget' ); ?>" class="button single-image-widget-modal-search-button" />
					<div class="clear"></div>
				</div>
				<div class="single-image-widget-modal-response"></div>
			</div>
		</script>
		<?php
	}
}
