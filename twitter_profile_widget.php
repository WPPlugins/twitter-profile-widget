<?php
/*
Plugin Name: Twitter Profile Widget
Plugin URI: http://wordpress.org/extend/plugins/twitter-profile-widget/
Description: 特定のスクリーンネームのプロフィールをウィジットに表示できるようになります。 Twitter Profile you can see the widget. Specified by screen name. 
Author: nemooon
Version: 0.5.2
Author URI: http://profiles.wordpress.org/users/nemooon/
*/
define( 'TWITTER_PROFILE_PLUGIN_DIR', WP_PLUGIN_DIR.'/'.plugin_basename( 'twitter-profile-widget' ).'/' );
define( 'TWITTER_PROFILE_PLUGIN_URL', WP_PLUGIN_URL.'/'.plugin_basename( 'twitter-profile-widget' ).'/' );
define( 'TWITTER_API_USER_SHOW', 'http://api.twitter.com/1/users/show' );

class TwitterProfileWidget extends WP_Widget {
	
	var $screen_name = array();
	
	function TwitterProfileWidget(){
		wp_enqueue_style( 'twitter-profile-widget-style', TWITTER_PROFILE_PLUGIN_URL.'style.css', array(), null );
		$name = "Twitter Profile";
		$widget_options = array( 'description' => 'Twitterのプロフィールを表示します。' );
		parent::WP_Widget( false, $name, $widget_options );
		$settings = $this->get_settings();
		foreach( $settings as $instance ){
			if( !$instance['screen_name'] ) continue;
			if( $instance['jsonp'] ){
				$screen_name_list = explode( ',', $instance['screen_name'] );
				for( $i=0; $i<count( $screen_name_list ); $i++ ){
					if( !empty( $screen_name_list[$i] ) ){
						$this->screen_name[] = $screen_name_list[$i];
					}
				}
			}
		}
		if( !empty( $this->screen_name ) ){
			add_action('template_redirect', array( $this, 'addUserShowFilter' ) );
		}
	}
	
	public function getUserShow( $query ){
		$url = TWITTER_API_USER_SHOW . '.json?' . http_build_query( $query );
		if( ( $response = @file_get_contents( $url ) ) == false ){
			list($version,$status_code,$msg) = explode(' ',$http_response_header[0], 3);
			$res = array();
			for ( $i = 1; $i < count( $http_response_header ); $i++ ) {
				list( $key, $value ) = explode( ': ', $http_response_header[ $i ] );
				$res[ $key ] = $value;
			}
			//var_dump($res,$version,$status_code,$msg);
			/*
			echo "<dt>現在のAPI制限回数:</dt>" . PHP_EOL;
			echo "<dd>" . $res[ 'X-RateLimit-Remaining' ] . "/" . $res[ 'X-RateLimit-Limit' ] . "</dd>" . PHP_EOL;
			echo "<dt>制限がリセットされる日時:</dt>" . PHP_EOL;
			echo "<dd>" . date( 'Y/m/d H:i:s', $res[ 'X-RateLimit-Reset' ] ) . "</dd>" . PHP_EOL;
			*/
			return;
		}
		
		
		
		return $response;
	}
	
	public function addUserShowFilter(){
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'twitter-profile-widget', TWITTER_PROFILE_PLUGIN_URL.'twitter_profile.js', array( 'jquery' ), null );
		foreach( $this->screen_name as $screen_name ){
			$query = array( 'screen_name' => $screen_name, 'callback' => 'twitterProfileUpdate', 'suppress_response_codes' =>'1' );
			$url = TWITTER_API_USER_SHOW . '.json?' . http_build_query( $query );
			wp_enqueue_script( 'twitter-profile-widget-jsop-'.$screen_name, $url, array( 'jquery', 'twitter-profile-widget' ), null );
		}
	}
	
	public function text_filter( $text ){
		$text = preg_replace( '/(https?:\/\/[a-zA-Z0-9.\/%#\?]+)/', '<a href="$1" target="_blank">$1</a>', $text );
		$text = preg_replace( '/@([a-zA-Z0-9_]+)/', '<a href="http://twitter.com/$1" target="_blank">@$1</a>', $text );
		$text = preg_replace( '/#([^\s^　]+)/', '<a href="http://twitter.com/#search?q=$1" target="_blank">#$1</a>', $text );
		return $text;
	}
	function widget($args, $instance) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		if( empty( $instance['screen_name'] ) ) return;
		$this->screen_name = $screen_name;
		echo $before_widget . PHP_EOL;
		if ( $title ) echo $before_title . $title . $after_title . PHP_EOL;
		$screen_name_list = explode( ',', $instance['screen_name'] );
		$callbacks = array();
		for( $i=0; $i<count( $screen_name_list ); $i++ ):
			$screen_name = $screen_name_list[$i];
			if( $screen_name == '' ) continue;
			$last = count( $screen_name_list ) == $i+1;
			$query = array( 'screen_name' => $screen_name );
			if( $instance['jsonp'] != '1' ){
				$jsonp = false;
				$response = $this->getUserShow( $query );
				if( is_null( $response ) ) continue;
				$usershow = json_decode( $response, true );
			} else {
				$jsonp = true;
			}
