<?php

/*
Plugin name: KC Media Enhancements
Plugin URI: http://kucrut.org/2011/04/kc-media-enhancements/
Description: Enhance WordPress media/attachment management
Version: 0.2
Author: Dzikri Aziz
Author URI: http://kucrut.org/
License: GPL v2
Text Domain: kc-media-enhancements
*/

class kcMediaEnhancements {
	private static $data = array(
		'defaults' => array(
			'general' => array(
				'components' => array( 'insert_custom_size', 'taxonomies' ),
				'taxonomies' => array( 'category', 'post_tag' )
			)
		)
	);


	public static function prepare() {
		add_image_size( 'kcme-test', 1000, 200, true );

		self::$data['inc_path'] = dirname(__FILE__) . '/kc-media-enhancements-inc';
		self::$data['kcSettingsOK'] = class_exists( 'kcSettings' );

		# i18n
		load_plugin_textdomain( 'kc-media-enhancements', false, 'kc-media-enhancements/kc-media-enhancements-inc/languages' );

		add_filter( 'kc_plugin_settings', array(__CLASS__, 'settings') );
		add_action( 'admin_init', array(__CLASS__, 'init') );
	}


	public static function settings( $groups ) {
		$groups[] = array(
			'prefix'     => 'kc-media-enhancements',
			'menu_title' => 'KC Media Enhc.',
			'page_title' => sprintf( __('%s Settings', 'kc-media-enhancements'), 'KC Media Enhancements' ),
			'options'    => array(
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
							'options' => kcSettings_options::$taxonomies
						)
					)
				)
			)
		);

		return $groups;
	}


	public static function init() {
		if ( self::$data['kcSettingsOK'] )
			$options = kc_get_option( 'kc-media-enhancements' );
		else
			$options = apply_filters( 'kcme_options', self::$data['defaults'] );

		self::$data['options'] = $options;
		if ( !isset($options['general']['components']) || !is_array($options['general']['components']) || empty($options['general']['components']) )
			return;

		# 0. Insert image with custom sizes
		if ( in_array('insert_custom_size', $options['general']['components']) )
			add_filter( 'image_size_names_choose', array(__CLASS__, 'insert_image'), 99 );

		# 1. Attachment taxonomies
		if (
			!in_array('taxonomies', $options['general']['components'])
			|| !is_array($options['general']['taxonomies'])
			|| empty($options['general']['taxonomies'])
		)
			return;

		$taxonomies = array();
		foreach ( $options['general']['taxonomies'] as $tax )
			if ( taxonomy_exists( $tax ) )
				$taxonomies[] = $tax;

		if ( !empty($taxonomies) ) {
			require_once( self::$data['inc_path'] . '/attachment_taxonomies.php' );
			kcmeAttachmentTaxonomies::init( $taxonomies );
		}
	}


	public static function insert_image( $sizes ) {
		global $_wp_additional_image_sizes;
		if ( empty($_wp_additional_image_sizes) )
			return $sizes;

		foreach ( $_wp_additional_image_sizes as $id => $data ) {
			if ( !isset($sizes[$id]) )
				$sizes[$id] = ucfirst( str_replace( '-', ' ', $id ) );
		}

		return $sizes;
	}
}
add_action( 'plugins_loaded', array('kcMediaEnhancements', 'prepare') );

?>
