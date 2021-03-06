/*
 * @package Comments Switcher 0.2.1
 * Plugin URI: http://web-argument.com/wordpress-comments-switcher/
 *
**/

var WPCSwitcher = {};

(function( $ ){

WPCSwitcher = {
				
				Settings : {
							appId  : "",
							status : true, // check login status
							cookie : true, // enable cookies to allow the server to access the session
							xfbml : true,  // parse XFBML
							oauth : true, //enables OAuth 2.0
							loginBtn : "fb_login",
							fb_logout : "fb_logout",
							AfterLoginCallback : function(){},
							AfterLogoutCallback : function(){}	 
				},
				
				UserInfo : {
						   fuid:"",	
						   name:"",						   
						   email:"",
						   pic:""									 
				},								 
				
				Init : function(options){ 
				   
							$.extend(WPCSwitcher.Settings,options);
							
							if (WPCSwitcher.Settings.appId == "") WPCSwitcher.LogoutAction();
							
							FB.init(WPCSwitcher.Settings);
							
							FB.getLoginStatus(WPCSwitcher.ToggleStatus);
							
							var favComment = WPCSwitcher.GetCookie("csw");							
				
							if (typeof favComment != "undefined" && favComment == "fb") {
								WPCSwitcher.ShowTab("fb");
								WPCSwitcher.HideTab("guest");	
							} else {
								WPCSwitcher.ShowTab("guest");
								WPCSwitcher.HideTab("fb");
							}							
							
							$("#guest_login").click(function(){
									WPCSwitcher.ShowTab("guest");
									WPCSwitcher.HideTab("fb");
									return false;								
							});
				
							$("#"+WPCSwitcher.Settings.loginBtn).click(WPCSwitcher.LoginAction);	
							
							$("#"+WPCSwitcher.Settings.fb_logout).click(WPCSwitcher.LogoutAction);
							
							$("#fbcommentform .req, #fbcommentform input.email").click(WPCSwitcher.Validate.Reset);
							
							$("#fb_submit").click(WPCSwitcher.PreSubmit);								
												   
				   
				},
				
				ToggleStatus : function(response) {
				   if (response.status != "connected") {
					   
						WPCSwitcher.LogoutAction(response);
						
				   } else {

						var fb_uid = WPCSwitcher.GetCookie("csw_uid");
						var fb_uname = WPCSwitcher.GetCookie("csw_name");
						var fb_uemail = WPCSwitcher.GetCookie("csw_email");							
						
						WPCSwitcher.Populate({
							fuid : (typeof fb_uid != "undefined" )? fb_uid : "",
							name : (typeof fb_uname != "undefined" )? fb_uname : "",
							email : (typeof fb_uemail != "undefined" )? fb_uemail : "",
							pic : (typeof fb_uid != "undefined" )? "http://graph.facebook.com/"+fb_uid+"/picture" : ""
					   });					
				   }
				},
				
				LoginAction : function(){
					
					WPCSwitcher.IdleStatus("show");
					if (WPCSwitcher.UserInfo.fuid == ""){
						FB.login(function(response){										
							if(response.authResponse && response.status == "connected"){
								WPCSwitcher.UpdateUserInfo();									
							}	  
						},{scope: 'email,publish_stream'});
					} else {
						WPCSwitcher.ShowTab("fb");
						WPCSwitcher.HideTab("guest");
					}
					return false;
					
				},
				
				LogoutAction : function(){	
								  
					WPCSwitcher.IdleStatus("show");
					
					FB.logout();
					
					WPCSwitcher.SetCookie("csw_uid","",-1);
					WPCSwitcher.SetCookie("csw_name","",-1);
					WPCSwitcher.SetCookie("csw_email","",-1);
					
					WPCSwitcher.Populate({
					   fuid:"",
					   name:"",									   
					   email:"",
					   pic:""										   
					});
					
					WPCSwitcher.IdleStatus("hide");
					WPCSwitcher.ShowTab("guest");
					WPCSwitcher.HideTab("fb");
					
					WPCSwitcher.Settings.AfterLogoutCallback();
					
					return false;				  
								   
				},				
				
				PreSubmit : function(){	
									
					WPCSwitcher.IdleStatus("show");					
					var valid = true;
					$("#fbcommentform .req").each(function() {
						var input = $(this);									
						if (!WPCSwitcher.Validate.Control["non-empty"](input)){ 
							valid = WPCSwitcher.Validate.ThrowError(input);
						}
					});
					
					var email_input = $("#fbcommentform input.email");
					if (!WPCSwitcher.Validate.Control["email"](email_input)) {
							valid = WPCSwitcher.Validate.ThrowError(email_input);
					}
					
					if(valid) {
						if($("#fb_feed_post").is(":checked")) WPCSwitcher.GraphStreamPublish.Post();
						else WPCSwitcher.GraphStreamPublishApp.Post();							
					} else {
						WPCSwitcher.IdleStatus("hide");
						return false;
					}
				},
				
				Validate : {
							Control : {
									"email" : function(input){
										var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
										
										if (!reg.test(input.val())) return false;
										return true;	
									},
									
									"non-empty" : function(input){
										if (input.val() == "") return false;
										return true;								
									}
							},
							
							ThrowError : function(input){
									 input.addClass("fb_input_error");
									 return false;
							},
							
							Reset : function(obj){
								$(obj).removeClass("fb_input_error");
							}
				},
								
				GraphStreamPublish : {					
					Body : {},
					Post : function (){
						FB.api('/me/feed', 'post',this.Body, function(response) {
							if (!response || response.error) {
								alert('Error occured');
							} else {
								WPCSwitcher.GraphStreamPublishApp.Post();
							}
						});
					
					}
				},
				
				GraphStreamPublishApp : {					
					Body : {},
					Post : function (){
						FB.api('/'+WPCSwitcher.Settings.appId+'/feed', 'post',this.Body, function(response) {
							if (!response || response.error) {
								alert('Error occured');
							} else {
								WPCSwitcher.Submit();
							}
						});
					
					}
				},				
				
				Submit : function(){				
					$("#fbcommentform").submit();					
				},
				
				UpdateUserInfo : function(){

					  FB.api('/me', function(response) {
							   var query = FB.Data.query('select name, pic_square, email from user where uid={0}', response.id);
							   query.wait(function(rows) {
												   
								 WPCSwitcher.Populate({
									   name:rows[0].name,
									   fuid:response.id,
									   email:rows[0].email,
									   pic:rows[0].pic_square										   
								  });
								 
								  WPCSwitcher.SetCookie("csw_uid",response.id);
								  WPCSwitcher.SetCookie("csw_name",rows[0].name);
								  WPCSwitcher.SetCookie("csw_email",rows[0].email);
								  
								  WPCSwitcher.ShowTab("fb");
								  WPCSwitcher.HideTab("guest");  								  									  
																
								  WPCSwitcher.Settings.AfterLoginCallback();
								   
							   });
						  });										 
				},
				
				Populate : function(options){
					
					$.extend(WPCSwitcher.UserInfo,options);
					
					$("#fb_info .fb_user").attr("href","http://www.facebook.com/profile.php?id="+WPCSwitcher.UserInfo.fuid).text(WPCSwitcher.UserInfo.name);					  
					$("#fb_uid").val(WPCSwitcher.UserInfo.fuid);
					$("#fb_info img").attr("src",WPCSwitcher.UserInfo.pic);	
					$("#fb_form #fb_author").val(WPCSwitcher.UserInfo.name);
					$("#fb_form #fb_email").val(WPCSwitcher.UserInfo.email);
					$("#fb_form #fb_url").val("http://www.facebook.com/profile.php?id="+WPCSwitcher.UserInfo.fuid);
					
				},
				
				ShowTab : function(tab){
					if (tab == "fb") WPCSwitcher.IdleStatus("hide");
					WPCSwitcher.SetCookie("csw",tab);
					$("#"+tab+"_form").show();
					$("#"+tab+"_login").parent("li").addClass("active");
				},
				
				HideTab : function(tab){
					$("#"+tab+"_form").hide();
					$("#"+tab+"_login").parent("li").removeClass("active");
				},				
				
				SetCookie : function(c_name,value){
					
					var path = "/";
					// set time, it's in milliseconds
					var today = new Date();
					today.setTime( today.getTime() );
					var expires = arguments[2];
					if (typeof expires != "undefined"){
						expires = expires * 1000 * 60 * 60 * 24;
						var expires_date = new Date( today.getTime() + (expires) );
					}					
					document.cookie = c_name + "=" +escape( value ) + 
					( ( typeof expires_date != "undefined" ) ? ";expires=" + expires_date.toGMTString() : "" ) + 
					";path=" + path;
				},
				
				GetCookie : function(c_name){
					var i,x,y,ARRcookies=document.cookie.split(";");
					for (i=0;i<ARRcookies.length;i++){
					  x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
					  y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
					  x=x.replace(/^\s+|\s+$/g,"");
					  if (x==c_name){
						return unescape(y);
					  }
					}
				 },
				 
				 IdleStatus : function(status){
					 var fb_status_img = $(".fb_status_img");
					 fb_status_img[status]();
				 }
		  };
		  
$(document).ready(function(){
									
		var e = document.createElement('script');
		e.type = 'text/javascript';
		e.src = document.location.protocol +
			'//connect.facebook.net/en_US/all.js';
		e.async = true;
		$("#fb-root").append(e);	
 });			
		  	
		  
})( jQuery );			