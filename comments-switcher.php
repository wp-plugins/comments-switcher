<?php
/*
Plugin Name: Comments Switcher
Plugin URI: http://web-argument.com/wordpress-comments-switcher/
Description: Allows users to comment on your blog using the <strong>facebook credentials</strong>. Go to your <a href="options-general.php?page=comments-switcher">Comments Switcher Configuration</a> page, and save your Facebook APP ID.
Version: 0.2
Author: Alain Gonzalez
Author URI: http://web-argument.com/
*/

define('CSW_PLUGIN_DIR', WP_PLUGIN_DIR."/".dirname(plugin_basename(__FILE__)));
define('CSW_PLUGIN_URL', WP_PLUGIN_URL."/".dirname(plugin_basename(__FILE__)));

add_filter('comments_template','csw_comments_path',12);

function csw_comments_path($path){
	
	$new_path = CSW_PLUGIN_DIR . "/comments.php";
	
	return $new_path;
}

add_filter('preprocess_comment','csw_preprocess_comment',11,1);

function csw_preprocess_comment($commentdata){
	
	if ($_POST["fb_uid"] && $_POST["fb_uid"] != "") {
		$commentdata['comment_type'] = "fb";
		$commentdata['comment_karma'] = $_POST["fb_uid"];
		$commentdata['comment_author'] = ( isset($_POST['author']) )  ? trim(strip_tags($_POST['author'])) : null;
		$commentdata['comment_author_email'] = ( isset($_POST['email']) )   ? trim($_POST['email']) : null;
		$commentdata['comment_author_url'] = ( isset($_POST['url']) )     ? trim($_POST['url']) : null;
	}	
	return $commentdata;
}

add_filter('comment_cookie_lifetime', 'csw_comment_cookie_lifetime',10,1);

function csw_comment_cookie_lifetime($lifetime){
	if ($_POST["fb_uid"] && $_POST["fb_uid"] != "") {
		$lifetime = -1;
	}
	return $lifetime;
}

add_filter('get_avatar','csw_get_fb_pic',10,5);

function csw_get_fb_pic ($avatar, $comment, $size, $default, $alt){
	if(is_object($comment)){
		$comment_type = $comment->comment_type;
		$comment_karma = $comment->comment_karma;
	
		if ($comment_type == "fb" && $comment_karma != 0){
			 return "<a href='http://www.facebook.com/profile.php?id=".$comment_karma."'><img src='http://graph.facebook.com/".$comment_karma."/picture' class='fb_pic' width = '".$size."' height='".$size."'/></a>";
		} else {
			return $avatar;
		}
	} else 	{
		return $avatar;
	}
}

add_filter( 'get_avatar_comment_types','csw_fb_type', 10 );

function csw_fb_type($arr){
	array_push ($arr,"fb");
	return $arr;
}


/**
 * Inserting files on the header
 */
function csw_head() {
    
	if (is_single() || is_page()){
	
		$options = get_csw_options();
		
		wp_enqueue_script( 'facebook-comment-switcher', CSW_PLUGIN_URL . '/js/wpcwitcher.js',array( 'jquery' ));
		
		$csw_header =  "\n<!-- Comments Switcher -->\n";
		$csw_header .= "<script type='text/javascript'>\n";	
		$csw_header .= "window.fbAsyncInit = function() {\n";	
		$csw_header .= "WPCSwitcher.GraphStreamPublish.Body = ".csw_fb_feed_generate().";\n";	
		$csw_header .= "WPCSwitcher.Init({appId:'".$options['fb_apid']."'});\n";	
		$csw_header .= "}\n";	
		$csw_header .= "</script>\n";
		$csw_header .= "<link rel='stylesheet' href='".CSW_PLUGIN_URL."/style.css' type='text/css' media='screen' />\n";
		$csw_header .=  "\n<!-- Comments Switcher -->\n";		
			
		print($csw_header);
	
	}
}

add_action('wp_head', 'csw_head');

wp_enqueue_script( 'comments-switcher', CSW_PLUGIN_URL . '/js/comments-switcher.0.2.js',array( 'jquery' ));


if ( ! function_exists( 'csw_comment' ) ) :
/**
 * Template for comments and pingbacks.
 */
