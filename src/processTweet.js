import { splitOn } from './splitOn';

let offset = 0;
let children = [];
let rest = '';

function handleEntity(entity) {
	const start = entity.indices[0];
	const end = entity.indices[1];
	const [beforeText, entityText, afterText] = splitOn(
		rest,
		start - offset,
		end - offset
	);

	// Text before entity
	if (beforeText !== '') {
		children.push(beforeText);
	}

	// Process entity
	if (entity.type === 'user_mention') {
		children.push(
			<a
				href={`https://twitter.com/${entity.screen_name}`}
				title={`${entity.name} (@${entity.screen_name})`}
				rel="noopener noreferrer"
				target="_blank"
				key={entity.screen_name + offset}
			>
				@{entity.screen_name}
			</a>
		);
	} else if (entity.type === 'url') {
		children.push(
			<a
				href={entity.expanded_url}
				rel="nofollow noopener noreferrer"
				target="_blank"
				key={entity.display_url + offset}
			>
				{entity.display_url}
			</a>
		);
	} else if (entity.type === 'hashtag') {
		children.push(
			<a
				href={`https://twitter.com/hashtag/${entity.text}?src=hash`}
				rel="noopener noreferrer"
				target="_blank"
				key={entity.text + offset}
			>
				#{entity.text}
			</a>
		);
	} else {
		children.push(entityText);
	}

	// Text after entity
	rest = afterText;

	// Increse offset equal to the amount we processed
	offset = end;
}

export function processTweet(tweet) {
	const entities = Object.entries(tweet.entities).reduce(
		(allEntities, [type, array]) => {
			if (type === 'annotations') {
				return allEntities;
			}
			return [
				...array.map((entity) => ({
					type: type.slice(0, -1),
					...entity,
				})),
				...allEntities,
			];
		},
		[]
	);

	offset = 0;
	children = [];
	rest = tweet.full_text;

	entities
		.sort((a, b) => a.indices[0] - b.indices[1]) // In order of appearance
		.forEach((entity) => {
			handleEntity(entity);
		});

	if (rest !== '') {
		children.push(rest);
	}

	return children;
}
