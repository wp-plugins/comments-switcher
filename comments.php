<?php
/*
 * @package Comments Switcher 0.2
 * Plugin URI: http://web-argument.com/wordpress-comments-switcher/
 *
**/

// Do not delete these lines
	if (!empty($_SERVER['SCRIPT_FILENAME']) && 'comments.php' == basename($_SERVER['SCRIPT_FILENAME']))
		die ('Please do not load this page directly. Thanks!');

	if ( post_password_required() ) { ?>
		<p class="nocomments"><?php _e('This post is password protected. Enter the password to view comments.'); ?></p>
	<?php
		return;
	}
?>

<!-- You can start editing here. -->

<?php if ( have_comments() ) : ?>
	<h3 id="comments"><?php	printf( _n( 'One Response to %2$s', '%1$s Responses to %2$s', get_comments_number() ),
									number_format_i18n( get_comments_number() ), '&#8220;' . get_the_title() . '&#8221;' ); ?></h3>

	<div class="navigation">
		<div class="alignleft"><?php previous_comments_link() ?></div>
		<div class="alignright"><?php next_comments_link() ?></div>
	</div>

	<ol class="commentlist">
	<?php wp_list_comments( array( 'callback' => 'csw_comment' ) );;?>
	</ol>

	<div class="navigation">
		<div class="alignleft"><?php previous_comments_link() ?></div>
		<div class="alignright"><?php next_comments_link() ?></div>
	</div>
 <?php else : // this is displayed if there are no comments so far ?>

	<?php if ( comments_open() ) : ?>
		<!-- If comments are open, but there are no comments. -->

	 <?php else : // comments are closed ?>
		<!-- If comments are closed. -->
		<p class="nocomments"><?php _e('Comments are closed.'); ?></p>

	<?php endif; ?>
<?php endif; ?>

<?php if ( comments_open() ) : ?>

<div id="respond">

<h3><?php comment_form_title( __('Leave a Reply'), __('Leave a Reply to %s' ) ); ?></h3>
<div id="fb-root"></div>
<ul id="csw_links">
	<li class="active"><a href="#csw_links" id="guest_login"><span><?php _e('Guest') ?></span></a></li>
	<li><a href="#csw_links" id="fb_login"><span><?php _e('Using Facebook') ?></span><img src="<?php echo CSW_PLUGIN_URL."/images/indicator_blue.gif" ?>" class="fb_status_img"  width="16" height="11"/></a></li>
</ul>

<div id="cancel-comment-reply">
	<small><?php cancel_comment_reply_link() ?></small>
</div>

<?php if ( get_option('comment_registration') && !is_user_logged_in() ) : ?>
<p><?php printf(__('You must be <a href="%s">logged in</a> to post a comment.'), wp_login_url( get_permalink() )); ?></p>
<?php else : ?>

<div id="guest_form">

<?php 

$options = get_csw_options();

if ($options['fb_apid'] == ''){ ?>
	<span class="fb_uid_error"><?php _e("The Facebook App ID should be included. Maybe you need to <a href='http://developers.facebook.com/apps' target='_blank'>register a Facebook app</a> for your blog." ) ?></span>
<?php } ?>

<form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post" id="guestcommentform">

<?php if ( is_user_logged_in() ) : ?>

<p><?php printf(__('Logged in as <a href="%1$s">%2$s</a>.'), get_option('siteurl') . '/wp-admin/profile.php', $user_identity); ?> <a href="<?php echo wp_logout_url(get_permalink()); ?>" title="<?php _e('Log out of this account'); ?>"><?php _e('Log out &raquo;'); ?></a></p>

<?php else : ?>

<p><input type="text" name="author" id="author" value="<?php echo esc_attr($comment_author); ?>" size="22" tabindex="1" <?php if ($req) echo "aria-required='true'"; ?> />
<label for="author"><small><?php _e('Name'); ?> <?php if ($req) _e('(required)'); ?></small></label></p>

<p><input type="text" name="email" id="email" value="<?php echo esc_attr($comment_author_email); ?>" size="22" tabindex="2" <?php if ($req) echo "aria-required='true'"; ?> />
<label for="email"><small><?php _e('Mail (will not be published)'); ?> <?php if ($req) _e('(required)'); ?></small></label></p>

