import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { TextControl, ToggleControl, PanelBody } from '@wordpress/components';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import useSWR from 'swr';
import { Tweet } from './Tweet';
import { ReactComponent as TwitterLogo } from '../assets/twttr.svg';
import './index.css';
import './style.css';

const fetchFromAPI = (path) => wp.apiFetch({ path });

registerBlockType('tweetline-block/tweetline-block', {
	icon: <TwitterLogo />,
	edit: ({ attributes, setAttributes }) => {
		const { data: timeline, error } = useSWR(
			`/tweetline/v1/timeline?username=${attributes.username}&count=${attributes.count}&exclude_replies=${attributes.exclude_replies}`,
			fetchFromAPI
		);

		return (
			<div {...useBlockProps()}>
				<InspectorControls>
					<PanelBody title={__('Settings')}>
						<TextControl
							label="Amount of tweets to show"
							type="number"
							value={attributes.count}
							onChange={(count) => {
								if (count < 1) {
									setAttributes({ count: 1 });
								} else if (count > 200) {
									setAttributes({ count: 200 });
								} else {
									setAttributes({ count });
								}
							}}
						/>
						<ToggleControl
							label="Exclude replies"
							checked={attributes.exclude_replies}
							onChange={(exclude_replies) =>
								setAttributes({ exclude_replies })
							}
						/>
						<ToggleControl
							label="Show title"
							checked={attributes.show_title}
							onChange={(show_title) =>
								setAttributes({ show_title })
							}
						/>
					</PanelBody>
				</InspectorControls>

				<TextControl
					label="Twitter username"
					value={attributes.username}
					onChange={(username) => setAttributes({ username })}
				/>
				{attributes.username === '' &&
					'Please enter a valid Twitter username'}
				{!error && timeline && (
					<div className="tweetline-block-tweetline-block">
						{attributes.show_title && (
							<div>
								<h2 className="widget-title">
									Tidslinje for {timeline[0].user.name}
								</h2>
							</div>
						)}
						<ul>
							{timeline.map((tweet) => (
								<Tweet key={tweet.id_str} tweet={tweet} />
							))}
						</ul>
					</div>
				)}
				{error && 'Error: ' + error.message}
			</div>
		);
	},
	save: () => null,
});
