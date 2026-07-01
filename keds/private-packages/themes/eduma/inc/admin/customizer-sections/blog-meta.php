<?php
/**
 * Section Blog Meta Tags
 *
 * @package Hair_Salon
 */

thim_customizer()->add_section(
	array(
		'id'       => 'blog_meta',
		'panel'    => 'blog',
		'title'    => esc_html__( 'Meta Tags', 'eduma' ),
		'priority' => 20,
	)
);

// Meta Tags - Sortable (show/hide + reorder)
thim_customizer()->add_field(
	array(
		'id'       => 'thim_blog_meta_tags',
		'type'     => 'sortable',
		'label'    => esc_html__( 'Meta Tags Order & Visibility', 'eduma' ),
		'tooltip'  => esc_html__( 'Click on eye icons to show or hide meta tags. Use drag and drop to change the order of meta tags displayed on blog pages.', 'eduma' ),
		'section'  => 'blog_meta',
		'priority' => 10,
		'default'  => array(
			'author',
			'date',
			'comment',
		),
		'choices'  => array(
			'author'   => esc_html__( 'Author', 'eduma' ),
			'date'     => esc_html__( 'Date', 'eduma' ),
			'category' => esc_html__( 'Category', 'eduma' ),
			'comment'  => esc_html__( 'Comment Number', 'eduma' ),
			'tag'      => esc_html__( 'Tag', 'eduma' ),
		),
	)
);

// Display Year in Date
// thim_customizer()->add_field(
// 	array(
// 		'id'       => 'thim_blog_display_year',
// 		'type'     => 'switch',
// 		'label'    => esc_html__( 'Display Year', 'eduma' ),
// 		'tooltip'  => esc_html__( 'Display year on date of Blog.', 'eduma' ),
// 		'section'  => 'blog_meta',
// 		'default'  => false,
// 		'priority' => 20,
// 	)
// );
