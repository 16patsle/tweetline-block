const { registerBlockType } = wp.blocks;
const { ServerSideRender, TextControl, ToggleControl } = wp.components;
const { InspectorControls,  } = wp.editor;

registerBlockType( 'tweetline-block/tweetline-block', {
    title: 'Tweetline',
    icon: 'smiley',
    category: 'layout',
    edit: ({attributes,setAttributes}) => {
        return <div>
            <InspectorControls>
                <TextControl
                    label="Amount of tweets to show"
                    type="number"
                    value={attributes.count}
                    onChange={(count) => {
                        if(count < 1){
                            setAttributes({ count:1 })
                        } else if (count > 200) {
                            setAttributes({ count: 200 })
                        } else {
                            setAttributes({ count })
                        }
                    }}
                />
                <ToggleControl
                    label="Exclude replies"
                    checked={attributes.exclude_replies}
                    onChange={(exclude_replies) => setAttributes({ exclude_replies })}
                />
                <ToggleControl
                    label="Show title"
                    checked={attributes.show_title}
                    onChange={(show_title) => setAttributes({ show_title })}
                />
            </InspectorControls>
            <TextControl
                label="Twitter username"
                value={attributes.username}
                onChange={(username) => setAttributes({ username })}
            />
            {
                attributes.username !== '' ? <ServerSideRender
                    block="tweetline-block/tweetline-block"
                    attributes={attributes}
                /> : 'Please enter a valid Twitter username'
            }
            
        </div>
    },
    save: () => null
} );