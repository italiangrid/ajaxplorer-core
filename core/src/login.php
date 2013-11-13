<?php
	// Necessary to make "connection" with the glueCode
	define("AJXP_EXEC", true);
	$glueCode = "./plugins/auth.remote/glueCode.php";
	$secret = "123456";

	// Initialize the "parameters holder"
	global $AJXP_GLUE_GLOBALS;
	$AJXP_GLUE_GLOBALS = array();
	$AJXP_GLUE_GLOBALS["secret"] = $secret;
	$AJXP_GLUE_GLOBALS["plugInAction"] = "login";
	$AJXP_GLUE_GLOBALS["autoCreate"] = true;
	// NOTE THE md5() call on the password field.
	$AJXP_GLUE_GLOBALS["login"] = array("name" => $_GET["userid"], "password" => md5('12345678'));
	
	$user_id=$_GET["userid"];
	error_log("userid is $user_id");
	
	// NOW call glueCode!
   	$cookieName = "LFR_SESSION_STATE_".$_GET["userid"];
   	setcookie($cookieName, $_GET["userid"], 0, '', '', true, false);
   	setcookie("vo_cookie", "", time() - 3600, '', '', true, false);
   	setcookie("vo_changed", "", time() - 3600, '', '', true, false);
   	setcookie("home", "", time() - 3600, '', '', true, false);
   	setcookie("is_admin", "", time() - 3600, '', '', true, false);
   	setcookie("home_changed", "", time() - 3600, '', '', true, false);
   	setcookie("New_user", "", time() - 3600, '', '', true, false);
	    
	session_start();
	include ('/opt/dm/ajp/plugins/access.lfc/config.php');
	$conf = $config;
	$user_dir="/opt/dm/ajp/data/personal/".$user_id;
	if(is_dir($user_dir)){
	} else { 
		mkdir($user_dir, 0644);
	}
	
	$user_proxy_dir = $conf['user_proxy_dir_base_path'].$user_id.'/';
	$handle=opendir($user_proxy_dir);
	$vo=array();
	

	$file_path = '/opt/dm/ajp/data/plugins/auth.serial/'.$user_id.'/prefs.ser';
	error_log("file_path is $file_path");
	$_SESSION['New_user']="false";
	if(!file_exists($file_path)){
		error_log("file non esiste");
		setcookie("New_user", "true", 0, '', '', true, false);
	}
		
	while ($file = readdir($handle))
	{		
		if (substr($file, 0, 7)=="x509up.") {
			$vo[] = substr($file, 7);
		} 
	}
	closedir($handle);
	$i = count($vo);
	error_log("numero vo is $i");
	if(is_dir("/opt/dm/ajp/data/plugins/auth.serial/".$user_id)){
		if ($i>1) {
			$vo_cookies = implode($vo, "---");
			setcookie("vo_cookie", $vo_cookies, 0, '', '', true, false);
			$user_vo_home_dir = '/opt/dm/ajp/data/plugins/auth.serial/'.$user_id.'/';
			$x509_user_proxy = $user_proxy_dir.'/x509up.'.$vo[0];		
			$proxy_dir = $user_vo_home_dir."homevo.".$vo[0];
			if(file_exists($proxy_dir)){
				$file1 = fopen($user_vo_home_dir."homevo.".$vo[0], 'rt');
				while(!feof($file1)) {
					$home = fgets($file1);
				}      
				fclose($file1);
				error_log("stto il cookie 1");
				setcookie("home", $home, 0, '', '', true, false);
				$_SESSION['home']=$home;
			} else { 		
				$handle2=opendir($user_vo_home_dir);
				$ourFileName = $user_vo_home_dir.'homevo.'.$vo[0];
				$ourFileHandle = fopen($ourFileName, 'w') or die("can't open file");
				$content = "/grid/".$vo[0]."/";
				fwrite($ourFileHandle, $content);
				fclose($ourFileHandle);
				$_SESSION['home'] = "/grid/".$vo[0];
				error_log("setto il cookie 2");
				setcookie("home", $_SESSION['home'], 0, '', '', true, false);
				closedir($handle2);	
			}
		} else if ($i==1){
			$vo_cookies=$vo[0];
			error_log("0");
			setcookie("vo_cookie", $vo_cookies, 0, '', '', true, false);
			error_log("0-1");
			$user_vo_home_dir = '/opt/dm/ajp/data/plugins/auth.serial/'.$user_id.'/';
			$x509_user_proxy = $user_proxy_dir.'/x509up.'.$vo[0];		
			$proxy_dir = $user_vo_home_dir."homevo.".$vo[0];
			error_log("proxy_dir is $proxy_dir");
			error_log("$user_vo_home_dir");
			error_log(print_r($vo,true));
			if(file_exists($proxy_dir)){
				$file1 = fopen($user_vo_home_dir."homevo.".$vo[0], 'rt');
				while(!feof($file1)) {
					$home = fgets($file1);
					error_log("home is $home");
				}      
				fclose($file1);
				error_log("setto il cookie 3");
				error_log("home is $home");
				setcookie("home", $home, 0, '', '', true, false);
				$_SESSION['home']=$home;
			} else { 
			error_log("1");		
				$handle2=opendir($user_vo_home_dir);
				$ourFileName = $user_vo_home_dir.'homevo.'.$vo[0];
				error_log("2");	
				$ourFileHandle = fopen($ourFileName, 'w') or die("can't open file");
				error_log("3");	
				$content = "/grid/".$vo[0]."/";
				fwrite($ourFileHandle, $content);
				fclose($ourFileHandle);
				$_SESSION['home'] = "/grid/".$vo[0];
				error_log("setto il cookie 4");
				setcookie("home", $_SESSION['home'], 0, '', '', true, false);
				closedir($handle2);	
			}
		} else if ($i==0){
			$vo_name[] = "None_VO";
			setcookie("vo_cookie", "None_VO", 0, '', '', true, false);

			$_SESSION['home'] = "/";
			setcookie("home", "/", 0, '', '', true, false);
		}        
	}
	
include($glueCode);

?>
