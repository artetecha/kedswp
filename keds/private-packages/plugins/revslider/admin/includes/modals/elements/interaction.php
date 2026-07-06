<?php
/**
 * @author    ThemePunch <info@themepunch.com>
 * @link      https://www.themepunch.com/
 * @copyright 2024 ThemePunch
 */

if(!defined('ABSPATH')) exit();

$dep_translate = 'magnetic#;#attract#;#repel'; // axis only applies to the move modes
?>
<sr-separator topborder>
	<sr-separator-head notoggle>
		<sr-separator-title><?php _e('Pointer Interaction','revslider'); ?></sr-separator-title>
	</sr-separator-head>
	<sr-separator-body>

		<!-- Interaction type: reacts to the cursor; composes on top of all other animations -->
		<sr-drop wide r="ix.type" viewchild="layer_hover" ignoreredraw data-v="none" class="sr--mb--10"
				 data-sh=".sr--ix--dep" data-shdep="#eqvalue"
				 data-onchange="editor.elements.ix.setType" data-undoredo="editor.elements.ix.setType">
			<sr-drop-view>
				<span class="sr--drop--value">None</span>
				<span class="sr--form--otitle"><?php _e('Interaction','revslider'); ?></span>
				<span class="sr--drop--icon"><svg width="10" height="6" transform="translate(0, -1)"><use xlink:href="#Drop_Down"></use></svg></span>
			</sr-drop-view>
			<sr-drops data-v="none"><?php _e('None','revslider'); ?></sr-drops>
			<sr-drops-title><?php _e('Move','revslider'); ?></sr-drops-title>
			<sr-drops data-v="magnetic"><?php _e('Magnetic (follow)','revslider'); ?></sr-drops>
			<sr-drops data-v="attract"><?php _e('Attract (lean)','revslider'); ?></sr-drops>
			<sr-drops data-v="repel"><?php _e('Repel / Push','revslider'); ?></sr-drops>
			<sr-drops-title><?php _e('Scale','revslider'); ?></sr-drops-title>
			<sr-drops data-v="pop"><?php _e('Pop (grow)','revslider'); ?></sr-drops>
			<sr-drops data-v="squeeze"><?php _e('Squeeze (shrink)','revslider'); ?></sr-drops>
			<sr-drops data-v="stretch"><?php _e('Stretch (toward cursor)','revslider'); ?></sr-drops>
			<sr-drops-title><?php _e('Rotate','revslider'); ?></sr-drops-title>
			<sr-drops data-v="tilt"><?php _e('Tilt 3D','revslider'); ?></sr-drops>
			<sr-drops data-v="rotate"><?php _e('Swing (2D)','revslider'); ?></sr-drops>
		</sr-drop>

		<!-- Everything below only shows once an interaction is chosen -->
		<sr-wrap wide class="sr--ix--dep" value="magnetic#;#attract#;#repel#;#squeeze#;#pop#;#stretch#;#tilt#;#rotate">

			<sr-input wide class="sr--mb--5">
				<input name="Strength" viewchild="layer_hover" r="ix.strength" ignoreredraw replace data-onchange="editor.elements.ix.update" data-undoredo="editor.elements.ix.update" livevisup autocomplete="off" dragnumber number="true" min="0" max="150" validate="true" type="text">
				<span noicon class="sr--form--otitle"><?php _e('Strength','revslider'); ?></span>
			</sr-input>

			<sr-input wide class="sr--mb--5">
				<input name="Radius" viewchild="layer_hover" r="ix.radius" ignoreredraw replace data-onchange="editor.elements.ix.update" data-undoredo="editor.elements.ix.update" livevisup autocomplete="off" dragnumber number="true" min="20" max="800" suffix="px" lastsuffix="px" validate="true" type="text">
				<span noicon class="sr--form--otitle"><?php _e('Proximity Radius','revslider'); ?></span>
			</sr-input>

			<sr-input wide class="sr--mb--10">
				<input name="Speed" viewchild="layer_hover" r="ix.speed" ignoreredraw replace data-onchange="editor.elements.ix.update" data-undoredo="editor.elements.ix.update" livevisup autocomplete="off" dragnumber number="true" min="2" max="40" validate="true" type="text">
				<span noicon class="sr--form--otitle"><?php _e('Follow Speed','revslider'); ?></span>
			</sr-input>

			<!-- Return ease: how it springs back when the cursor leaves the field -->
			<sr-drop wide r="ix.returnEase" viewchild="layer_hover" ignoreredraw data-v="smooth" class="sr--mb--10"
					 data-onchange="editor.elements.ix.update" data-undoredo="editor.elements.ix.update">
				<sr-drop-view>
					<span class="sr--drop--value">Smooth</span>
					<span class="sr--form--otitle"><?php _e('Return','revslider'); ?></span>
					<span class="sr--drop--icon"><svg width="10" height="6" transform="translate(0, -1)"><use xlink:href="#Drop_Down"></use></svg></span>
				</sr-drop-view>
				<sr-drops data-v="smooth"><?php _e('Smooth','revslider'); ?></sr-drops>
				<sr-drops data-v="back"><?php _e('Back (overshoot)','revslider'); ?></sr-drops>
				<sr-drops data-v="elastic"><?php _e('Elastic','revslider'); ?></sr-drops>
				<sr-drops data-v="bounce"><?php _e('Bounce','revslider'); ?></sr-drops>
			</sr-drop>

			<!-- Axis lock: only meaningful for the move modes -->
			<sr-wrap wide class="sr--ix--dep" value="<?php echo $dep_translate; ?>">
				<sr-drop wide r="ix.axis" viewchild="layer_hover" ignoreredraw data-v="both" class="sr--mb--0"
						 data-onchange="editor.elements.ix.update" data-undoredo="editor.elements.ix.update">
					<sr-drop-view>
						<span class="sr--drop--value">Both</span>
						<span class="sr--form--otitle"><?php _e('Axis','revslider'); ?></span>
						<span class="sr--drop--icon"><svg width="10" height="6" transform="translate(0, -1)"><use xlink:href="#Drop_Down"></use></svg></span>
					</sr-drop-view>
					<sr-drops data-v="both"><?php _e('Both','revslider'); ?></sr-drops>
					<sr-drops data-v="x"><?php _e('Horizontal','revslider'); ?></sr-drops>
					<sr-drops data-v="y"><?php _e('Vertical','revslider'); ?></sr-drops>
				</sr-drop>
			</sr-wrap>

			<!-- Perspective: only for Tilt 3D -->
			<sr-wrap wide class="sr--ix--dep" value="tilt">
				<sr-sp h="10"></sr-sp>
				<sr-input wide class="sr--mb--0">
					<input name="Perspective" viewchild="layer_hover" r="ix.perspective" ignoreredraw replace data-onchange="editor.elements.ix.update" data-undoredo="editor.elements.ix.update" livevisup autocomplete="off" dragnumber number="true" min="200" max="3000" suffix="px" lastsuffix="px" validate="true" type="text">
					<span noicon class="sr--form--otitle"><?php _e('Perspective','revslider'); ?></span>
				</sr-input>
			</sr-wrap>			
		</sr-wrap>

		<sr-sp h="20"></sr-sp>
	</sr-separator-body>
</sr-separator>
