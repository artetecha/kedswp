<?php
/**
 * @author    ThemePunch <info@themepunch.com>
 * @link      https://www.themepunch.com/
 * @copyright 2024 ThemePunch
 */

if(!defined('ABSPATH')) exit();
?>

<sr-separator keepborder>
	<sr-separator-head notoggle>
		<sr-separator-title><?php _e('Color Effect Modes','revslider'); ?></sr-separator-title>		
	</sr-separator-head>
	<sr-separator-body>		
		<sr-drop half r="blend" data-type="search" data-source="blends" keepotitle="" data-v="" dropsh="280" dropsw="350" viewchild="layer_extra" class="sr--mr--10">
			<sr-drop-view style="overflow:hidden;">
				<span class="sr--drop--value" style="text-overflow: ellipsis;max-width: 75px;white-space: nowrap;overflow: hidden;vertical-align: top;"></span>                            
				<span class="sr--drop--icon"><svg width="10" height="6" transform="translate(0, -1)"><use xlink:href="#Drop_Down"></use></svg></span>
				<span class="sr--form--otitle"><?php _e('Blend','revslider');?></span>
			</sr-drop-view>					
		</sr-drop><!--
		--><sr-drop half r="mF" data-type="search" data-source="filters"  keepotitle="" data-onchange="editor.elements.filter.update" data-undoredo="editor.elements.filter.update" data-undoredoparams="undoredo" data-v="" dropsh="280" dropsw="350" viewchild="layer_extra">
			<sr-drop-view style="overflow:hidden;">				
				<span class="sr--drop--value" style="text-overflow: ellipsis;max-width: 75px;white-space: nowrap;overflow: hidden;vertical-align: top;"></span>                            
				<span class="sr--drop--icon"><svg width="10" height="6" transform="translate(0, -1)"><use xlink:href="#Drop_Down"></use></svg></span>
				<span class="sr--form--otitle"><?php _e('Filter','revslider');?></span>
			</sr-drop-view>				
		</sr-drop>	
		<sr-sp h="5"></sr-sp>				
	</sr-separator-body>
</sr-separator>