const { registerBlockType } = wp.blocks;
const { ServerSideRender } = wp.components;

registerBlockType( 'tweetline-block/tweetline-block', {
    title: 'Tweetline',
    icon: 'smiley',
    category: 'layout',
    edit: props => <ServerSideRender
        block="tweetline-block/tweetline-block"
        attributes={ props.attributes }
    />,
    save: () => null
} );