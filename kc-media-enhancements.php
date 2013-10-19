<?php

/**
 * Plugin name: KC Media Enhancements
 * Plugin URI: http://kucrut.org/2011/04/kc-media-enhancements/
 * Description: Enhance WordPress media/attachment management
 * Version: 0.6
 * Author: Dzikri Aziz
 * Author URI: http://kucrut.org/
 * License: GPL v2
 * Text Domain: kc-media-enhancements
 */

class Kc_Media_Enhancements {

	const PREFIX = 'kc-media-enhancements';

	const VERSION = '0.6';

	/**
	 * Plugin class data
	 */
	private static $_data = array(
		'defaults' => array(
			'general' => array(
				'components' => array( 'insert_custom_size', 'taxonomies' ),
				'taxonomies' => array( 'category', 'post_tag' ),
			),
		),
	);


	/**
	 * Setup plugin
	 *
	 * @since 0.1
	 * @wp_hook action plugins_loaded
	 *
	 * @return void
	 */
	public static function _setup() {
		self::$_data['inc_path'] = dirname(__FILE__) . '/kc-media-enhancements-inc';

		# i18n
		load_plugin_textdomain(
			'kc-media-enhancements',
			false,
			'kc-media-enhancements/kc-media-enhancements-inc/languages'
		);

		add_action( 'init', array(__CLASS__, '_init'), 100 );
		add_filter( 'kc_plugin_settings', array(__CLASS__, '_settings') );
	}


	/**
	 * Get registered taxonomies
	 *
	 * @since 0.1
	 *
	 * @return array Array of taxonomy object
	 */
	public static function _get_taxonomies() {
		$taxonomies = kcSettings_options::taxonomies( false );
		unset( $taxonomies['post_format'] );

		return $taxonomies;
	}


	/**
	 * KC Settings entry
	 *
	 * @since 0.2
	 * @wp_hook filter kc_plugin_settings
	 *
	 * @param array $groups Settings groups
	 *
	 * @return array
	 */
	public static function _settings( $groups ) {
		$groups[] = array(
			'prefix'        => 'kc-media-enhancements',
			'menu_location' => 'upload.php',
			'menu_title'    => __('Enhancements', 'kc-media-enhancements'),
			'page_title'    => sprintf( __('%s Settings', 'kc-media-enhancements'), 'KC Media Enhancements' ),
			'options'       => array(
				'general'  => array(
					'id'     => 'general',
					'title'  => __('General', 'kc-media-enhancements'),
					'fields' => array(
						array(
							'id'      => 'components',
							'title'   => __('Components', 'kc-media-enhancements'),
							'type'    => 'checkbox',
							'options' => array(
								'insert_custom_size' => __('Enable insertion of images with custom sizes into post editor', 'kc-media-enhancements'),
								'taxonomies'         => __('Enable taxonomies for attachments', 'kc-media-enhancements')
							)
						),
						array(
							'id'      => 'taxonomies',
							'title'   => __('Taxonomies for attachments', 'kc-media-enhancements'),
							'type'    => 'checkbox',
							'options' => array( __CLASS__, '_get_taxonomies' ),
						)
					)
				)
			)
		);

		return $groups;
	}