<p><input type="text" name="url" id="url" value="<?php echo  esc_attr($comment_author_url); ?>" size="22" tabindex="3" />
<label for="url"><small><?php _e('Website'); ?></small></label></p>

<?php endif; ?>

<!--<p><small><?php printf(__('<strong>XHTML:</strong> You can use these tags: <code>%s</code>'), allowed_tags()); ?></small></p>-->

<p><textarea name="comment" id="comment" cols="58" rows="10" tabindex="4"></textarea></p>

<p><input name="submit" type="submit" id="submit" tabindex="5" value="<?php _e('Submit Comment'); ?>" />
<?php comment_id_fields(); ?>
</p>
<?php do_action('comment_form', $post->ID); ?>

</form>

</div><!-- /guest form -->


<div id="fb_form">

<form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post" id="fbcommentform" name="fbcommentform">

	<?php 
	$fb_uid = (isset($_COOKIE["csw_uid"]))?$_COOKIE["csw_uid"]:"";
	$fb_name = (isset($_COOKIE["csw_name"]))?$_COOKIE["csw_name"]:"";
	$fb_email = (isset($_COOKIE["csw_email"]))?$_COOKIE["csw_email"]:"";
    ?>
	<div id="fb_info">
    <?php 
        if($fb_uid != "") { ?>		
        <img src="http://graph.facebook.com/<?php echo $fb_uid ?>/picture" width="50" height="50" class="fb_pic"/>
    <?php  } else { ?>
		<img src="<?php echo CSW_PLUGIN_URL."/images/fb.jpg" ?>" width="50" height="50" class="fb_pic"/>	
	<?php } ?>
        <div class="fb_login_status">
        <?php
		printf(__('Logged in on Facebook <br />as <a href="http://www.facebook.com/profile.php?id=%1$s" target="_blank" class="fb_user">%2$s</a>.'), $fb_uid, $fb_name); ?>
        <span class="fb_login_status"><a href="" title="<?php _e('Facebook log out'); ?>" id="fb_logout"><?php _e('Log out &raquo;'); ?></a></span> 
        </div>       
    </div>
    
<input type="hidden" name="fb_uid" id="fb_uid" value="<?php echo $fb_uid ?>" />    
    
<p>
<input type="text" name="author" id="fb_author" value="<?php if(isset($fb_uid)) echo $fb_name; ?>" size="22" tabindex="1" <?php if ($req) echo "aria-required='true'"; ?> class="req" />
<label for="author"><small><?php _e('Name'); ?> <?php if ($req) _e('(required)'); ?></small></label></p>

<p><input type="text" name="email" id="fb_email" value="<?php if(isset($fb_email)) echo $fb_email; ?>" size="22" tabindex="2" <?php if ($req) echo "aria-required='true'"; ?> class="email" />
<label for="email"><small><?php _e('Mail (will not be published)'); ?> <?php if ($req) _e('(required)'); ?></small></label></p>

<p><input type="text" name="url" id="fb_url" value="<?php if(isset($fb_uid) && $fb_uid != "") echo "http://www.facebook.com/profile.php?id=".$fb_uid; ?>" size="22" tabindex="3" />
<label for="url"><small><?php _e('Website'); ?></small></label></p>


<p><textarea name="comment" id="comment" cols="58" rows="10" tabindex="4" class="req"></textarea></p>

<?php if ($options['fb_feed_allow_post']) { ?>
<p><input name="fb_feed" type="checkbox" value="1" checked  id="fb_feed_post"/><label for="url"><small><?php _e('Post notification to my Wall'); ?></small></label></p>
<?php } ?>

<p><input name="submit_btn" type="button" id="fb_submit" tabindex="5" value="<?php _e('Submit Comment'); ?>" />
<?php comment_id_fields(); ?>
</p>
<?php do_action('comment_form', $post->ID); ?>

</form>

</div><!-- /fb form -->

<?php endif; // If registration required and not logged in ?>
</div>

<?php endif; // if you delete this the sky will fall on your head ?>
