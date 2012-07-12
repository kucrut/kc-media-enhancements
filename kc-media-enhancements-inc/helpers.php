<?php

/*
function kc_get_public_taxonomies() {
	$public_taxonomies = array();
	$taxonomies = get_taxonomies( array('public' => true),  'objects');
	if ( empty($taxonomies) )
		return $public_taxonomies;

	foreach ( $taxonomies as $tax )
		if ( $tax->name != 'post_format' )
			$public_taxonomies[$tax->name] = $tax->label;

	return $public_taxonomies;
}

*/

/**
 * Get registered image sizes
 *
 * @param string $type Sizes to get: all, default, or custom
 * @return array $sizes Array of image sizes
 */
function kcme_get_image_sizes( $type = 'all' ) {
	$sizes = array();

	# Default sizes
	if ( $type !== 'custom' ) {
		foreach ( array('thumbnail', 'medium', 'large') as $size ) {
			$sizes[$size] = array(
				'width'  => get_option( "{$size}_size_w" ),
				'height' => get_option( "{$size}_size_h" )
			);
		}
	}

	if ( $type !== 'default' ) {
		global $_wp_additional_image_sizes;
		if ( is_array($_wp_additional_image_sizes) )
			$sizes = array_merge( $sizes, $_wp_additional_image_sizes );
	}

	ksort( $sizes );
	return $sizes;
}


?>
