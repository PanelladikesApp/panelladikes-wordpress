import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import './editor.css';
import Edit from './edit';
import Icon from './icon';

registerBlockType( metadata.name, {
	icon: Icon,
	edit: Edit,
	// Dynamic block: the front end is printed by PHP.
	save: () => null,
} );
