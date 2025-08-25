/**
 * Register SearchWP Results Block
 */
import './style.scss';
import './editor.scss';

import { registerBlockType } from '@wordpress/blocks';
import edit from './edit';
import save from './save';

registerBlockType( 'searchwp/results-template', {
    edit,
    save,
} );
