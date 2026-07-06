<?php
$cert = '';
global $post;
$cert_template = LP_Addon_Certificates::get_link_cert_bg_by_course( $post->ID );
?>

<div id="learn-press-certificates-preload" style="">
	<div id="cert-design-view-preload">
		<div id="cert-design-view-inside-preload">
			<div id="cert-design-editor-preload">
				<?php
				if ( $cert_template ) {
					echo '<img src="' . $cert_template . '" class="cert-template-preload" id="cert-template-preload"/>';
				}
				?>
			</div>
		</div>
	</div>
</div>
<div id="learn-press-certificates" class="no-design"
	data-popup-confirm-remove-layer="<?php esc_attr_e( 'Remove this layer?', 'learnpress-certificates' ); ?>">
	<?php _e( 'Publish certificate before editing!', 'learnpress-certificates' ); ?>
</div>
<script type="text/x-template" id="tmpl-certificates">
	<div id="learn-press-certificates" :class="{'has-template': !!template}"
		data-popup-confirm-remove-layer="<?php esc_attr_e( 'Remove this layer?', 'learnpress-certificates' ); ?>">

		<?php include_once 'editor-toolbar.php'; ?>

		<div id="cert-design-view" :class="{'dragover': dragover}" style="width:100%;">
			<div id="cert-design-view-inside">
				<div id="cert-design-editor">
					<img v-show="!!template" :src="template" class="cert-template"/>
					<canvas></canvas>
				</div>
			</div>

			<?php include_once 'editor-no-template.php'; ?>
			<?php include_once 'editor-rulers.php'; ?>
		</div>
	</div>
</script>
