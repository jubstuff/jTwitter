<?php
/**
 * Plugin Name: JTwitter
 * Plugin URI:
 * Description: A simple shortcode showing tweets. Uses transient API to cache the result
 * Version: 0.1
 * Author: Giustino Borzacchiello
 * Author URI: http://giustino.borzacchiello.it
 *
 */
add_shortcode('twitter', function ($atts, $content) {
    if (!isset($atts['username'])) {
        $atts['username'] = 'jubstuff';
    }
    if (empty($content)) {
        $content = 'Follow me on Twitter!';
    }
    /**
     * @var $username string Twitter username
     * @var $show_tweets bool Show or not last tweets?
     * @var $tweet_reset_time int Time in minutes: how frequently fetch tweets
     * @var $num_tweets int How many tweets to fetch
     */
    extract(shortcode_atts(array(
        'username' => 'jubstuff',
        'content' => !empty($content) ? $content : 'Follow me on Twitter!',
        'show_tweets' => false,
        'tweet_reset_time' => 10,
        'num_tweets' => 8
    ), $atts));
    $tweets = '';
    if ($show_tweets) {
        $tweets = fetch_tweets($num_tweets, $username, $tweet_reset_time);
    }
    return $tweets . '<p><a href="http://twitter.com/' . $username . '">' . $content . '</a></p>';
});

function fetch_tweets($num_tweets, $username, $tweet_reset_time)
{
    $key = 'jtwitter_last_tweets';
    $stored_tweets = get_transient($key);
    if ($stored_tweets !== false) {
        return $stored_tweets;
    }
    else {
        $response = wp_remote_get(
            "http://api.twitter.com/1/statuses/user_timeline.json?screen_name={$username}&count={$num_tweets}"
        );
        if (is_wp_error($response)) {
            // If there is an error, get the last tweets saved
            return get_option($key);
        }
        else {
            $tweets = json_decode(wp_remote_retrieve_body($response));
            $data = array();
            foreach ($tweets as $tweet) {
                $data[] = $tweet->text;
            }
            $recent_tweets = '<ul class="j_tweets"><li>' . implode('</li><li>', $data) . '</li></ul>';
            set_transient($key, $recent_tweets, $tweet_reset_time * 60);
            update_option($key, $recent_tweets);
            return $recent_tweets;
        }
    }

}