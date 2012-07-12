=== Plugin Name ===
Contributors: kucrut
Donate link: http://kucrut.org/
Tags: media, attachment, taxonomy, terms, category, tag
Requires at least: 3.0
Tested up to: 3.4.1
Stable tag: 0.2

Enhance WordPress media/attachment management

== Description ==

This plugin provides enhancements for media/attachment management, such as the ability to insert images with custom size into posts and set terms for the attachments files.

== Installation ==

1. Use standard WordPress plugin installation or upload the `kc-media-enhancements` directory to your `wp-content/plugins` directory
2. Activate the plugin through the 'Plugins' menu
3. Read the FAQ section on readme.txt if you need to enable only certain components

== Frequently Asked Questions ==

= Can I only activate components I need? =
Sure! By default, all components are enabled. If you have [KC Settings plugin] (http://wordpress.org/extend/plugins/kc-settings/) installed and activated, you'll have the luxury to select the components you need by visiting *Settings* &raquo; *KC Media Enhc.* in you dashboard.

If you don't want to use KC Setting plugin but still want to enable only certain components, you'll need to add this block of code to your theme's `functions.php` file and change each unwanted component's value to `false`:

`
function my_kcme_options( $options ) {
	$options = array(
		'general' => array(
			'components' => array( 'insert_custom_size', 'taxonomies' ),
			'taxonomies' => array( 'category', 'post_tag' )
		)
	);

	return $options;
}
add_filter( 'kcme_options', 'my_kcme_options' );
`

Please replace the taxonomies array with the taxonomy names you want to set for the attachment post type.

Options saved by KC Settings will always get the highest priority when the plugin is active.

== Screenshots ==
1. Settings page
2. Insert image with custom sizes into post
3. Set terms for the attachments


== Changelog ==

= 0.1 =
* Initial release

