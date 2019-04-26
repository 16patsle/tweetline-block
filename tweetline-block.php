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
    <div>
        <h2>
            Tidslinje for <?php echo $timeline[0]->user->name ?>
            <img src="<?php echo plugins_url( 'assets/twttr.svg', __FILE__ ) ?>" alt="" style="height: .9em; margin: 0 .25em; margin-bottom: -.1em;">
        </h2>
    </div>
    <?php
    echo '<ul>';
    foreach ($timeline as $tweet ) {
        ?>
        <li>
            <img src="<?php echo $tweet->user->profile_image_url_https ?>" alt="avatar">
            <h3>
                <a href="https://twitter.com/<?php echo $tweet->user->screen_name ?>"><?php echo $tweet->user->name ?> (@<?php echo $tweet->user->screen_name ?>)</a>
            </h3>
            <p>
                <?php tweet_text($tweet) ?>
            </p>
            <p>
            <a href="https://twitter.com/multitek_no/status/<?php echo $tweet->id ?>">Vis på Twitter</a> <a href="https://twitter.com/multitek_no/status/<?php echo $tweet->id ?>"><?php echo $tweet->created_at ?></a>
            </p>
        </li>
        <?php
    }
    echo'</ul>';
    $string = ob_get_clean();
    return $string;
}

function tweet_text($tweet) {
    $text = $tweet->full_text;
    foreach ($tweet->entities->urls as $url) {
        $text = str_replace($url->url, '<a href="' . $url->url . '">' . $url->display_url . '</a>', $text);
    }
    foreach ($tweet->entities->user_mentions as $user_mention) {
        $text = str_replace('@' . $user_mention->screen_name, '<a href="https://twitter.com/' . $user_mention->screen_name . '" title="' . $user_mention->name . ' (@' . $user_mention->screen_name . ')' . '">@' . $user_mention->screen_name . '</a>', $text);
    }
    foreach ($tweet->entities->hashtags as $hashtag) {
        $text = str_replace('#' . $hashtag->text, '<a href="https://twitter.com/hashtag/' . $hashtag->text . '?src=hash">#' . $hashtag->text . '</a>', $text);
    }
    echo $text;
}

function tweetline_block() {
    wp_register_script(
        'tweetline-block',
        plugins_url( 'build/index.js', __FILE__ ),
        array( 'wp-blocks', 'wp-element', 'wp-components' )
    );

    register_block_type( 'tweetline-block/tweetline-block', array(
        'editor_script' => 'tweetline-block',
        'render_callback' => 'tweetline_block_render'
    ) );

}
add_action( 'init', 'tweetline_block' );