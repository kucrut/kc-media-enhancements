<?php

/*
Plugin name: KC Media Enhancements
Plugin URI: http://kucrut.org/2011/04/kc-media-enhancements/
Description: Enhance WordPress media/attachment management
Version: 0.4
Author: Dzikri Aziz
Author URI: http://kucrut.org/
License: GPL v2
Text Domain: kc-media-enhancements
*/

class kcMediaEnhancements {

	private static $_data = array(
		'defaults' => array(
			'general' => array(
				'components' => array( 'insert_custom_size', 'taxonomies' ),
				'taxonomies' => array( 'category', 'post_tag' )
			),
		),
	);


	public static function _setup() {
		self::$_data['inc_path'] = dirname(__FILE__) . '/kc-media-enhancements-inc';

		# i18n
		load_plugin_textdomain( 'kc-media-enhancements', false, 'kc-media-enhancements/kc-media-enhancements-inc/languages' );

		add_action( 'init', array(__CLASS__, '_init'), 100 );
		add_filter( 'kc_plugin_settings', array(__CLASS__, '_settings') );
	}


	public static function _get_taxonomies() {
		$taxonomies = kcSettings_options::$taxonomies;
		unset( $taxonomies['post_format'] );

		return $taxonomies;
	}


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

		add_filter( 'attachment_fields_to_edit', array(__CLASS__, '_add_media_term_fields' ),  99, 2 );
		add_filter( 'attachment_fields_to_save', array(__CLASS__, '_save_media_term_fields' ), 99, 2 );
	}


	/**
	 * Add/Replace taxonomy term fields on Media Manager lightbox
	 *
	 * @param array  $form_fields Form fields
	 * @param object $post        WP_Post object
	 *
	 * @return array $form_fields
	 */
	public static function _add_media_term_fields( $form_fields, $post ) {
		foreach ( self::$_data['options']['general']['taxonomies'] as $taxonomy ) {
			if ( !isset( $form_fields[ $taxonomy ] ) )
				continue;

			$form_fields[ "{$taxonomy}-terms" ] = array(
				'label'        => $form_fields[ $taxonomy ]['label'],
				'input'        => 'html',
				'html'         => self::_get_taxonomy_field( $post, $taxonomy ),
				'show_in_edit' => false,
			);

			unset( $form_fields[ $taxonomy ] );
		}

		return $form_fields;
	}


	/**
	 * Get taxonomy term fields for Media Manager lightbox
	 *
	 * @param object $post     WP_Post object
	 * @param string $taxonomy Taxonomy name
	 *
	 * @return string Taxonomy term fields HTML
	 */
	private static function _get_taxonomy_field( $post, $taxonomy ) {
		$taxonomy_object = get_taxonomy( $taxonomy );
		$taxonomy_terms  = get_terms( $taxonomy, array(	'hide_empty' => false ) );

		if ( empty($taxonomy_object->args) ) {
			$taxonomy_object->args = array();
		}
		$terms = get_object_term_cache( $post->ID, $taxonomy );
		if ( false === $terms ) {
			$terms = wp_get_object_terms( $post->ID, $taxonomy, $taxonomy_object->args );
		}
		$media_terms = array();
		foreach ( $terms as $term ) {
			$media_terms[] = $term->slug;
		}

		ob_start();
		?>
			<div class="media-terms">
				<div class="available" style="max-height:11em;overflow:auto;margin-bottom:.5em">
					<?php foreach ( $taxonomy_terms as $term ) : ?>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( sprintf( 'attachments[%d][%s-terms][available][%s]', $post->ID, $taxonomy, $term->slug ) ) ?>" value="1"<?php checked( in_array( $term->slug, $media_terms ) ) ?> />
							<?php echo esc_html( $term->name ) ?>
						</label><br />
					<?php endforeach; ?>
				</div>
				<div class="new">
					<input type="text" name="<?php echo esc_attr( sprintf( 'attachments[%d][%s-terms][new]', $post->ID, $taxonomy ) ) ?>" />
					<p class="description" style="margin-bottom:0"><?php printf( esc_html__('Insert new %s separated by commas.', 'kc-media-enhancements'), strtolower($taxonomy_object->labels->name) ) ?></p>
				</div>
			</div>
		<?php
		return ob_get_clean();
	}


	/**
	 * Save media terms
	 *
	 * @param object $post            WP_Post object
	 * @param array  $attachment_data Attachment post data
	 */
	public static function _save_media_term_fields( $post, $attachment_data ) {
		foreach ( self::$_data['options']['general']['taxonomies'] as $taxonomy ) {
			$terms =
				!empty( $attachment_data["{$taxonomy}-terms"]['available'] )
				? array_filter( array_keys( $attachment_data["{$taxonomy}-terms"]['available'] ) )
				: array();
			if ( !empty($attachment_data["{$taxonomy}-terms"]['new']) ) {
				$terms = array_merge( $terms, explode( ',', $attachment_data["{$taxonomy}-terms"]['new'] ) );
			}

			wp_set_object_terms( $post['ID'], array_map( 'trim', $terms ), $taxonomy, false );
		}
	}


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
add_action( 'plugins_loaded', array( 'kcMediaEnhancements', '_setup' ) );
