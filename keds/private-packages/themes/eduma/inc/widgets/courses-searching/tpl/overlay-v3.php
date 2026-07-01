<?php
wp_enqueue_script( 'search-course-widget' );
$placeholder = $extral_class = '';
if ( $instance['placeholder'] && $instance['placeholder'] <> '' ) {
	$placeholder = $instance['placeholder'];
}

if ( isset( $instance['icon_style_overlay'] ) && $instance['icon_style_overlay'] <> '' ) {
	$extral_class = ' ' . $instance['icon_style_overlay'];
}

?>
<div class="thim-course-search-overlay<?php echo $extral_class; ?>">
	<div class="search-toggle"><i class="edu-search"></i></div>
	<?php thim_form_search_popup( $placeholder ); ?>
</div>