function csw_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	$comment_type = $comment->comment_type;	
	switch ( $comment_type ) :
		case '' :
		case 'fb' :
	?>
	<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
		<div id="comment-<?php comment_ID(); ?>">
		<div class="comment-author vcard">
            <?php  echo get_avatar( $comment, 40 ); ?>	
            <div class="comment-meta commentmetadata">
                <?php printf( __( '%s <span class="says">says:</span>'), sprintf( '<cite class="fn">%s</cite>', get_comment_author_link() ) ); ?><br />
                <?php if ( $comment->comment_approved == '0' ) : ?>
                    <em class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.'); ?></em>
                    <br />
                <?php endif; ?>        
            
                <a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
                <?php
                    /* translators: 1: date, 2: time */
                    printf( __( '%1$s at %2$s' ), get_comment_date(),  get_comment_time() ); ?></a><?php edit_comment_link( __( '(Edit)' ), ' ' );
                ?>
                </a>
                
            </div><!-- .comment-meta .commentmetadata -->
            <div class="reply">
                <?php comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
            </div><!-- .reply -->            
        </div><!-- .comment-author .vcard -->

		<div class="comment-body"><?php comment_text(); ?></div>


	</div><!-- #comment-##  -->

	<?php
			break;
		case 'pingback'  :
		case 'trackback' :
	?>
	<li class="post pingback">
		<p><?php _e( 'Pingback:' ); ?> <?php comment_author_link(); ?><?php edit_comment_link( __( '(Edit)'), ' ' ); ?></p>
	<?php
			break;
	endswitch;
}
endif;

/**
 * Default Options
 */
function get_csw_options ($default = false){

	$csw_default = array(
							'fb_apid' => '',
							'fb_feed_allow_post' => "1",
							'fb_feed_message'=>__('commented on %blog_name%'),
							'fb_feed_link'=>'%post_url%',
							'fb_feed_name'=>'%post_title%',
							'fb_feed_caption'=>'%blog_description%',
							'fb_feed_description'=>'%post_excerpt%'
							);							
    	
	if ($default) {
	update_option('csw_op', $csw_default);
	return $csw_default;
	}
	
	$options = get_option('csw_op');	
	
	if (empty($options)) {
		 $options = $csw_default;
	}
	
	return $options;
}

/**
 * Settings
 */  

add_action('admin_menu', 'csw_set');

function csw_set() {
		$plugin_page = add_options_page('Comments Switcher', 'Comments Switcher', 'administrator', 'comments-switcher', 'csw_options_page');	 
	 }


