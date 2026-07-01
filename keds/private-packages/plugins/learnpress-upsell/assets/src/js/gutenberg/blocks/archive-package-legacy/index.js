/**
 * Register block archive package legacy.
 */
import edit from './edit';
import { save } from './save';
import metadata from './block.json';
import { checkTemplatesCanLoadBlock } from '../utilBlock.js';
import { registerBlockType } from '@wordpress/blocks';
const templatesName = [ 'learnpress/learnpress//archive-learnpress_package' ];

checkTemplatesCanLoadBlock( templatesName, metadata, ( metadataNew ) => {
	registerBlockType( metadataNew.name, {
		...metadataNew,
		edit,
		save,
	} );
} );

registerBlockType( metadata.name, {
	...metadata,
	edit,
	save,
} );
