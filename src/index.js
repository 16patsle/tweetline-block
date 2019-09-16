const { registerBlockType } = wp.blocks;
const { ServerSideRender, TextControl } = wp.components;

registerBlockType( 'tweetline-block/tweetline-block', {
    title: 'Tweetline',
    icon: 'smiley',
    category: 'layout',
    edit: ({attributes,setAttributes}) => {
        return <div>
            <TextControl
                label="Twitter username"
                value={attributes.username}
                onChange={(username) => setAttributes({ username })}
            />{
                attributes.username !== '' ? <ServerSideRender
                    block="tweetline-block/tweetline-block"
                    attributes={attributes}
                /> : 'Please enter a valid Twitter username'
            }
            
        </div>
    },
    save: () => null
} );