<?php

/**
 * Insert additional image sizes into the existing image sizes radio list
 *
 * @uses apply_filters() Calls 'kc_image_size_name' on image size name
 *
 * @param array $fields Current attachment fields.
 * @param object $post Post object.
 * @return array Modified $fields.
 */
function kc_additional_image_size_input_fields( $fields, $post ) {
	if ( !isset($fields['image-size']['html']) || substr($post->post_mime_type, 0, 5) != 'image' )
		return $fields;

	$sizes = kc_get_additional_image_sizes();
	if ( !count($sizes) )
		return $fields;

	$items = array();
	foreach ( array_keys($sizes) as $size ) {
		$downsize = image_downsize( $post->ID, $size );
		$enabled = $downsize[3];
		$css_id = "image-size-{$size}-{$post->ID}";
		$label = apply_filters( 'kc_image_size_name', $size );

		$html  = "<div class='image-size-item'>\n";
		$html .= "\t<input type='radio' " . disabled( $enabled, false, false ) . "name='attachments[{$post->ID}][image-size]' id='{$css_id}' value='{$size}' />\n";
		$html .= "\t<label for='{$css_id}'>{$label}</label>\n";
		if ( $enabled )
			$html .= "\t<label for='{$css_id}' class='help'>" . sprintf( "(%d&nbsp;&times;&nbsp;%d)", $downsize[1], $downsize[2] ). "</label>\n";
		$html .= "</div>";

		$items[] = $html;
	}

	$items = join( "\n", $items );
	$fields['image-size']['html'] = "{$fields['image-size']['html']}\n{$items}";

	return $fields;
}

?>