<?php

/*
Plugin name: KC Media Enhancements
Plugin URI: http://kucrut.org/2011/04/kc-media-enhancements/
Description: Enhance WordPress media/attachment management
Version: 0.1
Author: Dzikri Aziz
Author URI: http://kucrut.org/
License: GPL v2
*/

class kcMediaEnhancements {
	private static $data = array(
		'defaults' => array(
			'general' => array(
				'components' => array( 'insert_custom_size', 'taxonomies' ),
				'taxonomies' => array(
					'category' => true,
					'post_tag' => true
				)
			)
		)
	);


	public static function prepare() {
		add_image_size( 'kcme-test', 1000, 200, true );

		self::$data['inc_path'] = dirname(__FILE__) . '/kc-media-enhancements-inc';
		self::$data['kcSettingsOK'] = class_exists( 'kcSettings' );

		# i18n
		load_plugin_textdomain( 'kc-media-enhancements', false, 'kc-media-enhancements/kc-media-enhancements-inc/languages' );

		# Load helpers
		require_once( self::$data['inc_path'] . '/helpers.php' );

		add_filter( 'kc_plugin_settings', array(__CLASS__, 'settings') );
		add_action( 'admin_init', array(__CLASS__, 'init') );
		add_action( 'admin_footer', array(__CLASS__, '_debug') );
	}


	public static function settings( $groups ) {
		$groups[] = array(
			'prefix'     => 'kc-media-enhancements',
			'menu_title' => __('KC Media Enhancements', 'kc-media-enhancements'),
			'page_title' => __('KC Media Enhancements Settings', 'kc-media-enhancements'),
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
			add_filter( 'attachment_fields_to_edit', array(__CLASS__, 'insert_image'), 11, 2 );

		# 1. Attachment taxonomies
		/*
		if ( isset($options['general']['components']['taxonomies'])
					&& $options['general']['components']['taxonomies']
					&& isset($this->options['general']['taxonomies'])
					&& is_array($this->options['general']['taxonomies'])
					&& !empty($this->options['general']['taxonomies']) ) {

			$taxonomies = array();
			foreach ( $this->options['general']['taxonomies'] as $key => $val )
				if ( $val )
					$taxonomies[] = $key;

			if ( !empty($taxonomies) ) {
				require_once( $this->inc_path . '/attachment_taxonomies.php' );
				$do = new kcAttachmentTaxonomies( $taxonomies );
			}
		}
		*/
	}


	public static function insert_image( $fields, $post ) {
		if ( !isset($fields['image-size']['html']) || substr($post->post_mime_type, 0, 5) != 'image' )
			return $fields;

		if ( self::$data['kcSettingsOK'] ) {
			$_sizes = kcSettings_options::$image_sizes_custom;
		}
		else {
			global $_wp_additional_image_sizes;
			$_sizes = $_wp_additional_image_sizes;
		}
		if ( empty($_sizes) )
			return $fields;

		$items = array();
		foreach ( array_keys($_sizes) as $size ) {
			$img = image_get_intermediate_size( $post->ID, $size );
			if ( !$img )
				continue;

			$css_id = "image-size-{$size}-{$post->ID}";
			$html  = "<div class='image-size-item'>";
			$html .= "<input type='radio' name='attachments[{$post->ID}][image-size]' id='{$css_id}' value='{$size}' />";
			$html .= "<label for='{$css_id}'>{$size}</label>";
			$html .= "<label for='{$css_id}' class='help'>" . sprintf( "(%d&nbsp;&times;&nbsp;%d)", $img['width'], $img['height'] ). "</label>";
			$html .= "</div>";

			$items[] = $html;
		}

		$items = join( "\n", $items );
		$fields['image-size']['html'] = "{$fields['image-size']['html']}\n{$items}";

		return $fields;
	}


	public static function _debug() {
		//echo '<pre>'.print_r( kcme_get_image_sizes('custom'), true).'</pre>';
		echo '<pre>'.print_r( self::$data['options'], true).'</pre>';
	}
}
add_action( 'plugins_loaded', array('kcMediaEnhancements', 'prepare') );

?>
