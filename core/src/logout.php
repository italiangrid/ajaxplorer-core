<?php
	// Necessary to make "connection" with the glueCode
	define("AJXP_EXEC", true);
	$glueCode = "./plugins/auth.remote/glueCode.php";
	$secret = "*******";

	// Initialize the "parameters holder"
	global $AJXP_GLUE_GLOBALS;
	$AJXP_GLUE_GLOBALS = array();
	$AJXP_GLUE_GLOBALS["secret"] = $secret;
	$AJXP_GLUE_GLOBALS["plugInAction"] = "logout";
	//$AJXP_GLUE_GLOBALS["autoCreate"] = true;
	
	// NOTE THE md5() call on the password field.
	//$AJXP_GLUE_GLOBALS["login"] = array("name" => $_GET["userid"], "password" => md5('12345678'));
	
	// NOW call glueCode!
   	include($glueCode);
//   	setcookie("vo_cookie", "", time() - 3600, '', '', true, false);
   	setcookie("home", "", time() - 3600, '', '', true, false);
   	setcookie("is_admin", "", time() - 3600, '', '', true, false);
//}

?>
