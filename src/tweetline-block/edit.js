import { __ } from '@wordpress/i18n';
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
	const { data: timeline, error } = useSWR(
		`/tweetline/v1/timeline?username=${ attributes.username }&count=${ attributes.count }&exclude_replies=${ attributes.exclude_replies }`,
		fetchFromAPI
	);

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Settings' ) }>
					<TextControl
						label="Tweet count"
						help="Max amount of tweets to show."
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
						label="Exclude replies"
						help="Hides replies from the feed. May result in fewer tweets being shown."
						checked={ attributes.exclude_replies }
						onChange={ ( exclude_replies ) =>
							setAttributes( { exclude_replies } )
						}
					/>
					<ToggleControl
						label="Show title"
						help="Shows a widget style title."
						checked={ attributes.show_title }
						onChange={ ( show_title ) =>
							setAttributes( { show_title } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<TextControl
				label="Twitter username"
				value={ attributes.username }
				onChange={ ( username ) => setAttributes( { username } ) }
			/>
			{ attributes.username === ''
				? 'Please enter a valid Twitter username'
				: error && 'Error: ' + error.message }
			{ ! error && timeline && (
				<div className="tweetline-block-tweetline-block">
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
		</div>
	);
}