?>
	<div id="<?php echo "TwitterProfile_$screen_name"; ?>" class="twitter-profile">
		<div class="tp_icon_name">
			<a class="tp_user_link" href="http://twitter.com/<?php echo $screen_name; ?>" target="_blank">
				<img width="48" height="48" class="tp_profile_image" src="<?php echo $usershow['profile_image_url']; ?>" />
			</a>
			<a class="tp_user_link" href="http://twitter.com/<?php echo $screen_name; ?>" target="_blank">
				<span class="tp_name"><?php echo $usershow['name']; ?></span>
			</a><br />
			@<span class="tp_screen_name"><?php echo $screen_name; ?></span><br />
			<span class="tp_time_zone"><?php echo $usershow['time_zone']; ?></span>
		</div>
<?php if( $jsonp || !empty( $usershow['location'] ) ) : ?>
		<div class="tp_profile">
			<span class="label">Location</span>
			<span class="tp_location"><?php echo $usershow['location']; ?></span>
		</div>
<?php endif; ?>
<?php if( $jsonp || !empty( $usershow['description'] ) ) : ?>
		<div class="tp_profile">
			<span class="label">Bio</span>
			<span class="tp_description"><?php echo $this->text_filter( $usershow['description'] ); ?></span>
		</div>
<?php endif; ?>
<?php if( $jsonp || !empty( $usershow['url'] ) ) : ?>
		<div class="tp_profile">
			<span class="label">Web</span>
			<span class="tp_url"><?php echo $this->text_filter( $usershow['url'] ); ?></span>
		</div>
<?php endif; ?>
		<div class="tp_profile">
			<span class="label">Latest Tweet</span>
			<span class="tp_latest_tweet"><?php echo $this->text_filter( $usershow['status']['text'] ); ?></span>
		</div>
		<table class="tp_counts">
			<tr>
				<td>
					<a href="http://twitter.com/<?php echo $screen_name; ?>">
						<span class="tp_status_count"><?php echo number_format( $usershow['statuses_count'] ); ?></span><br />tweets
					</a>
				</td>
				<td>
					<a href="http://twitter.com/<?php echo $screen_name; ?>/following">
						<span class="tp_friends_count"><?php echo number_format( $usershow['friends_count'] ); ?></span><br />following
					</a>
				</td>
				<td>
					<a href="http://twitter.com/<?php echo $screen_name; ?>/followers">
						<span class="tp_followers_count"><?php echo number_format( $usershow['followers_count'] ); ?></span><br />followers
					</a>
				</td>
			</tr>
		</table>
	</div>
<?php
		endfor;
		echo $after_widget . PHP_EOL;
	}
	
	function update($new_instance, $old_instance) {
		return $new_instance;
	}
	
	function form($instance) {
		$title = esc_attr($instance['title']);
		$screen_name = esc_attr($instance['screen_name']);
		$jsonp = esc_attr($instance['jsonp']);
?>
<p>
	<label><?php _e('Title:'); ?>
	<input class="widefat" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label>
</p>
<p>
	<label><?php _e('Screen name:'); ?>
	<input class="widefat" name="<?php echo $this->get_field_name('screen_name'); ?>" type="text" value="<?php echo $screen_name; ?>" /></label>
</p>
<p>
	<input id="<?php echo $this->get_field_id('jsonp'); ?>" name="<?php echo $this->get_field_name('jsonp'); ?>" type="checkbox" value="1" <?php if( $jsonp == '1' ) echo 'checked="checked"'; ?> />
	<label for="<?php echo $this->get_field_id('jsonp'); ?>"> <?php _e('JSONP'); ?></label>
</p>
<?php
	}
}

add_action('widgets_init', create_function( '', 'return register_widget("TwitterProfileWidget");' ));
?>