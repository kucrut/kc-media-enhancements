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

?>
