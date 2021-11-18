<?php

/*
Plugin Name: Tweetline Block
Plugin URI: https://github.com/16patsle/tweetline-block
Description: Twitter timeline block for Gutenberg
Version: 1.3.0
Requires at least: 5.6
Requires PHP: 7.3
Author: Patrick Sletvold
Author URI: https://www.multitek.no
*/

require 'vendor/autoload.php';

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Fetch a Twitter user's timeline using the Twitter API
 *
 * @param array $attributes username, count and exclude_replies attributes.
 * @return array|int The timeline, or an error code if none.
 */
function tweetline_get_timeline($attributes) {
    $attributeSettingsString = 'u:' . $attributes['username'] . ',c:' . $attributes['count'] . ',r:' . ($attributes['exclude_replies'] ? 'true' : 'false');
    if (false === ($timeline = get_transient('tweetline_' . $attributeSettingsString))) {
        // It wasn't there, so regenerate the data and save the transient
        $keys = json_decode(file_get_contents(__DIR__ . "/keys.json"));
        $twitter_connection = new TwitterOAuth($keys->tweetline_key, $keys->tweetline_secret);
        $timeline = $twitter_connection->get("statuses/user_timeline", ["screen_name" => $attributes['username'], "count" => $attributes['count'], "exclude_replies" => $attributes['exclude_replies'], "tweet_mode" => "extended"]);
        $statuscode = $twitter_connection->getLastHttpCode();
        if ($statuscode == 200) {
            set_transient('tweetline_' . $attributeSettingsString, $timeline, 12 * HOUR_IN_SECONDS);
        } else {
            return $statuscode;
        }
    }
    return $timeline;
}

function tweetline_block_render($attributes, $content) {
    $attributeSettingsString = 'u:' . $attributes['username'] . ',c:' . $attributes['count'] . ',r:' . ($attributes['exclude_replies'] ? 'true' : 'false') . ',t:' . ($attributes['show_title'] ? 'true' : 'false');
    if (false === ($string = get_transient('tweetline_' . $attributeSettingsString . '_html'))) {
        // It wasn't there, so regenerate the data and save the transient
        if (is_numeric($timeline = tweetline_get_timeline($attributes))) {
            return __(sprintf('ERROR: Could not load timeline. The Twitter user @%s may not exist, or may be private', $attributes['username']), 'tweetline');
        }
        ob_start();
        //var_dump($attributes);
?>
        <div class="tweetline-block-tweetline-block">
            <?php
            if ($attributes['show_title']) {
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
                foreach ($timeline as $tweet) {
                ?>
                    <li>
                        <div class="author">
                            <img src="<?php echo $tweet->user->profile_image_url_https ?>" alt="avatar" class="author-img">
                            <a href="https://twitter.com/<?php echo $tweet->user->screen_name ?>" rel="noopener noreferrer" target="_blank">
                                <?php echo $tweet->user->name ?> (@<?php echo $tweet->user->screen_name ?>)
                            </a>
                            <img src="<?php echo plugins_url('assets/twttr.svg', __FILE__) ?>" height="0.9em" alt="" class="twttr-logo">
                        </div>
                        <div class="tweet">
                            <?php tweet_text($tweet) ?>
                        </div>
                        <div class="links">
                            <a href="https://twitter.com/<?php echo $tweet->user->screen_name ?>/status/<?php echo $tweet->id_str ?>" class="view" rel="noopener noreferrer" target="_blank">
                                Vis på Twitter
                            </a>
                            <a href="https://twitter.com/<?php echo $tweet->user->screen_name ?>/status/<?php echo $tweet->id_str ?>" class="date" rel="noopener noreferrer" target="_blank">
                                <time datetime="<?php echo $tweet->created_at ?>"><?php echo date_i18n(get_option('date_format')/*"j. M. Y"*/, strtotime($tweet->created_at)) ?></time>
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
        set_transient('tweetline_' . $attributeSettingsString . '_html', $string, 12 * HOUR_IN_SECONDS);
    }
    return $string;
}

function tweet_text($tweet) {
    $text = $tweet->full_text;
    foreach ($tweet->entities->urls as $url) {
        $text = str_replace($url->url, '<a href="' . $url->url . '" rel="nofollow noopener noreferrer" target="_blank">' . $url->display_url . '</a>', $text);
    }
    foreach ($tweet->entities->user_mentions as $user_mention) {
        $text = str_replace('@' . $user_mention->screen_name, '<a href="https://twitter.com/' . $user_mention->screen_name . '" title="' . $user_mention->name . ' (@' . $user_mention->screen_name . ')' . '" rel="noopener noreferrer" target="_blank">@' . $user_mention->screen_name . '</a>', $text);
    }
    foreach ($tweet->entities->hashtags as $hashtag) {
        $text = str_replace('#' . $hashtag->text, '<a href="https://twitter.com/hashtag/' . $hashtag->text . '?src=hash" rel="noopener noreferrer" target="_blank">#' . $hashtag->text . '</a>', $text);
    }
    echo $text;
}

/**
 * Fetch a Twitter user's timeline
 *
 * @param WP_REST_Request $request The request object.
 * @return array|false The timeline, or false if none.
 */
function tweetline_endpoint($request) {
    $attributes = array(
        'username' => $request->get_param('username'),
        'count' => (int) $request->get_param('count'),
        'exclude_replies' => (bool) $request->get_param('exclude_replies')
    );
    if (is_numeric($timeline = tweetline_get_timeline($attributes))) {
        if ($timeline == 404) {
            return new WP_Error(
                'api_error_not_found',
                __(sprintf('The Twitter user @%s does not exist', $attributes['username']), 'tweetline'),
                array('status' => $timeline),
            );
        } else {
            return new WP_Error(
                'api_error',
                __(sprintf('Could not load timeline. The Twitter user @%s may not exist, or may be private', $attributes['username']), 'tweetline'),
                array('status' => $timeline),
            );
        }
    }
    foreach ($timeline as $tweet) {
        $tweet->created_at_formatted = date_i18n(get_option('date_format')/*"j. M. Y"*/, strtotime($tweet->created_at));
    }
    return $timeline;
}
add_action('rest_api_init', function () {
    register_rest_route('tweetline/v1', '/timeline', array(
        'methods' => 'GET',
        'callback' => 'tweetline_endpoint',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        },
        'args' => array(
            'username' => array(
                'required' => true,
                'validate_callback' => function ($param, $request, $key) {
                    return is_string($param) && !empty($param);
                },
            ),
            'count' => array(
                'default' => 5,
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param);
                },
            ),
            'exclude_replies' => array(
                'default' => true,
                'validate_callback' => function ($param, $request, $key) {
                    return in_array($param, array('true', 'false', '1', '0'));
                }
            ),
        ),
    ));
});

function tweetline_block() {
    register_block_type(
        __DIR__,
        array(
            'render_callback' => 'tweetline_block_render',
        )
    );
}
add_action('init', 'tweetline_block');

function tweetline_shortcode($atts) {
    $a = shortcode_atts(array(
        'username' => '',
        'count' => 5,
        'exclude_replies' => true,
        'show_title' => false
    ), $atts);
    return tweetline_block_render($a, null);
}
add_shortcode('tweetline', 'tweetline_shortcode');
