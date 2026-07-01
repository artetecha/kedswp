<?php

/**
 * Section Blog Settings
 *
 * @package Eduma
 */

thim_customizer()->add_section(
	array(
		'id'       => 'blog_settings',
		'panel'    => 'blog',
		'title'    => esc_html__( 'Blog Settings', 'eduma' ),
		'priority' => 2,
	)
);

thim_customizer()->add_field(
	array(
		'id'            => 'thim_archive_cate_display_layout',
		'type'          => 'toggle',
		'label'         => esc_html__( 'Show Switch Grid/List', 'eduma' ),
		'section'       => 'blog_settings',
		'default'       => false,
		'priority'      => 20,
		'wrapper_attrs' => array(
			'class' => '{default_class}' . thim_customizer_extral_class( 'archive-post' ),
		),
	)
);
thim_customizer()->add_field(
	array(
		'type'          => 'select',
		'id'            => 'thim_archive_cate_template',
		'label'         => esc_html__( 'Template Default', 'eduma' ),
		'priority'      => 20,
		'default'       => 'list',
		'multiple'      => 0,
		'section'       => 'blog_settings',
		'choices'       => array(
			// 'default' => esc_html__( 'Default', 'eduma' ),
			'list' => esc_html__( 'List', 'eduma' ),
			'grid' => esc_html__( 'Grid', 'eduma' ),
		),
		// 'active_callback' => array(
		//  array(
		//      'setting'  => 'thim_archive_cate_display_layout',
		//      'operator' => '!==',
		//      'value'    => true,
		//  ),
		// ),
		'wrapper_attrs' => array(
			'class' => '{default_class}' . thim_customizer_extral_class( 'archive-post', array( 'all', 'post_page' ) ),
		),
	)
);


thim_customizer()->add_field(
	array(
		'type'            => 'radio-buttonset',
		'id'              => 'thim_feature_image_pos',
		'label'           => esc_html__( 'Item Image Placement', 'eduma' ),
		'default'         => 'beside',
		'section'         => 'blog_settings',
		'priority'        => 20,
		'choices'         => array(
			'beside' => esc_html__( 'Beside', 'eduma' ),
			'above'  => esc_html__( 'Above', 'eduma' ),
		),
		'active_callback' => array(
			array(
				'setting'  => 'thim_archive_cate_template',
				'operator' => '===',
				'value'    => 'list',
			),
			array(
				'setting'  => 'thim_archive_cate_display_layout',
				'operator' => '!==',
				'value'    => true,
			),
		),
		'wrapper_attrs'   => array(
			'class' => '{default_class}' . thim_customizer_extral_class( 'archive-post' ),
		),
	)
	// array(
	//  'id'              => 'thim_feature_image_pos',
	//  'type'            => 'select',
	//  'label'           => esc_html__( 'Item Image Placement', 'eduma' ),
	//  'priority'        => 20,
	//  'default'         => 'beside',
	//  'section'         => 'blog_settings',
	//  'choices'         => array(
	//      'beside' => esc_html__( 'Beside', 'eduma' ),
	//      'above'  => esc_html__( 'Above', 'eduma' ),
	//  ),
	//  'active_callback' => array(
	//      array(
	//          'setting'  => 'thim_archive_cate_template',
	//          'operator' => '===',
	//          'value'    => 'list',
	//      ),
	//      array(
	//          'setting'  => 'thim_archive_cate_display_layout',
	//          'operator' => '!==',
	//          'value'    => true,
	//      ),
	//  ),
	//  'wrapper_attrs'   => array(
	//      'class' => '{default_class}' . thim_customizer_extral_class( 'archive-post' ),
	//  ),
	// )
);


thim_customizer()->add_field(
	array(
		'id'              => 'thim_archive_cate_columns_grid',
		'type'            => 'slider',
		'label'           => esc_html__( 'Grid Column', 'eduma' ),
		'tooltip'         => esc_html__( 'Allows select column for style grid.', 'eduma' ),
		'priority'        => 20,
		'default'         => 3,
		'section'         => 'blog_settings',
		'choices'         => array(
			'min'  => '1',
			'max'  => '4',
			'step' => '1',
		),
		'active_callback' => array(
			// array(
			//  'setting'  => 'thim_archive_cate_display_layout',
			//  'operator' => '!==',
			//  'value'    => true,
			// ),
			array(
				'setting'  => 'thim_archive_cate_template',
				'operator' => '===',
				'value'    => 'grid',
			),
		),
		'wrapper_attrs'   => array(
			'class' => '{default_class}' . thim_customizer_extral_class( 'archive-post' ),
		),
	)
);


// Excerpt Content
thim_customizer()->add_field(
	array(
		'id'            => 'thim_archive_excerpt_length',
		'type'          => 'slider',
		'label'         => esc_html__( 'Excerpt Length', 'eduma' ),
		'tooltip'       => esc_html__( 'Choose the number of words you want to cut from the content to be the excerpt of search and archive', 'eduma' ),
		'priority'      => 30,
		'default'       => 30,
		'section'       => 'blog_settings',
		'choices'       => array(
			'min'  => '10',
			'max'  => '100',
			'step' => '5',
		),
		'wrapper_attrs' => array(
			'class' => '{default_class}' . thim_customizer_extral_class( 'archive-post' ),
		),
	)
);


thim_customizer()->add_field(
	array(
		'id'            => 'thim_archive_cate_show_description',
		'type'          => 'toggle',
		'label'         => esc_html__( 'Show Category Description', 'eduma' ),
		'tooltip'       => esc_html__( 'Allows you can show category description on archive blog.', 'eduma' ),
		'section'       => 'blog_settings',
		'default'       => false,
		'priority'      => 30,
		'wrapper_attrs' => array(
			'class' => '{default_class}' . thim_customizer_extral_class( 'archive-post' ),
		),
	)
);


// Meta Tags - Sortable (show/hide + reorder)
thim_customizer()->add_field(
	array(
		'id'       => 'thim_blog_meta_tags',
		'type'     => 'sortable',
		'label'    => esc_html__( 'Meta Tags Order & Visibility', 'eduma' ),
		'tooltip'  => esc_html__( 'Click on eye icons to show or hide meta tags. Use drag and drop to change the order of meta tags displayed on blog pages.', 'eduma' ),
		'section'  => 'blog_settings',
		'priority' => 30,
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
//  array(
//      'id'       => 'thim_blog_display_year',
//      'type'     => 'switch',
//      'label'    => esc_html__( 'Display Year', 'eduma' ),
//      'tooltip'  => esc_html__( 'Display year on date of Blog.', 'eduma' ),
//      'section'  => 'blog_settings',
//      'default'  => false,
//      'priority' => 30,
//  )
// );
