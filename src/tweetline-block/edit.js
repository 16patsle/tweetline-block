import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { TextControl, ToggleControl, PanelBody } from '@wordpress/components';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import useSWR from 'swr';
import { Tweet } from '../Tweet';
import './editor.scss';

const fetchFromAPI = ( path ) => apiFetch( { path } );

/**
 * Renders the tweetline-block block.
 *
 * @param {Object}  props
 * @param {Object}  props.attributes
 * @param {Object}  props.setAttributes
 * @see https://developer.wordpress.org/block-editor/developers/block-api/block-edit-save/#edit
 * @return {WPElement} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( {
		className: 'tweetline-block-tweetline-block',
	} );
	const { data: timeline, error } = useSWR(
		`/tweetline/v1/timeline?username=${ attributes.username }&count=${ attributes.count }&exclude_replies=${ attributes.exclude_replies }`,
		fetchFromAPI
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings' ) }>
					<TextControl
						label={ __( 'Twitter username' ) }
						value={ attributes.username }
						onChange={ ( username ) =>
							setAttributes( { username } )
						}
					/>
					<TextControl
						label={ __( 'Tweet count' ) }
						help={ __( 'Max amount of tweets to show.' ) }
						type="number"
						value={ attributes.count }
						onChange={ ( count ) => {
							if ( count < 1 ) {
								setAttributes( { count: 1 } );
							} else if ( count > 200 ) {
								setAttributes( { count: 200 } );
							} else {
								setAttributes( { count } );
							}
						} }
					/>
					<ToggleControl
						label={ __( 'Exclude replies' ) }
						help={ __(
							'Hides replies from the feed. May result in fewer tweets being shown.'
						) }
						checked={ attributes.exclude_replies }
						onChange={ ( exclude_replies ) =>
							setAttributes( { exclude_replies } )
						}
					/>
					<ToggleControl
						label={ __( 'Show title' ) }
						help={ __( 'Shows a widget style title.' ) }
						checked={ attributes.show_title }
						onChange={ ( show_title ) =>
							setAttributes( { show_title } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			{ attributes.username === ''
				? __( 'Please enter a valid Twitter username' )
				: error && sprintf( 'Error: %s', error.message ) }
			{ ! error && timeline && (
				<div { ...blockProps }>
					{ attributes.show_title && timeline[ 0 ] && (
						<div>
							<h2 className="widget-title">
								Tidslinje for { timeline[ 0 ].user.name }
							</h2>
						</div>
					) }
					<ul>
						{ timeline.map( ( tweet ) => (
							<Tweet key={ tweet.id_str } tweet={ tweet } />
						) ) }
					</ul>
				</div>
			) }
		</>
	);
}
