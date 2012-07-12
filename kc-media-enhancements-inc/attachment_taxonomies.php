<?php

class kcAttachmentTaxonomies {
	function __construct( $taxonomies = array() ) {
		if ( empty($taxonomies) )
			return false;

		$this->build_taxonomies( $taxonomies );
		$this->actions_n_filters();
	}


	function build_taxonomies( $taxonomies ) {
		$attachment_taxonomies = get_object_taxonomies( 'attachment' );
		$tax_objects = array();
		foreach ( $taxonomies as $t ) {
			$tax = get_taxonomy( $t );
			$tax_objects[] = $tax;

			if ( !in_array($t, $attachment_taxonomies) ) {
				register_taxonomy_for_object_type( $t, 'attachment' );
				$attachment_taxonomies[] = $t;
			}
		}

		$this->taxonomies = $tax_objects;
	}


	function actions_n_filters() {

		# Modify posted attachment data and save terms for attachment.
		add_action( 'init', array(&$this, 'save_terms'), 100 );

		# Add submenu under 'Media'
		add_action( 'admin_menu', array(&$this, 'create_menu') );

		# Modify attachment edit form
		add_filter( 'attachment_fields_to_edit', array(&$this, 'form_fields'), 10, 2 );
	}


	/**
	 * Add submenus under 'Media' for attachments' taxonomies
	 */
	function create_menu() {
		foreach ( $this->taxonomies as $tax )
			add_submenu_page( 'upload.php', $tax->labels->name, $tax->labels->menu_name, $tax->cap->manage_terms, 'edit-tags.php?taxonomy=' . $tax->name );
	}


	/**
	 * Modify attachment edit form
	 *
	 * @param array $fields Attachment form fields
	 * @param object $post Attachment post object
	 * @return array $fields Modified attachment form fields
	 */
	function form_fields( $fields, $post ) {
		foreach ( $this->taxonomies as $tax ) {
			if ( !isset($tax->args) )
				$tax->args = array();

			$post_terms = get_object_term_cache( $post->ID, $tax->name );
			if ( empty($post_terms) )
				$post_terms = wp_get_object_terms( $post->ID, $tax->name, $tax->args );

			$att_terms = array();
			if ( !empty($post_terms) )
				foreach ( $post_terms as $post_term )
					$att_terms[$post_term->term_id] = $post_term->name;

			$tax_terms = get_terms( $tax->name, array('hide_empty' => false) );

			$html = "<ul class='attachment-terms-list'>\n";

			if ( !empty($tax_terms) )
				foreach ( $tax_terms as $term )
					# Existing terms
					$html .= "\t<li><label><input type='checkbox' name='attachments[{$post->ID}][{$tax->name}][]' value='".esc_attr($term->name)."' ".checked(array_key_exists($term->term_id, $att_terms), true, false)." /> {$term->name}</label></li>\n";

			# New term
			$html .= "\t<li><input type='text' name='attachments[{$post->ID}][{$tax->name}][]' /></label></li>\n";

			$html .= "</ul>\n";

			$fields[$tax->name]['input']	= 'html';
			$fields[$tax->name]['html']		= $html;
			$fields[$tax->name]['helps']	= sprintf(__( 'Check/uncheck existing %s, or add new one(s), separated by commas.', 'kc-media-enhancements'), $fields[$tax->name]['label'] );
		}

		return $fields;
	}


	/**
	 * Modify posted attachment data
	 */
	function save_terms() {
		if ( empty($_POST['attachments']) )
			return;

		foreach ( $_POST['attachments'] as $id => $data ) {
			$taxonomies = get_attachment_taxonomies( $id );
			if ( empty($taxonomies) )
				continue;

			foreach ( $taxonomies as $tax ) {
				if ( isset($data[$tax]) && !empty($data[$tax]) )
					$_POST['attachments'][$id][$tax] = trim( join( ',', array_unique($data[$tax]) ) );
			}
		}
	}

}

?>