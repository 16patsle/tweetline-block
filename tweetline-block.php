<?php

/*
Plugin Name: Tweetline Block
Plugin URI: https://github.com/16patsle/tweetline-block
Description: Twitter timeline block for Gutenberg
Version: 1.1.0
Requires PHP: 7.3
Author: Patrick Sletvold
Author URI: https://www.multitek.no
*/

require 'vendor/autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;

function tweetline_block_render( $attributes, $content ) {
    $attributeSettingsString = 'u:' . $attributes['username'] . ',c:' . $attributes['count'] . ',r:' . ($attributes['exclude_replies'] ? 'true' : 'false') . ',t:' . ($attributes['show_title'] ? 'true' : 'false');
    if ( false === ( $string = get_transient( 'tweetline_' . $attributeSettingsString . '_html' ) ) ) {
        // It wasn't there, so regenerate the data and save the transient
        if ( false === ( $timeline = get_transient( 'tweetline_' . $attributeSettingsString ) ) ) {
            // It wasn't there, so regenerate the data and save the transient
            $keys = json_decode(file_get_contents(__DIR__ . "/keys.json"));
            $twitter_connection = new TwitterOAuth($keys->tweetline_key, $keys->tweetline_secret);
            $timeline = $twitter_connection->get("statuses/user_timeline", ["screen_name" => $attributes['username'], "count" => $attributes['count'], "exclude_replies" => $attributes['exclude_replies'], "tweet_mode" => "extended"]);
            if ( $twitter_connection->getLastHttpCode() == 200 ) {
                set_transient( 'tweetline_' . $attributeSettingsString, $timeline, 12 * HOUR_IN_SECONDS );
            } else {
                return 'ERROR: Could not load timeline. The Twitter username ' . $attributes['username'] . ' may not exist';
            }
        }
        ob_start();
        //var_dump($attributes);
        ?>
        <div class="tweetline-block-tweetline-block">
            <?php
            if ( $attributes['show_title'] ) {
                ?>
                <div>
                    <h2 class="widget-title">
                        Tidslinje for <?php echo $timeline[0]->user->name ?>
                    </h2>
                </div>
                <?php
            }
            ?>
            <ul>
        <?php
        foreach ($timeline as $tweet ) {
            ?>
            <li>
                <div class="author">
                    <img src="<?php echo $tweet->user->profile_image_url_https ?>" alt="avatar">
                    <a href="https://twitter.com/<?php echo $tweet->user->screen_name ?>" rel="noopener" target="_blank">
                        <?php echo $tweet->user->name ?> (@<?php echo $tweet->user->screen_name ?>)
                    </a>
                    <img src="<?php echo plugins_url( 'assets/twttr.svg', __FILE__ ) ?>" height="0.9em" alt="" class="twttr-logo">
                </div>
                <div class="tweet">
                    <?php tweet_text($tweet) ?>
                </div>
                <div class="links">
                    <a href="https://twitter.com/multitek_no/status/<?php echo $tweet->id ?>" class="view" rel="noopener" target="_blank">
                        Vis på Twitter
                    </a>
                    <a href="https://twitter.com/multitek_no/status/<?php echo $tweet->id ?>" class="date" rel="noopener" target="_blank">
                        <time datetime="<?php echo $tweet->created_at ?>"><?php echo date_i18n( get_option( 'date_format' )/*"j. M. Y"*/, strtotime( $tweet->created_at ) ) ?></time>
                    </a>
                </div>
            </li>
            <?php
        }
        ?>
            </ul>
        </div>
        <?php
        $string = ob_get_clean();
        set_transient( 'tweetline_' . $attributeSettingsString . '_html', $string, 12 * HOUR_IN_SECONDS );
    }
    return $string;
}

function tweet_text($tweet) {
    $text = $tweet->full_text;
    foreach ($tweet->entities->urls as $url) {
        $text = str_replace($url->url, '<a href="' . $url->url . '" rel="nofollow noopener" target="_blank">' . $url->display_url . '</a>', $text);
    }
    foreach ($tweet->entities->user_mentions as $user_mention) {
        $text = str_replace('@' . $user_mention->screen_name, '<a href="https://twitter.com/' . $user_mention->screen_name . '" title="' . $user_mention->name . ' (@' . $user_mention->screen_name . ')' . '" rel="noopener" target="_blank">@' . $user_mention->screen_name . '</a>', $text);
    }
    foreach ($tweet->entities->hashtags as $hashtag) {
        $text = str_replace('#' . $hashtag->text, '<a href="https://twitter.com/hashtag/' . $hashtag->text . '?src=hash" rel="noopener" target="_blank">#' . $hashtag->text . '</a>', $text);
    }
    echo $text;
}

function tweetline_block() {
    wp_register_script(
        'tweetline-block',
        plugins_url( 'build/index.js', __FILE__ ),
        array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'build/index.js' )
    );

    wp_register_style(
        'tweetline-block',
        plugins_url( 'src/style.css', __FILE__ ),
        array( ),
        filemtime( plugin_dir_path( __FILE__ ) . 'src/style.css' )
    );

    register_block_type( 'tweetline-block/tweetline-block', array(
        'editor_script' => 'tweetline-block',
        'style' => 'tweetline-block',
        'render_callback' => 'tweetline_block_render',
        'attributes' => array(
            'username' => array(
                'type' => 'string',
                'default' => ''
            ),
            'count' => array(
                'type' => 'number',
                'default' => 5
            ),
            'exclude_replies' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'show_title' => array(
                'type' => 'boolean',
                'default' => true
            )
        )
    ) );

}
add_action( 'init', 'tweetline_block' );

function tweetline_shortcode( $atts ) {
	$a = shortcode_atts( array(
		'username' => '',
        'count' => 5,
        'exclude_replies' => true,
        'show_title' => false
    ), $atts );
    return tweetline_block_render($a, null);
}
add_shortcode( 'tweetline', 'tweetline_shortcode' );