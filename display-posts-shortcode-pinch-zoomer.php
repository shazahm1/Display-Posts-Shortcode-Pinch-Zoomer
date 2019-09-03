<?php
/**
 * @package   Display Posts Shortcode - Pinch Zoomer
 * @category  Extension
 * @author    Steven A. Zahm
 * @license   GPL-2.0+
 * @link      https://connections-pro.com
 * @copyright 2018 Steven A. Zahm
 *
 * @wordpress-plugin
 * Plugin Name:       Display Posts Shortcode - Pinch Zoomer
 * Plugin URI:        https://connections-pro.com/
 * Description:       An extension for the Display Posts Shortcode plugin which adds support pinch zooming post images.
 * Version:           1.0
 * Author:            Steven A. Zahm
 * Author URI:        https://connections-pro.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       display-posts-shortcode-pinch-zoomer
 * Domain Path:       /languages
 */

if ( ! class_exists( 'Display_Posts_Pinch_Zoomer' ) ) {

	final class Display_Posts_Pinch_Zoomer {

		const VERSION = '1.0';

		/**
		 * @var Display_Posts_Pinch_Zoomer Stores the instance of this class.
		 *
		 * @since 1.0
		 */
		private static $instance;

		/**
		 * @var string The absolute path this this file.
		 *
		 * @since 1.0
		 */
		private $file = '';

		/**
		 * @var string The URL to the plugin's folder.
		 *
		 * @since 1.0
		 */
		private $url = '';

		/**
		 * @var string The absolute path to this plugin's folder.
		 *
		 * @since 1.0
		 */
		private $path = '';

		/**
		 * @var string The basename of the plugin.
		 *
		 * @since 1.0
		 */
		private $basename = '';

		/**
		 * A dummy constructor to prevent the class from being loaded more than once.
		 *
		 * @since 1.0
		 */
		public function __construct() { /* Do nothing here */ }

		/**
		 * The main Connection Form plugin instance.
		 *
		 * @since 1.0
		 *
		 * @return self
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {

				self::$instance = $self = new self;

				$self->file     = __FILE__;
				$self->url      = plugin_dir_url( $self->file );
				$self->path     = plugin_dir_path( $self->file );
				$self->basename = plugin_basename( $self->file );

				$self->hooks();
				$self->registerJavaScripts();
				$self->registerCSS();
			}

			return self::$instance;
		}

		/**
		 * @since 1.0
		 */
		private function hooks() {

			add_filter( 'display_posts_shortcode_output', array( __CLASS__, 'postItem' ), 10, 11 );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueueCSS' ) );
		}

		/**
		 * @since 1.0
		 */
		public function getURL() {

			return $this->url;
		}

		/**
		 * @since 1.0
		 */
		private function registerJavaScripts() {

			$min    = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			$folder = $min ? 'minified' : 'uncompressed';
			$url    = Display_Posts_Pinch_Zoomer()->getURL();

			wp_register_script(
				'hammerjs',
				"{$url}includes/vendor/pinch-zoomer/{$folder}/js/hammer{$min}.js",
				array(),
				'2.0.8',
				TRUE
			);

			wp_register_script(
				'tweenmax',
				"{$url}includes/vendor/pinch-zoomer/{$folder}/js/TweenMax{$min}.js",
				array(),
				'2.0.1',
				TRUE
			);

			wp_register_script(
				'jquery-pinchzoomer',
				"{$url}includes/vendor/pinch-zoomer/{$folder}/js/jquery.pinchzoomer{$min}.js",
				array( 'jquery' , 'hammerjs', 'tweenmax' ),
				'2.2',
				TRUE
			);
		}

		/**
		 * @since 1.0
		 */
		public static function enqueueJS() {

			wp_enqueue_script( 'jquery-pinchzoomer' );
		}

		/**
		 * @since 1.0
		 */
		private function registerCSS() {

			$min    = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			$folder = $min ? 'minified' : 'uncompressed';
			$url    = Display_Posts_Pinch_Zoomer()->getURL();

			wp_register_style(
				'pinchzoomer',
				"{$url}includes/vendor/pinch-zoomer/{$folder}/css/pinchzoomer{$min}.css",
				array(),
				self::VERSION
			);
		}

		/**
		 * @since 1.0
		 */
		public static function enqueueCSS() {

			wp_enqueue_style( 'pinchzoomer' );
		}

		/**
		 * @since 1.0
		 *
		 * @return array
		 */
		public function getDefaults() {

			return array(
				'pz-enabled'  => TRUE,
				'pz-initZoom' => 1,
				'pz-maxZoom'  => 2,
				'pz-minZoom'  => 1,
			);
		}

		/**
		 * @since 1.0
		 *
		 * @param array $untrusted The user defined shortcode attributes.
		 *
		 * @return array
		 */
		public function shortcodeAtts( $untrusted ) {

			$defaults = Display_Posts_Pinch_Zoomer()->getDefaults();
			$atts     = shortcode_atts( $defaults, $untrusted, FALSE );

			self::toBoolean( $atts['pz-enabled'] );

			$atts['pz-initZoom'] = absint( $atts['pz-initZoom'] );
			$atts['pz-maxZoom']  = absint( $atts['pz-maxZoom'] );
			$atts['pz-minZoom']  = absint( $atts['pz-minZoom'] );

			return $atts;
		}

		/**
		 * @since 1.0
		 *
		 * @param array $options
		 *
		 * @return array
		 */
		public function postImageAttributes( $options ) {

			$attr = array();

			if ( $options['pz-enabled'] ) {

				$data   = array();
				$url    = Display_Posts_Pinch_Zoomer()->getURL();
				$min    = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
				$folder = $min ? 'minified' : 'uncompressed';

				$attr['data-elem']    = 'pinchzoomer';
				$attr['data-options'] = '';

				$data['preloaderUrl'] = "{$url}includes/vendor/pinch-zoomer/{$folder}/assets/preloader.gif";
				$data['initZoom']     = absint( $options['pz-initZoom'] );
				$data['maxZoom']      = absint( $options['pz-maxZoom'] );
				$data['minZoom']      = absint( $options['pz-minZoom'] );

				foreach ( $data as $property => $value ) {

					$attr['data-options'] .= $property . ':' . $value . ';';
				}

			}

			return $attr;
		}

		/**
		 * Callback for the `display_posts_shortcode_output` filter.
		 *
		 * @since 1.0
		 *
		 * @param string $html          The shortcode's HTML output.
		 * @param array  $original_atts Original attributes passed to the shortcode.
		 * @param string $image         HTML markup for the post's featured image element.
		 * @param string $title         HTML markup for the post's title element.
		 * @param string $date          HTML markup for the post's date element.
		 * @param string $excerpt       HTML markup for the post's excerpt element.
		 * @param string $inner_wrapper Type of container to use for the post's inner wrapper element.
		 * @param string $content       The post's content.
		 * @param array  $class         Space-separated list of post classes to supply to the $inner_wrapper element.
		 * @param string $author        HTML markup for the post's author.
		 * @param string $category_display_text
		 *
		 * @return string $html
		 */
		public static function postItem(
			$html,
			$original_atts,
			$image,
			$title,
			$date,
			$excerpt,
			$inner_wrapper,
			$content,
			$class,
			$author,
			$category_display_text
		) {

			$options = Display_Posts_Pinch_Zoomer()->shortcodeAtts( $original_atts );

			// If PZ is not enabled, no need to process the filter to add post image attributes.
			if ( ! $options['pz-enabled'] ) {

				return $html;
			}

			// Add action which will enqueue the PZ scripts.
			add_action( 'wp_footer', array( __CLASS__, 'enqueueJS' ) );

			$image_size   = sanitize_key( $original_atts['image_size'] );
			$include_link = filter_var( $original_atts['include_link'], FILTER_VALIDATE_BOOLEAN );

			$imageAttributes = Display_Posts_Pinch_Zoomer()->postImageAttributes( $options );


			if ( $image_size && has_post_thumbnail() && $include_link ) {

				$image = '<a class="image" href="' . get_permalink() . '">' . get_the_post_thumbnail( get_the_ID(), $image_size, $imageAttributes ) . '</a> ';

			} elseif( $image_size && has_post_thumbnail() ) {

				$image = '<span class="image">' . get_the_post_thumbnail( get_the_ID(), $image_size, $imageAttributes ) . '</span> ';
			}

			$image = '<div style="width: 100%; height: auto; position: relative; overflow: hidden;">' . $image . '</div>';

			$html = '<' . $inner_wrapper . ' class="' . implode( ' ', $class ) . '">' . $image . $title . $date . $author . $category_display_text . $excerpt . $content . '</' . $inner_wrapper . '>';

			/**
			 * Filter the HTML markup for output via the shortcode.
			 *
			 * @since 1.1
			 *
			 * @param string $html          The shortcode's HTML output.
			 * @param array  $original_atts Original attributes passed to the shortcode.
			 * @param string $image         HTML markup for the post's featured image element.
			 * @param string $title         HTML markup for the post's title element.
			 * @param string $date          HTML markup for the post's date element.
			 * @param string $excerpt       HTML markup for the post's excerpt element.
			 * @param string $inner_wrapper Type of container to use for the post's inner wrapper element.
			 * @param string $content       The post's content.
			 * @param string $class         Space-separated list of post classes to supply to the $inner_wrapper element.
			 * @param string $author 		HTML markup for the post's author.
			 * @param string $category_display_text
			 */
			$html = apply_filters(
				'Display_Posts_Shortcode_Pinch_Zoomer\postItem',
				$html,
				$original_atts,
				$image,
				$title,
				$date,
				$excerpt,
				$inner_wrapper,
				$content,
				$class,
				$author,
				$category_display_text
				);

			return $html;
		}

		/**
		 * Converts the following strings: yes/no; true/false and 0/1 to boolean values.
		 * If the supplied string does not match one of those values the method will return NULL.
		 *
		 * @since 1.0
		 *
		 * @param string|int|bool $value
		 *
		 * @return bool
		 */
		public static function toBoolean( &$value ) {

			// Already a bool, return it.
			if ( is_bool( $value ) ) return $value;

			$value = filter_var( strtolower( $value ), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

			if ( is_null( $value ) ) {

				$value = FALSE;
			}

			return $value;
		}
	}

	/**
	 * @since 1.0
	 *
	 * @return Display_Posts_Pinch_Zoomer
	 */
	function Display_Posts_Pinch_Zoomer() {

		return Display_Posts_Pinch_Zoomer::instance();
	}

	Display_Posts_Pinch_Zoomer();
}