	public static function _init() {
		if ( class_exists( 'kcSettings' ) )
			self::$_data['options'] = kc_get_option( 'kc-media-enhancements' );
		else
			self::$_data['options'] = apply_filters( 'kcme_options', self::$_data['defaults'] );

		if ( empty(self::$_data['options']['general']['components']) || !is_array(self::$_data['options']['general']['components']) )
			return;

		# 0. Insert image with custom sizes
		if ( in_array('insert_custom_size', self::$_data['options']['general']['components']) )
			add_filter( 'image_size_names_choose', array(__CLASS__, 'insert_image'), 99 );

		# 1. Attachment taxonomies
		if (
			!in_array('taxonomies', self::$_data['options']['general']['components'])
			|| empty(self::$_data['options']['general']['taxonomies'])
			|| !is_array(self::$_data['options']['general']['taxonomies'])
		)
			return;

		// Blacklist post_format
		$taxonomies = array_fill_keys( self::$_data['options']['general']['taxonomies'], true );
		unset( $taxonomies['post_format'] );
		self::$_data['options']['general']['taxonomies'] = array_keys( $taxonomies );
		if ( empty(self::$_data['options']['general']['taxonomies']) )
			return;

		$media_taxonomies = get_object_taxonomies( 'attachment' );
		foreach ( self::$_data['options']['general']['taxonomies'] as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) && !in_array( $taxonomy, $media_taxonomies ) ) {
				register_taxonomy_for_object_type( $taxonomy, 'attachment' );
			}
		}

		add_action( 'attachment_fields_to_edit', array( __CLASS__, '_modify_media_taxonomy_fields' ), 99, 2 );
		add_action( 'wp_enqueue_media',         array( __CLASS__, '_enqueue_assets' ) );
	}


	/**
	 * Modify Media Taxonomy Fields on media post edit
	 *
	 * @since 0.5
	 * @wp_hook action attachment_fields_to_edit
	 *
	 * @param array  $form_fields Attachment metadata form fields
	 * @param object $post       Attachment post object
	 *
	 * @return array
	 */
	public static function _modify_media_taxonomy_fields( $form_fields, $post ) {
		$taxonomies = get_object_taxonomies( 'attachment' );
		if ( empty($taxonomies) )
			return;

		foreach ( $taxonomies as $taxonomy ) {
			if ( !isset( $form_fields[ $taxonomy ] ) )
				continue;

			$form_fields[ $taxonomy ] = array_merge(
				$form_fields[ $taxonomy ],
				array(
					'input' => 'html',
					'html'  => sprintf(
						'<input type="text" class="text tax-terms" id="%s" name="%s" value="%s" data-taxonomy="%s" placeholder="%s" />',
						esc_attr( sprintf( 'attachments-%d-%s', $post->ID, $taxonomy ) ),
						esc_attr( sprintf( 'attachments[%d][%s]', $post->ID, $taxonomy ) ),
						esc_attr( $form_fields[ $taxonomy ]['value'] ),
						esc_attr( $taxonomy ),
						esc_attr__( 'Start typing&hellip;', 'kc-media-enhancements' )
					),
				)
			);
		}

		return $form_fields;
	}


	/**
	 * Get taxonomy term slugs
	 *
	 * @since 0.5
	 *
	 * @param string $taxonomy Taxonomy name
	 *
	 * @return mixed Array of taxonomy term slugs or false if taxonomy doesn't exist
	 */
	public static function get_terms_slugs( $taxonomy ) {
		if ( !taxonomy_exists($taxonomy) )
			return false;

		$terms_slugs = get_terms( $taxonomy, array( 'hide_empty' => false ) );
		if ( !empty($terms_slugs) ) {
			$terms_slugs = wp_list_pluck( $terms_slugs, 'slug' );
		}

		return $terms_slugs;
	}


	/**
	 * Enqueue assets for taxonomy terms autocomplete on media edit screen
	 *
	 * @since 0.5
	 * @wp_hook action wp_enqueue_media
	 *
	 * @return void
	 */
	public static function _enqueue_assets() {
		$include_url = sprintf(
			'%s%s-inc',
			trailingslashit( plugin_dir_url( __FILE__ ) ),
			self::PREFIX
		);
		wp_enqueue_style (
			'kc-me-jquery-ui',
			$include_url . '/css/jquery-ui/jquery.ui.all.css',
			false,
			null
		);
		wp_enqueue_script(
			'kc-me',
			$include_url . '/js/kc-me.js',
			array( 'jquery-ui-autocomplete' ),
			self::VERSION,
			true
		);

		$taxonomies = get_object_taxonomies( 'attachment' );
		if ( empty( $taxonomies ) )
			return;

		$taxonomy_terms = array();
		foreach ( $taxonomies as $taxonomy ) {
			$taxonomy_terms[  $taxonomy ] = self::get_terms_slugs( $taxonomy );
		}
		wp_localize_script( 'kc-me', 'kcmeTaxTerms', $taxonomy_terms );
	}


	/**
	 * Add custom image sizes to size selection dropdown on media edit screen
	 *
	 * @since 0.3
	 * @wp_hook image_size_names_choose
	 *
	 * @param array $sizes Image size names
	 *
	 * @return array
	 */
	public static function insert_image( $sizes ) {
		global $_wp_additional_image_sizes;
		if ( empty($_wp_additional_image_sizes) )
			return $sizes;

		foreach ( $_wp_additional_image_sizes as $id => $_data ) {
			if ( !isset($sizes[$id]) )
				$sizes[$id] = ucfirst( str_replace( '-', ' ', $id ) );
		}

		return $sizes;
	}
}
add_action( 'plugins_loaded', array( 'Kc_Media_Enhancements', '_setup' ) );
