import { registerBlockType } from '@wordpress/blocks';
import { ReactComponent as TwitterLogo } from '../assets/twttr.svg';
import './style.scss';

import Edit from './edit';

registerBlockType( 'tweetline-block/tweetline-block', {
	icon: <TwitterLogo />,
	edit: Edit,
	save: () => null,
} );
