<?php
/**
 * Template loader.
 *
 * Based off version 1.1.0 of the Gamajo Template Loader by Gary Jones. Changes
 * allow for overriding class properties during instantiation and adding a
 * load_template() method that accepts arbitrary data to extra into the local
 * template scope.
 *
 * @package   SingleImageWidget
 * @since     4.0.0
 * @author    Gary Jones
 * @link      http://github.com/GaryJones/Gamajo-Template-Loader
 * @copyright 2013 Gary Jones
 * @license   GPL-2.0+
 */

/**
 * Template loader.
 *
 * @package SingleImageWidget
 * @since   4.0.0
 * @author  Gary Jones
 */
class Single_Image_Widget_Template_Loader {
	/**
	 * Prefix for filter names.
	 *
	 * @since 4.0.0
	 *
	 * @type string
	 */
	protected $filter_prefix = 'Single_Image_Widget';

	/**
	 * Directory name where custom templates for this plugin should be found in
	 * the theme.
	 *
	 * @since 4.0.0
	 *
	 * @type string
	 */
	protected $theme_template_directory = 'single-image-widget';

	/**
	 * Reference to the root directory path of this plugin.
	 *
	 * @since 4.0.0
	 *
	 * @type string
	 */
	protected $plugin_directory = SIW_DIR;

	/**
	 * Directory name where templates are found in this plugin.
	 *
	 * @since 4.0.0
	 *
	 * @type string
	 */
	protected $plugin_template_directory = 'templates';

	/**
	 * Contructor method to set up the loader.
	 *
	 * Accepts an array of class properties when instantiated to override the
	 * defaults.
	 *
	 * @since 4.0.0
	 *
	 * @param array $args List of class properties.
	 */
	public function __construct( $args = array() ) {
		$keys = array_keys( get_object_vars( $this ) );
		foreach ( $keys as $key ) {
			if ( isset( $args[ $key ] ) ) {
				$this->$key = $args[ $key ];
			}
		}
	}

	/**
	 * Retrieve a template part.
	 *
	 * @since 4.0.0
	 *
	 * @uses Single_Image_Widget_Template_Loader::get_template_possble_parts()
	 *     Create file names of templates.
	 * @uses Single_Image_Widget_Template_Loader::locate_template() Retrieve the
	 *     name of the highest priority template file that exists.
	 *
	 * @param string  $slug
	 * @param string  $name Optional. Default null.
	 * @param bool    $load Optional. Default true.
	 * @return string
	 */
	public function get_template_part( $slug, $name = null, $load = true ) {
		/**
		 * Execute code for this part.
		 *
		 * @since 4.0.0
		 *
		 * @param string $slug
		 * @param string $name
		 */
		do_action( 'get_template_part_' . $slug, $slug, $name );

		// Get files names of templates, for given slug and name.
		$templates = $this->get_template_file_names( $slug, $name );

		// Return the part that is found.
		return $this->locate_template( $templates, $load, false );
	}

	/**
	 * Given a slug and optional name, create the file names of templates.
	 *
	 * @since 4.0.0
	 *
	 * @param string $slug
	 * @param string $name
	 * @return array
	 */
	protected function get_template_file_names( $slug, $name ) {
		$templates = array();
		if ( isset( $name ) ) {
			$templates[] = $slug . '-' . $name . '.php';
		}
		$templates[] = $slug . '.php';

		/**
		 * Allow template choices to be filtered.
		 *
		 * The resulting array should be in the order of most specific first, to
		 * least specific last.
		 * e.g. 0 => recipe-instructions.php, 1 => recipe.php
		 *
		 * @since 4.0.0
		 *
		 * @param array  $templates Names of template files that should be looked for, for given slug and name.
		 * @param string $slug      Template slug.
		 * @param string $name      Template name.
		 */
		return apply_filters( $this->filter_prefix . '_get_template_part', $templates, $slug, $name );
	}

	/**
	 * Retrieve the name of the highest priority template file that exists.
	 *
	 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
	 * inherit from a parent theme can just overload one file. If the template is
	 * not found in either of those, it looks in the theme-compat folder last.
	 *
	 * @since 4.0.0
	 *
	 * @uses Single_Image_Widget_Template_Loader::get_template_paths() Return a
	 *     list of paths to check for template locations.
	 *
	 * @param string|array $template_names Template file(s) to search for, in order.
	 * @param bool         $load           If true the template file will be loaded if it is found.
	 * @param bool         $require_once   Whether to require_once or require. Default true.
	 *     Has no effect if $load is false.
	 * @return string      The template filename if one is located.
	 */
	public function locate_template( $template_names, $load = false, $require_once = true ) {
		// No file found yet.
		$located = false;

		// Remove empty entries.
		$template_names = array_filter( (array) $template_names );
		$template_paths = $this->get_template_paths();

		// Try to find a template file.
		foreach ( $template_names as $template_name ) {
			// Trim off any slashes from the template name.
			$template_name = ltrim( $template_name, '/' );

			// Try locating this template file by looping through the template paths.
			foreach ( $template_paths as $template_path ) {
				if ( file_exists( $template_path . $template_name ) ) {
					$located = $template_path . $template_name;
					break 2;
				}
			}
		}

		if ( $load && $located ) {
			load_template( $located, $require_once );
		}

		return $located;
	}

	/**
	 * Load a template file.
	 *
	 * @since 4.0.0
	 *
	 * @param string $template_file Absolute path to a file or list of template parts.
	 * @param array  $data          Optional. List of variables to extract into the template scope.
	 */
	public function load_template( $template_file, $data = array() ) {
		global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;

		if ( is_array( $data ) && ! empty( $data ) ) {
			extract( $data, EXTR_SKIP );
			unset( $data );
		}

		if ( file_exists( $template_file ) ) {
			require( $template_file );
		}
	}

	/**
	 * Return a list of paths to check for template locations.
	 *
	 * Default is to check in a child theme (if relevant) before a parent theme,
	 * so that themes which inherit from a parent theme can just overload one
	 * file. If the template is not found in either of those, it looks in the
	 * theme-compat folder last.
	 *
	 * @since 4.0.0
	 *
	 * @return mixed|void
	 */
	protected function get_template_paths() {
		$theme_directory = trailingslashit( $this->theme_template_directory );

		$file_paths = array(
			10  => trailingslashit( get_template_directory() ) . $theme_directory,
			100 => $this->get_templates_dir(),
		);

		// Only add this conditionally, so non-child themes don't redundantly check active theme twice.
		if ( is_child_theme() ) {
			$file_paths[1] = trailingslashit( get_stylesheet_directory() ) . $theme_directory;
		}

		/**
		 * Allow ordered list of template paths to be amended.
		 *
		 * @since 4.0.0
		 *
		 * @param array $var Default is directory in child theme at index 1, parent theme at 10, and plugin at 100.
		 */
		$file_paths = apply_filters( $this->filter_prefix . '_template_paths', $file_paths );

		// sort the file paths based on priority
		ksort( $file_paths, SORT_NUMERIC );

		return array_map( 'trailingslashit', $file_paths );
	}

	/**
	 * Return the path to the templates directory in this plugin.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	protected function get_templates_dir() {
		return trailingslashit( $this->plugin_directory ) . $this->plugin_template_directory;
	}
}