function csw_options_page() {

	$options = get_csw_options();

    if(isset($_POST['Restore_Default'])) $options = get_csw_options(true);	?>

	<div class="wrap">   
	
	<h2><?php _e("Comments Switcher") ?></h2>

	<?php 

	if(isset($_POST['Submit'])){
		
		if($_POST['fb_apid'] == "") { ?>
		
			<div class='error'><p><strong><?php _e("The Facebook App ID should be included. Maybe you need to <a href='http://developers.facebook.com/apps' target='_blank'>register a Facebook app</a> for your blog." ); ?></strong></p></div>
            
        <?php } else {
	
     		$newoptions['fb_apid'] = isset($_POST['fb_apid'])?$_POST['fb_apid']:$options['fb_apid'];
			
			$newoptions['fb_feed_allow_post'] = isset($_POST['fb_feed_allow_post'])?$_POST['fb_feed_allow_post']:"0";
			
			$newoptions['fb_feed_message'] = isset($_POST['fb_feed_message'])?$_POST['fb_feed_message']:$options['fb_feed_message'];
			$newoptions['fb_feed_link'] = isset($_POST['fb_feed_link'])?$_POST['fb_feed_link']:$options['fb_feed_link'];			
			$newoptions['fb_feed_name'] = isset($_POST['fb_feed_name'])?$_POST['fb_feed_name']:$options['fb_feed_name'];
			$newoptions['fb_feed_caption'] = isset($_POST['fb_feed_caption'])?$_POST['fb_feed_caption']:$options['fb_feed_caption'];		
			$newoptions['fb_feed_description'] = isset($_POST['fb_feed_description'])?$_POST['fb_feed_description']:$options['fb_feed_description'];


			if ( $options != $newoptions ) {
				$options = $newoptions;
				update_option('csw_op', $options);			
			}
			
		}
	    
 	} 

	$fb_apid = $options['fb_apid'];
	$fb_feed_allow_post = $options['fb_feed_allow_post'];
	
	$fb_feed_message = $options['fb_feed_message'];
	$fb_feed_link = $options['fb_feed_link'];	
	$fb_feed_name = $options['fb_feed_name'];
	$fb_feed_caption = $options['fb_feed_caption'];
	$fb_feed_description = $options['fb_feed_description'];
	
	?>  
	
	<form method="POST" name="options" target="_self" enctype="multipart/form-data">
    
	<h3><?php _e("Connecting to Facebook.") ?></h3>
    
    <table width="80%" border="0" cellspacing="10" cellpadding="0">
      <tr>
        <td rowspan="2" align="center" valign="middle" width="70"><img src="<?php echo CSW_PLUGIN_URL."/images/fb.jpg" ?>" width="50" height="50" /></td>
        <td width="130" align="right" height="40"><strong><?php _e("Facebook App ID") ?></strong></td>
        <td>
        	<input name="fb_apid" type="text" size="30" value="<?php echo $fb_apid ?>" />
            <?php _e("<em><a href='http://developers.facebook.com/apps' target='_blank'>Register a Facebook app</a></em>" ); ?>
        
        </td>
      </tr>
      <tr>
        <td align="right" valign="top"><strong><?php _e("Facebook Feed") ?></strong></td>        
        <td>
			<input name="fb_feed_allow_post" type="checkbox" value="1" <?php if ($fb_feed_allow_post == "1") echo "checked = \"checked\"" ?> /> 
            <em><?php _e(' Check if you want to include the option "Post notification on my Wall"') ?></em>
        </td>
      </tr>
    </table>
      
      <table width="80%" border="0" cellspacing="10" cellpadding="0">
        <tr>
          <td valign="top">
          		 <h3><?php _e("Facebook Feed Arguments.") ?></h3>                 
                 <table width="80%" border="0" cellspacing="10" cellpadding="0">        
                  <tr>
                    <td align="right"><?php _e("Message") ?></td>
                    <td><textarea name="fb_feed_message" cols="30" rows="2"><?php echo $fb_feed_message ?></textarea></td>
                  </tr>
                  <tr>
                    <td align="right"><?php _e("Link") ?></td>
                    <td><textarea name="fb_feed_link" cols="30" rows="2"><?php echo $fb_feed_link ?></textarea></td>
                  </tr>
                  <tr>
                    <td align="right"><?php _e("Name") ?></td>
                    <td><textarea name="fb_feed_name" cols="30" rows="2"><?php echo $fb_feed_name ?></textarea></td>
                  </tr>      
                  <tr>
                    <td align="right"><?php _e("Caption") ?></td>
                    <td><textarea name="fb_feed_caption" cols="30" rows="2"><?php echo $fb_feed_caption ?></textarea></td>
                  </tr>
                  <tr>
                    <td align="right"><?php _e("Description") ?></td>
                    <td><textarea name="fb_feed_description" cols="30" rows="2"><?php echo $fb_feed_description ?></textarea></td>
                  </tr> 
                </table>          
          
          </td>
          <td valign="top">
          
            	<h3><?php _e("The following tags can be used.") ?></h3>    
                <table width="80%" border="0" cellspacing="10" cellpadding="0">
                  <tr>
                    <td width="60" align="right">%blog_name%</td>
                    <td><em><?php _e("Blog Name") ?></em></td>
                  </tr>
                  <tr>
                     <td align="right">%blog_description%</td>
                    <td><em><?php _e("Blog description") ?></em></td>
                  </tr>
                  <tr>
                    <td align="right">%blog_url%</td>
                    <td><em><?php _e("Blog Url") ?></em></td>
                  </tr>
                  <tr>
                    <td align="right">%post_title%</td>
                    <td><em><?php _e("Post Title") ?></em></td>
                  </tr> 
                  <tr>
                    <td align="right">%post_excerpt%</td>
                    <td><em><?php _e("Post Excerpt") ?></em></td>
                  </tr>
                  <tr>
                    <td align="right">%post_url%</td>
                    <td><em><?php _e("Post Url") ?></em></td>
                  </tr>                                                          
                    
                </table>           
          
          
          </td>      
        </tr>
      </table> 
      
      <p><em><?php _e("This section allows to define how the comment posted on your Facebook News Feed will look like.") ?></em></p>
      
      <p><em><?php _e("Visit the <a href='' target='_blak'>plugin page</a> for more details.") ?></em></p>
    
    <p class="submit">
    <input type="submit" name="Submit" value="Update" class="button-primary" /><input type="submit" name="Restore_Default" value="<?php _e("Restore Default") ?>" class="button" />
    </p>
    </form>
    </div>

<?php }  

/**
 * Feed generator
 */  

function csw_fb_feed_generate(){
	
	$options = get_csw_options();
	
	$fb_feed_args = "{";	
	$fb_feed_args .= "message: '".csw_args_cleaner($options['fb_feed_message'])."',";						                             
	$fb_feed_args .= "link:'".csw_args_cleaner($options['fb_feed_link'])."',";
	$fb_feed_args .= "name:'".csw_args_cleaner($options['fb_feed_name'])."',";
	$fb_feed_args .= "caption:'".csw_args_cleaner($options['fb_feed_caption'])."',";
	$fb_feed_args .= "description:'".csw_args_cleaner($options['fb_feed_description'])."'";						
	$fb_feed_args .= "}";
	
	return $fb_feed_args;
	
}

function csw_args_cleaner($arg){
	
	global $post;
	
	$blog_name = get_bloginfo('name');
	$blog_description = get_bloginfo('description');
	$blog_url = get_bloginfo('wpurl');
	$post_title = $post->post_title;
	$post_excerpt = $post->post_excerpt;
	$post_url = get_permalink($post->ID);
	
	$find = array("%blog_name%","%blog_description%","%blog_url%","%post_title%","%post_excerpt%","%post_url%","\f","\v","\t","\r","\n","\\","\"");
	$replace  = array($blog_name,$blog_description,$blog_url,$post_title,$post_excerpt,$post_url,"","","","","","","'");
	
	$cleaned = str_replace( $find,$replace, $arg);	
	
	return $cleaned;
}
