import twitterLogoUrl from '../assets/twttr.svg';
import { TweetText } from './TweetText';

export const Tweet = ({ tweet }) => {
	return (
		<li>
			<div className="author">
				<img
					src={tweet.user.profile_image_url_https}
					alt="avatar"
					className="author-img"
				/>
				<a
					href={`https://twitter.com/${tweet.user.screen_name}`}
					rel="noopener noreferrer"
					target="_blank"
				>
					{tweet.user.name} (@{tweet.user.screen_name})
				</a>
				<img
					src={twitterLogoUrl}
					height="0.9em"
					alt=""
					className="twttr-logo"
				/>
			</div>
			<div className="tweet">
				<TweetText tweet={tweet} />
			</div>
			<div className="links">
				<a
					href={`https://twitter.com/${tweet.user.screen_name}/status/${tweet.id_str}`}
					className="view"
					rel="noopener noreferrer"
					target="_blank"
				>
					Vis på Twitter
				</a>
				<a
					href={`https://twitter.com/${tweet.user.screen_name}/status/${tweet.id_str}`}
					className="date"
					rel="noopener noreferrer"
					target="_blank"
				>
					<time dateTime="<?php echo $tweet->created_at ?>">
						{tweet.created_at_formatted}
					</time>
				</a>
			</div>
		</li>
	);
};
