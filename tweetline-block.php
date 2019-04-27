<?php

/*
Plugin Name: Tweetline Block
*/

require 'vendor/autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;

function tweetline_block_render( $attributes, $content ) {
    if ( false === ( $timeline = get_transient( 'tweetline_multitek_no' ) ) ) {
        // It wasn't there, so regenerate the data and save the transient
        $keys = json_decode(file_get_contents(__DIR__ . "/keys.json"));
        $twitter_connection = new TwitterOAuth($keys->tweetline_key, $keys->tweetline_secret);
        $timeline = $twitter_connection->get("statuses/user_timeline", ["screen_name" => "multitek_no", "count" => 5, "exclude_replies" => true, "tweet_mode" => "extended"]);
        set_transient( 'tweetline_multitek_no', $timeline, 12 * HOUR_IN_SECONDS );
    }
    ob_start();
    ?>
    <div class="tweetline-block-tweetline-block">
        <div>
            <h2>
                Tidslinje for <?php echo $timeline[0]->user->name ?>
                <img src="<?php echo plugins_url( 'assets/twttr.svg', __FILE__ ) ?>" alt="" class="twttr-logo">
            </h2>
        </div>
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
        array( 'wp-blocks', 'wp-element', 'wp-components' ),
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
        'render_callback' => 'tweetline_block_render'
    ) );

}
add_action( 'init', 'tweetline_block' );