import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { TextControl, ToggleControl, PanelBody } from '@wordpress/components';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import useSWR from 'swr';
import { Tweet } from './Tweet';
import './index.css';
import './style.css';

const fetchFromAPI = (path) => wp.apiFetch({ path });

registerBlockType('tweetline-block/tweetline-block', {
	icon: (
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="80 90 240 220">
			<path
				fill="#1da1f2"
				d="M153.62 301.59c94.34 0 145.94-78.16 145.94-145.94 0-2.22 0-4.43-.15-6.63A104.36 104.36 0 00325 122.47a102.38 102.38 0 01-29.46 8.07 51.47 51.47 0 0022.55-28.37 102.79 102.79 0 01-32.57 12.45 51.34 51.34 0 00-87.41 46.78A145.62 145.62 0 0192.4 107.81a51.33 51.33 0 0015.88 68.47A50.91 50.91 0 0185 169.86v.65a51.31 51.31 0 0041.15 50.28 51.21 51.21 0 01-23.16.88 51.35 51.35 0 0047.92 35.62 102.92 102.92 0 01-63.7 22 104.41 104.41 0 01-12.21-.74 145.21 145.21 0 0078.62 23"
			/>
		</svg>
	),
	edit: ({ attributes, setAttributes }) => {
		const { data: timeline, error } = useSWR(
			'/tweetline/v1/timeline',
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
				{attributes.username !== '' ? (
					<ServerSideRender
						block="tweetline-block/tweetline-block"
						attributes={attributes}
					/>
				) : (
					'Please enter a valid Twitter username'
				)}
			</div>
		);
	},
	save: () => null,
});
