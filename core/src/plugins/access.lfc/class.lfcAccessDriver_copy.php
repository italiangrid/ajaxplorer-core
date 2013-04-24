<?php
/*
 * Copyright 2007-2011 Charles du Jeu <contact (at) cdujeu.me>
 * This file is part of AjaXplorer.
 *
 * AjaXplorer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AjaXplorer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with AjaXplorer.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <http://www.ajaxplorer.info/>.
 */
defined('AJXP_EXEC') or die( 'Access not allowed');

/**
 * @package info.ajaxplorer.plugins
 * AJXP_Plugin to access a MySQL Server and make AjaXplorer act like a phpMyAdmin
 */
class lfcAccessDriver extends fsAccessDriver 

{
	/**
	* @var Repository
	*/
	public $repository;
	public $driverConf;
	protected $wrapperClassName;
	protected $urlBase;
    private static $loadedUserBookmarks;
  
private $metaStore;
  		
	function initRepository(){
	setcookie("repo_lfc", "true", 0, '', '', true, false);

	$this->lcg_gfal_infosys = 'egee-bdii.cnaf.infn.it:2170';
	
	session_start();
	if(!isset($_SESSION['user_id'])) {
		foreach ($_COOKIE as $key => $value) {
			if (substr($key, 0, 18)=="LFR_SESSION_STATE_") {
				$_SESSION['user_id'] = substr($key, 18);
			}
		}
	}
	$this->user_id = $_SESSION['user_id'];
		
	$user_proxy_dir = '/opt/dm/users/'.$this->user_id.'/';
//	$user_proxy_dir = '/opt/liferay-portal/tomcat/temp/users/'.$this->user_id.'/';
	$handle=opendir($user_proxy_dir);
	while ($file = readdir($handle))
	{		
		if (substr($file, 0, 7)=="x509up.") {
			$vo_name[] = substr($file, 7);	
//				error_log(print_r($vo_name,true));	
			} 
	}
	closedir($handle);
	
	if (isset($vo_name)){
	$_SESSION['vo'] = $vo_name;
			if ($_COOKIE['active']=="None_VO"){
			setcookie("active", "", time() - 3600);
			}	
	} else  {
			$vo_name[] = "None_VO";
			$_SESSION['vo'] = $vo_name;
				if ($_COOKIE['active']!="None_VO"){
				setcookie("active", "", time() - 3600);
				}
			}
			
	$vo = $_SESSION['vo'];
	
//error_log(print_r($vo,true));			    
	
	if (!isset($_COOKIE['active'])){ 
		setcookie("active", $vo[0], 0, '', '', true, false);
	}
	
	$vo_without_active = array_diff($vo, array($_COOKIE['active']));
	$vo_cookies = $_COOKIE['active'];
	if (sizeof($vo_without_active)>0) {
		$vo_cookies_without_active = implode($vo_without_active, "---");
		$vo_cookies.="---".$vo_cookies_without_active;
	}
		
	setcookie("vo_cookie", $vo_cookies, 0, '', '', true, false);

	$this->x509_user_proxy = '/opt/dm/users/'.$this->user_id.'/x509up.'.$_COOKIE['active'];	
//	$this->x509_user_proxy = '/opt/liferay-portal/tomcat/temp/users/'.$this->user_id.'/x509up.'.$_COOKIE['active'];	
	$this->vo_active = $_COOKIE['active'];
	
	

	###### Imposto il path iniziale ############## 	
	$user_vo_home_dir = '/var/www/html/ajp/data/plugins/auth.serial/'.$this->user_id.'/';
	$proxy_dir = $user_vo_home_dir."homevo.".$_COOKIE['active'];	
	
	error_log("proxy_dir is $proxy_dir");	
	$pippa = $_COOKIE['active'];
	error_log("il wall uguale a $pippa");
	
		
	if(file_exists($proxy_dir) && $_COOKIE['ajxp_wall']!=true && !isset($_COOKIE['ajxp_wall'])){
		$file1 = fopen($user_vo_home_dir."homevo.".$_COOKIE['active'], 'rt');
		while(!feof($file1))
		    {
		      $home_path_initial = fgets($file1);
		    }      
	    fclose($file1);
		setcookie("home_path_initial", $home_path_initial, 0, '', '', true, false);
		$_SESSION['home'] = $home_path_initial;
		error_log("home_path_initial1 is $home_path_initial");
	} 
	
	if ($_COOKIE['ajxp_wall']!="true" && !file_exists($proxy_dir)) { 		
		$handle2=opendir($user_vo_home_dir);
		$ourFileName = '/var/www/html/ajp/data/plugins/auth.serial/'.$this->user_id.'/homevo.'.$_COOKIE['active'];
		$ourFileHandle = fopen($ourFileName, 'w') or die("can't open file");
		$content = "/grid/".$_COOKIE['active'];
		fwrite($ourFileHandle, $content);
		fclose($ourFileHandle);
		$_SESSION['home'] = "/grid/".$_COOKIE['active'];
		setcookie("home_path_initial", $home_path_initial, 0, '', '', true, false);
		setcookie("ajxp_wall", "false", time() - 3600, '', '', true, false);
		setcookie("ajxp_jsreload", "", time() - 3600, '', '', true, false);
		closedir($handle2);	
		error_log("home_path_initial2 is $home_path_initial");
	}
	
	
	if ($_COOKIE['ajxp_wall']=="true" && !file_exists($proxy_dir)) { 		
		$handle2=opendir($user_vo_home_dir);
		$ourFileName = '/var/www/html/ajp/data/plugins/auth.serial/'.$this->user_id.'/homevo.'.$_COOKIE['active'];
		$ourFileHandle = fopen($ourFileName, 'w') or die("can't open file");
		$content = "/grid/".$_COOKIE['active'];
		fwrite($ourFileHandle, $content);
		fclose($ourFileHandle);
		$_SESSION['home'] = "/grid/".$_COOKIE['active'];
		setcookie("home_path_initial", $home_path_initial, 0, '', '', true, false);
		setcookie("ajxp_wall", "false", time() - 3600, '', '', true, false);
		setcookie("ajxp_jsreload", "", time() - 3600, '', '', true, false);
		closedir($handle2);	
		error_log("home_path_initial3 is $home_path_initial");
	}
	
	
	if ($_COOKIE['active']=="None_VO"){
	error_log("none-vo");
		$handle2=opendir($user_vo_home_dir);
		$ourFileName = '/var/www/html/ajp/data/plugins/auth.serial/'.$this->user_id.'/homevo.'.$_COOKIE['active'];
		$ourFileHandle = fopen($ourFileName, 'w') or die("can't open file");
		$content = "";
		fwrite($ourFileHandle, $content);
		fclose($ourFileHandle);
		$_SESSION['home'] = "/";
		setcookie("home_path_initial", $home_path_initial, 0, '', '', true, false);
		setcookie("ajxp_wall", "false", time() - 3600, '', '', true, false);
		setcookie("ajxp_jsreload", "", time() - 3600, '', '', true, false);
		closedir($handle2);
		error_log("home_path_initial4 is $home_path_initial");
	}
	
//	$command2 = "sudo X509_USER_PROXY=$this->x509_user_proxy LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys lcg-infosites --vo $this->vo_active lfc";
//	error_log("command2 is $command2");
	exec("sudo X509_USER_PROXY=$this->x509_user_proxy LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lcg-infosites --vo $this->vo_active lfc", $lfc_values);
	$_SESSION['lfc_server'] = $lfc_values[0];				
	$this->lfc_server = $_SESSION['lfc_server'];	

    @include_once("HTTP/WebDAV/Client.php");
	if(is_array($this->pluginConf)){
		$this->driverConf = $this->pluginConf;
	}else{
		$this->driverConf = array();
	}
		$create = $this->repository->getOption("CREATE");			
	
		$wrapperData = $this->detectStreamWrapper(true);
		$this->wrapperClassName = $wrapperData["classname"];
		$this->urlBase = $wrapperData["protocol"]."://".$this->repository->getId();
		
		$base = $this->urlBase;	
	}
	    
        
	function switchAction($action, $httpVars, $fileVars){



		$repo = ConfService::getRepository();

		if(!isSet($this->actions[$action])) return;
		$xmlBuffer = "";

		$selection = new UserSelection();
		$selection->initFromHttpVars($httpVars);



		if(isSet($dir) && $action != "upload") { 
			$safeDir = $dir; 
			$dir = SystemTextEncoding::fromUTF8($dir); 

		}
		// FILTER DIR PAGINATION ANCHOR
			
		if(isSet($dest)) {
			$dest = SystemTextEncoding::fromUTF8($dest);			
		}
		$mess = ConfService::getMessages();


		if(!isSet($this->actions[$action])) return;
		$selection = new UserSelection();
		$dir = $httpVars["dir"] OR "";

        if($this->wrapperClassName == "lfcAccessWrapper"){
            $dir = AccessWrapper::patchPathForBaseDir($dir);
        }
		$dir = AJXP_Utils::securePath($dir);
		if($action != "upload"){
			$dir = AJXP_Utils::decodeSecureMagic($dir);
		}
		
		$selection->initFromHttpVars($httpVars);
		if(!$selection->isEmpty()){
			$this->filterUserSelectionToHidden($selection->getFiles());			
		}
		$mess = ConfService::getMessages();
		
		$newArgs = RecycleBinManager::filterActions($action, $selection, $dir, $httpVars);
		if(isSet($newArgs["action"])) $action = $newArgs["action"];
		if(isSet($newArgs["dest"])) $httpVars["dest"] = SystemTextEncoding::toUTF8($newArgs["dest"]);//Re-encode!
 		// FILTER DIR PAGINATION ANCHOR
		$page = null;				
		if(isSet($dir) && strstr($dir, "%23")!==false){
			$parts = explode("%23", $dir);
			$dir = $parts[0];
			$page = $parts[1];
		}		
		$pendingSelection = "";
		$logMessage = null;
		$reloadContextNode = false;		
		switch($action)
		{				



			//------------------------------------
			//	DOWNLOAD
			//------------------------------------

		case "download";
			
				if($selection->isEmpty())
				{
					throw new AJXP_Exception("", 113);
				}
//				$logMessages = array();
//				$errorMessage = $this->download($selection->getFiles(), $logMessages);
//				if(count($logMessages))
//				{
//					$logMessage = join("\n", $logMessages);
//				}

				$message = $this->download($selection->getFiles(), $logMessages);
										
				AJXP_XMLWriter::header();
 				AJXP_XMLWriter::sendMessage($message['type']=="success"?$message['text']:null, $message['type']=="success"?null:$message['text']);
				AJXP_XMLWriter::close();

				return ;

        break;



			//------------------------------------
			//	SET HOME
			//------------------------------------

		case "sethome":

			if(isSet($httpVars["sub_action"])) {					
				$message = $this->homeAction($httpVars["path"], $httpVars["sub_action"]);				
				AJXP_XMLWriter::header();
 				AJXP_XMLWriter::sendMessage($message['type']=="success"?$message['text']:null, $message['type']=="success"?null:$message['text']);
				AJXP_XMLWriter::close();
				$home_path_initial=$_COOKIE['home'].$httpVars["path"];
				setcookie("home_path_initial", $home_path_initial, 0, '', '', true, false);
				setcookie("ajxp_wall", "false", time() - 3600, '', '', true, false);
				setcookie("ajxp_jsreload", "", time() - 3600, '', '', true, false);				
				return ;	
						
			} else {
				$user_vo_home_dir = '/var/www/html/ajp/data/plugins/auth.serial/'.$this->user_id.'/';
				$file = fopen($user_vo_home_dir."homevo.".$_COOKIE['active'], 'rt');
				while(!feof($file))
			    {
			      $home_path = fgets($file);
			    }      
			    fclose($file);						    	    
				$result = "";
                $result = $result."<Home>";
                $result = $result."<CurrentHome Value='".$home_path."' />";
                $result = $result."</Home>";
                return AJXP_XMLWriter::write($result , $print);
			}				
			
	   break;



			//------------------------------------
			//	SHARE WITH
			//------------------------------------

		case "sharewith":

			if(isSet($httpVars["sub_action"])) {
			error_log("sono in sharewith");
								$act = "add";
			//					$messId = "73";
			
				$message = $this->aclAction($httpVars["subject"], $httpVars["fileName"], $httpVars["sub_action"]);
				AJXP_XMLWriter::header();
 				AJXP_XMLWriter::sendMessage($message['type']=="success"?$message['text']:null, $message['type']=="success"?null:$message['text']);
				AJXP_XMLWriter::close();

				return ;			

			} else {	
			if (!isset($_SESSION['lastName'])) {
				session_start();
			}
							if($selection->isEmpty())
							{
								throw new AJXP_Exception("", 113);
							}
							$logMessages = array();
							
							$con = mysql_connect('localhost', 'PortalUser', 'PassPortalUser');
					
					if (!$con)
					  {
					  die('Could not connect: ' . mysql_error());
					  }
			
					mysql_select_db("PortalUser", $con);
			
					$data=mysql_query("select userInfo.userId, firstName, lastName, mail, subject from (((userInfo join certificate on userInfo.userId = certificate.userId) join userToVO on userInfo.userId = userToVO.userId) join VO on userToVO.idVO = VO.idVO) where VO.VO = '".$_COOKIE['active']."'");
					$result = "";

			//AJXP_XMLWriter::header(UsersInfo);

			$node = AJXP_Utils::decodeSecureMagic($httpVars["file"]);
			
						while($info = mysql_fetch_array( $data )) 		{	
						$subject = $info['subject'];
			//			$subject[] = $info['subject'];		
						$lastName[$subject] = $info['lastName'];
						$firstName[$subject] = $info['firstName'];
					}
			
			$_SESSION['lastName']=$lastName;
			
			$lfn_file=$_SESSION['home'].$httpVars['file'];
			
			  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys   /usr/bin/lfc-getacl $lfn_file", $getacl_output);
			
			$vo_share="false";
			foreach ($getacl_output as $output_line) {
			//	if (strstr($output_line, "group::rw")!="") {
				if (substr($output_line, 0, 9) == "group::rw") {
					$vo_share="true";
				}
			}
			
			foreach ($lastName as $key => $value) {
			$found_acl=false;
			$owner=false;
				foreach ($getacl_output as $output_line) {	
					if (strstr($output_line, "owner: ".$key)!="") {
						$owner=true;
					}
					if (!$owner && strstr($output_line, "user:".$key)!="") {
			//			$dn=strstr($output_line, "user:".$key);
						$users_with_acl[]=array($key, $value);
						$found_acl=true;
					}
					 
				}
				if (!$found_acl && !$owner) {
					$users_without_acl[]=array($key, $value);
				}
			}	

			$result = "";
            $result = $result."<UserList>";
            $result = $result."<VOShare Value=\"$vo_share\" />";
            if (isset($users_with_acl)) {
				foreach ($users_with_acl as $user) {						
                	    $result = $result."<UserWithACL Lastname=\"$user[1]\" Subject=\"$user[0]\" />";                                
            	}
            }
            if (isset($users_without_acl)) {
            	foreach ($users_without_acl as $user) {
                	    $result = $result."<UserWithoutACL Lastname=\"$user[1]\" Subject=\"$user[0]\" />";
            	}
            }
            $result = $result."</UserList>";

            return AJXP_XMLWriter::write($result , $print);		
	
            }	

			
			break;
		


			//------------------------------------
			//	REPLICATE
			//------------------------------------



			case "replicate":


				if(isSet($httpVars["sub_action"]) && ($httpVars["sub_action"]=="doReplica" || $httpVars["sub_action"]=="delReplica")) {
                                        $act = "add";
                                        $messId = "73";
                                	$message=$this->replicaAction($httpVars["hostName"], $httpVars["fileName"], $httpVars["sub_action"]);
	
        	                        AJXP_XMLWriter::header();
//                	                AJXP_XMLWriter::sendMessage($mess["ajxp_conf.".$messId].$httpVars["user_id"], null);
									AJXP_XMLWriter::sendMessage($message['type']=="success"?$message['text']:null, $message['type']=="success"?null:$message['text']);
                        	        AJXP_XMLWriter::close();
                        	        return ;
				} else {
							if($selection->isEmpty())
	                        {
	                                throw new AJXP_Exception("", 113);
	                        }
	                        $logMessages = array();
	                        $lfn_file=$_SESSION['home'].$httpVars['file'];
	                        exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lcg-lr lfn:$lfn_file", $srm_list);
	                        foreach ($srm_list as $srm) {
			                    $srm_substr = str_replace("srm://", "", $srm);
			                    $srm_pieces = explode("/", $srm_substr);	
			                    $se = $srm_pieces[0];
			                    $se_used_list[] = $se;
		                    }
	
		                    exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lcg-infosites -v 4 se", $se_site_row_list);
	
	
							$y=0;
							for ($i=2; $i<sizeof($se_site_row_list); $i++) {
								$se_site_list[$y] = preg_split("/[\s]+/", $se_site_row_list[$i]);
								$total_list[$y] = array($se_site_list[$y][1], $se_site_list[$y][0]);
				//				$total_list[$se_site_list[$y][0]] = array($se_site_list[$y][1], $se_site_list[$y][0]);
								sort($total_list);
								$y++;
							}
				
				
							foreach ($total_list as $total_info) {
								$se_total_list[] = $total_info[1];
								$site_total_list[$total_info[1]] = $total_info[0];
								if (in_array($total_info[1], $se_used_list)) {
									$se_used_sorted_list[] = $total_info[1];
								}
							}
							
							foreach ($se_used_list as $se_used) {
								if (!in_array($se_used, $se_total_list)) {
									$se_used_sorted_list[] = $se_used;
									$site_total_list[$se_used] = "***SE not available at moment***";
				
								}
							}
				
				
							$se_avail_list = array_diff($se_total_list, $se_used_list);
				
				                        $result = "";
				                        $result = $result."<SEList File=\"$lfn_file\">";
				
				
										foreach ($se_used_sorted_list as $se_host) {
				                                $result = $result."<SEUsed Site=\"$site_total_list[$se_host]\" Hostname=\"$se_host\" />";
				                        }
				                        foreach ($se_avail_list as $se_host) {
				                                $result = $result."<SEAvail Site=\"$site_total_list[$se_host]\" Hostname=\"$se_host\" />";
				                        }
				                        $result = $result."</SEList>";
				
				                        return AJXP_XMLWriter::write($result , $print);
	                  }
            break;





			//------------------------------------
			//	MORE INFO
			//------------------------------------


			case "more_info";

				if($selection->isEmpty())
				{
					throw new AJXP_Exception("", 113);
				}
				$logMessages = array();
					
				$lfn_file="lfn:/".$_SESSION['home'].$httpVars['file'];
				$lfn2_file=$_SESSION['home'].$httpVars['file'];  	
				
//				error_log("lfn_file is $lfn_file");
//				error_log("lfn2_file is $lfn2_file");	      
				
	        	exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server    /usr/bin/lcg-lr $lfn_file", $replica_values);
				exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server    /usr/bin/lcg-lg $lfn_file", $guid_values);
				exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server    /usr/bin/lfc-getacl $lfn2_file", $permission_values);				
				$owner = str_replace("# owner: ", "", $permission_values[1]);
				$group = str_replace("# group: ", "", $permission_values[2]);
	
				$result = "";
				
				$result = $result."<FileInfo GuidValue=\"$guid_values[0]\" LFNValue=\"$lfn2_file\">";
				$result = $result."<Owner Value=\"$owner\" />";
				$result = $result."<Group Value=\"$group\" />";
				$result = $result."<Permissions>";
				foreach ($permission_values as $value) {
					if (substr($value, 0, 4)=="user") {
						$result = $result."<User Value=\"$value\" />";
					} else if (substr($value, 0, 5)=="group") {
						$result = $result."<Group Value=\"$value\" />";
					} else if (substr($value, 0, 4)=="mask") {
						$result = $result."<Mask Value=\"$value\" />";
					} else if (substr($value, 0, 5)=="other") {
						$result = $result."<Other Value=\"$value\" />";
					}
				}
				$result = $result."</Permissions>";
				
//				$result = $result."<Permissions User=\"$permission_values[3]\" Group=\"$permission_values[4]\" Other=\"$permission_values[5]\" />";
				foreach ($replica_values as $rep_val) {
					$result = $result."<Replica Value=\"$rep_val\" />";
				}
				$result = $result."</FileInfo>";
				
				return AJXP_XMLWriter::write($result , $print);		
							
			break;
			


			
			
			//------------------------------------
			//	MOVE
			//------------------------------------

			case "move";
				
				if($selection->isEmpty())
				{

					throw new AJXP_Exception("", 113);
				}
				$success = $error = array();
				$dest = $_SESSION['home'].AJXP_Utils::decodeSecureMagic($httpVars["dest"]);


				if($selection->inZip()){
					// Set action to copy anycase (cannot move from the zip).
					$action = "copy";
					$this->extractArchive($dest, $selection, $error, $success);
				}else{

					$this->copyOrMove($dest, $selection->getFiles(), $error, $success, ($action=="move"?true:false));
				}
				
				if(count($error)){					
					throw new AJXP_Exception(SystemTextEncoding::toUTF8(join("\n", $error)));
				}else {
					$logMessage = join("\n", $success);
					AJXP_Logger::logAction(($action=="move"?"Move":"Copy"), array("files"=>$selection, "destination"=>$dest));
				}
				$reloadContextNode = true;
                if(!(RecycleBinManager::getRelativeRecycle() ==$dest && $this->driverConf["HIDE_RECYCLE"] == true)){
				$base = $_SESSION['home'];


				$destOK= str_replace($base , "/", $dest);
                      $reloadDataNode = $destOK;
                }
				
			break;





			//------------------------------------
			//	DELETE
			//------------------------------------
			case "delete";
			
				if($selection->isEmpty())
				{
					throw new AJXP_Exception("", 113);
				}
				$logMessages = array();
				$errorMessage = $this->delete($selection->getFiles(), $logMessages);
				if(count($logMessages))
				{
					$logMessage = join("\n", $logMessages);
				}
//				if($errorMessage) throw new AJXP_Exception(SystemTextEncoding::toUTF8($errorMessage));
//				AJXP_Logger::logAction("Delete", array("files"=>$selection));
				$reloadContextNode = true;

				$xmlBuffer='';
				$xmlBuffer .= AJXP_XMLWriter::sendMessage($errorMessage['type']=="success"?$errorMessage['text']:null, $errorMessage['type']=="success"?null:$errorMessage['text'], false);
                        	if(!isSet($pendingSelection)) $pendingSelection = "";
                        	$xmlBuffer .= AJXP_XMLWriter::reloadDataNode("", $pendingSelection, false);
		        return $xmlBuffer;
				
            break;
		




			//------------------------------------
			//	RENAME
			//------------------------------------
			case "rename";

			
				$file = $_SESSION['home'].AJXP_Utils::decodeSecureMagic($httpVars["file"]);				
				$filename_new = AJXP_Utils::decodeSecureMagic($httpVars["filename_new"]);								
				$pieces = explode("/", $file);				
				$file_old = end($pieces);				
				$file_new = str_replace($file_old , $filename_new, $file);
				exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server    /usr/bin/lfc-rename $file $file_new");																
				exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys  /usr/bin/lfc-ls $file_new", $new_lfn);

				$message['text']="Failed to rename from ".$file." to ".$file_new;
				$message['type']="fail";
                foreach ($new_lfn as $lfn) {
                        if (strstr($lfn, $file_new)!="") {
                                $message['text']="File successfully renamed from ".$file." to ".$file_new;
								$message['type']="success";
                        }
                }
					
				if ($message['type']=="success") {	
					foreach (file("/var/www/html/ajp/data/files/shared_files.txt") as $name) {
	    					if(str_replace("\r\n", "", $name) == $file) {
	       						$this->del_line_in_file("/var/www/html/ajp/data/files/shared_files.txt", $file);
								$handle = fopen("/var/www/html/ajp/data/files/shared_files.txt", "a+");
								fwrite($handle, $file_new."\r\n");
								fclose($handle);
								}
								
							}
				}			 	

				$reloadContextNode = true;
			
			break;



		
			//------------------------------------
			//	CREER UN REPERTOIRE / CREATE DIR
			//------------------------------------
			case "mkdir";
			        
				$messtmp="";
				$dirname=AJXP_Utils::decodeSecureMagic($httpVars["dirname"], AJXP_SANITIZE_HTML_STRICT);
				

				$dirname = substr($dirname, 0, ConfService::getCoreConf("NODENAME_MAX_LENGTH"));
				
				$newdir = $_SESSION['home'].$dir."/".$dirname;


				  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server    /usr/bin/lfc-mkdir -m 700 $newdir");

				if(isSet($error)){
					throw new AJXP_Exception($error);
				}
                $currentNodeDir = new AJXP_Node($this->urlBase.$dir);
                AJXP_Controller::applyHook("node.before_change", array(&$currentNodeDir));
				$messtmp.="$mess[38] ".SystemTextEncoding::toUTF8($dirname)." $mess[39] ";
				if($dir=="") {$messtmp.="/";} else {$messtmp.= SystemTextEncoding::toUTF8($dir);}
				$logMessage = $messtmp;
				$pendingSelection = $dirname;
				$reloadContextNode = true;
                $newNode = new AJXP_Node($this->urlBase.$dir."/".$dirname);
                AJXP_Controller::applyHook("node.change", array(null, $newNode, false));
                AJXP_Logger::logAction("Create Dir", array("dir"=>$dir."/".$dirname));

			break;
		


				
				
				

			//------------------------------------
			//	XML LISTING
			//------------------------------------
			case "ls":

				if ((!isset($_COOKIE['ajxp_wall'])) || ($_COOKIE['ajxp_wall']!="true")) { 						
					$user_vo_home_dir = '/var/www/html/ajp/data/plugins/auth.serial/'.$this->user_id.'/';
					$handle2=opendir($user_vo_home_dir);
					while ($file = readdir($handle2))
					{							
						if (substr($file, 0, 5)=="group"){
								$file = fopen($user_vo_home_dir."group", 'rt');
								while(!feof($file))
							    {
							      $home_path = fgets($file);
							    }      
							    fclose($file);
							    $ourFileName = '/var/www/html/ajp/data/plugins/auth.serial/'.$this->user_id.'/homevo.'.$_COOKIE['active'];
							    $ourFileHandle = fopen($ourFileName, 'w') or die("can't open file");
							    $content = $home_path;
							    fwrite($ourFileHandle, $content);
							    fclose($ourFileHandle);
							    $_SESSION['home'] = $home_path;
							    exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lfc-ls $home_path" , $nodes);
							    if (sizeof($nodes)==0){		
								    $newdir1 = $_SESSION['home']."/";
								    $newdir2 = $_SESSION['home']."/input/";		
								    $newdir3 = $_SESSION['home']."/output/";	
								 
									exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lfc-mkdir -m 700 $newdir1", $output1);
									exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lfc-mkdir -m 700 $newdir2", $output2);
									exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lfc-mkdir -m 700 $newdir3", $output3);
							  	}				  	
							    unlink('/var/www/html/ajp/data/plugins/auth.serial/'.$this->user_id.'/group');
							    $file_exist=true;			
						} else if (substr($file, 0, 5)!="group" && substr($file, 0, 7)=="homevo.") {
									$file_pieces = explode(".", $file);	
									if ($file_pieces[1]==$_COOKIE['active']){
										$file = fopen($user_vo_home_dir."homevo.".$file_pieces[1], 'rt');
										while(!feof($file))
									    {
									      $home_path = fgets($file);
									    }      
									    fclose($file);
									    $_SESSION['home'] = $home_path;
									    $file_exist=true;
					 			   }				
					 			}
					}
						
				} else if ($_COOKIE['ajxp_wall']=="true" && $_COOKIE['ajxp_wall']=="true" && $_SESSION['home']!="/grid/".$_COOKIE['active'] && $_COOKIE['ajxp_jsreload']=="true") {
					setcookie("ajxp_jsreload", "", time() - 3600);
						error_log("levo ultimo pezzo");
					$home_pieces = explode("/", $_SESSION['home']);
					$home_pieces_pop = array_pop($home_pieces);
					$home_path = implode("/", $home_pieces);				
					$_SESSION['home'] = $home_path;
				}	
				
				setcookie("home", $_SESSION['home'], 0, '', '', true, false);
		
				if(!isSet($dir) || $dir == "/") $dir = "";
				$lsOptions = $this->parseLsOptions((isSet($httpVars["options"])?$httpVars["options"]:"a"));

				$startTime = microtime();				
				$dir = AJXP_Utils::securePath(SystemTextEncoding::magicDequote($dir));


				if ($dir=="") {
					$path_right = $_SESSION['home'];
					$path = "";
				} else {
					$path = ($dir!= ""?($dir[0]=="/"?"":"/").$dir:"");
					$path_right = $_SESSION['home'].$dir;
					
				}
				
                $nonPatchedPath = $path;
                
                if($this->wrapperClassName == "lfcAccessWrapper") {
                    $nonPatchedPath = lfcAccessWrapper::unPatchPathForBaseDir($path);
                    error_log("nonPatchedPath1 is $nonPatchedPath");
                }

				$threshold = $this->repository->getOption("PAGINATION_THRESHOLD");
				if(!isSet($threshold) || intval($threshold) == 0) $threshold = 200;

				$limitPerPage = $this->repository->getOption("PAGINATION_NUMBER");
				if(!isset($limitPerPage) || intval($limitPerPage) == 0) $limitPerPage = 200;
				
				$limitPerPageOLD = $limitPerPage;
				
				if ($page>1) {
					$countFiles = $_SESSION['count'];
					$limitPerPage = $_SESSION['new_limitPerPage'][$page-1];
				} else {
					$countFiles = $this->countFiles($path_right, $limitPerPage, $threshold, !$lsOptions["f"]);
					$limitPerPage = $_SESSION['new_limitPerPage'][0];
				}								
//				$countFiles = $this->countFiles($path_right, !$lsOptions["f"]);				

				if($countFiles > $threshold){				
				error_log("sono dentro al if countfiles");
				error_log("page is $page");
					$offset = 0;
					$crtPage = 1;
					if(isSet($page)){
//						$offset = (intval($page)-1)*$limitPerPage;
						for ($i=1; $i<$page; $i++) {
							
//							$offset += $_SESSION['new_limitPerPage'][$i-1] - $_SESSION['pagination_info'][$page-2]["length"]; 
							$offset += $_SESSION['new_limitPerPage'][$i-1];
						
						$coppone=$_SESSION['pagination_info'][$page-2]["length"];
						error_log("pagination_info is $coppone");
						error_log("offset di $i is $offset");
						$crtPage = $page;
						$newlimit=$_SESSION['new_limitPerPage'][$i-1];
						error_log("limitPerPage is $newlimit");
						}
					}
					$totalPages = floor($countFiles / $limitPerPageOLD) + 1;
				}else{
					$offset = $limitPerPage = 0;
				}					
												
				$metaData = array();
				if(RecycleBinManager::recycleEnabled() && $dir == ""){
                    $metaData["repo_has_recycle"] = "true";
				}
				

				$metaData["path_right"] = $path_right;				
				$parentAjxpNode = new AJXP_Node($nonPatchedPath, $metaData);				
				$parentAjxpNode->loadNodeInfo();

				AJXP_XMLWriter::renderAjxpHeaderNode($parentAjxpNode);
				if(isSet($totalPages) && isSet($crtPage)){
					AJXP_XMLWriter::renderPaginationData(
						$countFiles, 
						$crtPage, 
						$totalPages,
						-1,
						$limitPerPageOLD,
						$_SESSION['pagination_info'][$crtPage-1]["position"],
						$_SESSION['pagination_info'][$crtPage-1]["length"],
						$_SESSION['count_folders']						
//						$this->countFiles($path_right, TRUE)
					);
					if(!$lsOptions["f"]){
						AJXP_XMLWriter::close();
						exit(1);
					}
				}
			
			$cursor = 0;
			$fullList = array("d" => array(), "z" => array(), "f" => array());		
			$fullListOne = array();





			date_default_timezone_set('UTC');
			$node_names=array();
			$nodes = $_SESSION['nodes'];
			foreach ($nodes as $nodeTotalInfo){
				$nodeTotalInfo = trim($nodeTotalInfo);
				$nodeTotalInfo = preg_replace('!\s+!', ' ', $nodeTotalInfo);
				$node_info = explode(" ", $nodeTotalInfo);
				array_push($node_names, $node_info[9]);	
				$node_total_info[$node_info[9]] = $nodeTotalInfo;
			}

			if(!empty($this->driverConf["SCANDIR_RESULT_SORTFONC"])){
				usort($nodes, $this->driverConf["SCANDIR_RESULT_SORTFONC"]);
			}
				
				
$numero=count($node_names);				
error_log("numero is $numero");


//			$fullList_log="";            
//           $node_total_info = $_SESSION['node_total_info'];
            
            
//			foreach ($_SESSION['hello'] as $nodeName){
			foreach ($node_names as $nodeName){
//				if($nodeName == "." || $nodeName == "..") continue;
			
//				$isLeaf = "";
//				if(!$this->filterNodeName($path, $nodeName, $isLeaf, $lsOptions)){
//					continue;
//				}
				
				if($offset > 0 && $cursor < $offset){
					$cursor ++;
					continue;
				}
				
				if($limitPerPage > 0 && ($cursor - $offset) >= $limitPerPage) {				
					break;
				}									
					
				$currentFile = $nonPatchedPath."/".$nodeName;	 	 
                $meta = array();    				
				$lfn_file=$_SESSION['home'].$currentFile;
				$shared = "false";
				foreach (file("/var/www/html/ajp/data/files/shared_files.txt") as $name) {
				    if(str_replace("\r\n", "", $name) == $lfn_file) {
				    	 $shared = "true";
				    }
				}
				$node_total_info[$nodeName].= " ".$shared;
	    		$meta["total_info"] = $node_total_info[$nodeName];
                $node = new AJXP_Node($currentFile, $meta); 
                $node->setLabel($nodeName);
                $node->loadNodeInfo();


				if(!empty($metaData["nodeName"]) && $metaData["nodeName"] != $nodeName){              
                    $node->setUrl($nonPatchedPath."/".$metaData["nodeName"]);
				}

                $nodeType = "d";
                   if($node->isLeaf()){
                    if(AJXP_Utils::isBrowsableArchive($nodeName)) {
                        if($lsOptions["f"] && $lsOptions["z"]){
                            $nodeType = "f";
                        }else{
                            $nodeType = "z";
                        }
                    }
                    else $nodeType = "f";
                }
							
				$fullList[$nodeType][$nodeName] = $node;	
				$fullListOne[] = $node;
				$cursor ++;
			}				

			array_map(array("AJXP_XMLWriter", "renderAjxpNode"), $fullListOne);
//			array_map(array("AJXP_XMLWriter", "renderAjxpNode"), $fullList["d"]);
//			array_map(array("AJXP_XMLWriter", "renderAjxpNode"), $fullList["z"]);
//			array_map(array("AJXP_XMLWriter", "renderAjxpNode"), $fullList["f"]);


	
			AJXP_Logger::debug("LS Time : ".intval((microtime()-$startTime)*1000)."ms");
			
			$result = "<VO Value=\"$vo\" />";
			AJXP_XMLWriter::write($result , $print);

			AJXP_XMLWriter::close();
			return;
				
			break;		
		}

		
		$xmlBuffer = "";
		if(isset($logMessage) || isset($errorMessage))
		{
			$xmlBuffer .= AJXP_XMLWriter::sendMessage((isSet($logMessage)?$logMessage:null), (isSet($errorMessage)?$errorMessage:null), false);			
		}				
		if($reloadContextNode){
			if(!isSet($pendingSelection)) $pendingSelection = "";
			$xmlBuffer .= AJXP_XMLWriter::reloadDataNode("", $pendingSelection, false);
		}
		if(isSet($reloadDataNode)){
			$xmlBuffer .= AJXP_XMLWriter::reloadDataNode($reloadDataNode, "", false);
		}
									
		return $xmlBuffer;
	}


			
	function parseLsOptions($optionString){
	

		// LS OPTIONS : dz , a, d, z, all of these with or without l
		// d : directories
		// z : archives
		// f : files
		// => a : all, alias to dzf
		// l : list metadata
		$allowed = array("a", "d", "z", "f", "l");
		$lsOptions = array();
		foreach ($allowed as $key){
			if(strchr($optionString, $key)!==false){
				$lsOptions[$key] = true;
			}else{
				$lsOptions[$key] = false;
			}
		}
		if($lsOptions["a"]){
			$lsOptions["d"] = $lsOptions["z"] = $lsOptions["f"] = true;
		}
		return $lsOptions;
	}


	
	function countFiles($path_right, $limitPerPage, $foldersOnly = false, $nonEmptyCheckOnly = false){
		$_SESSION['nodes']=array();
		$folders=array();
		$_SESSION['count'] = 0;
		$command = "sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server lfc-ls -cilTu $path_right";
		error_log("command is $command");
		$_SESSION['path']=$path_right;
		error_log("path_right33 is $path_right");
		exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lfc-ls -il $path_right" , $_SESSION['nodes']);
		$_SESSION['count']=count($_SESSION['nodes']);
				
if($_SESSION['count'] > $threshold){


error_log("ciao prodi");

		$nodes = $_SESSION['nodes'];
		$_SESSION['node_names']=array();
		$node_names=array();
		foreach ($nodes as $nodeTotalInfo){
			$nodeTotalInfo2 = trim($nodeTotalInfo);
			$nodeTotalInfo2 = preg_replace('!\s+!', ' ', $nodeTotalInfo2);
			$node_info = explode(" ", $nodeTotalInfo2);
//			array_push($node_names, $node_info[9]);	
//			$node_total_info2[$node_info[9]] = $nodeTotalInfo;
			$node_type = substr($node_info[1], 0, 1);
			if($node_type=="d"){ 
				array_push($folders, $nodeTotalInfo);											
			}					
											
		}			
//		$_SESSION['hello']=$node_names;
//		$_SESSION['node_total_info']= $node_total_info2;															
			$count_folders=count($folders);
			$_SESSION['count_folders'] = $count_folders;
			$iterations=round($_SESSION['count']/$limitPerPage);
			$super_nodes_all = array();
			$super_nodes_folders = array();
			$super_nodes_files = array();
			$_SESSION['new_limitPerPage'] = array();
			$offset=0;
			for ($i=0; $i<=$iterations; $i++) {
				$slice[$i] = array_slice($_SESSION['nodes'], $offset, $limitPerPage);
				$super_nodes[$i] = array();
				$super_nodes_folders[$i] = array();
				$super_nodes_files[$i] = array();
				foreach ($slice[$i] as $slice_elem) {
					$slice_elem2 = trim($slice_elem);
					$slice_elem2 = preg_replace('!\s+!', ' ', $slice_elem2);
					$node_info = explode(" ", $slice_elem2);
					$node_type = substr($node_info[1], 0, 1);
					if($node_type=="d"){ 
						array_push($super_nodes_folders[$i], $slice_elem);	
					} else {
						array_push($super_nodes_files[$i], $slice_elem);
					}
				}	
				$position[$i] = array_search($super_nodes_folders[$i][0], $folders);
				$count_right_folders[$i] = count($super_nodes_folders[$i]);
				$_SESSION['pagination_info'][$i] = array("position"=>$position[$i], "length"=>$count_right_folders[$i]); 		
				$super_nodes[$i] = array_merge($folders, $super_nodes_files[$i]);
				if ($i==0) {
					$super_nodes_all = $super_nodes[$i];
				} else {
					$super_nodes_all = array_merge($super_nodes_all, $super_nodes[$i]);
				}						
				$offset = $offset + $limitPerPage;
				$_SESSION['new_limitPerPage'][$i] = $limitPerPage + $count_folders - $count_right_folders[$i];
			}
			$_SESSION['nodes']=$super_nodes_all;
		
		}	
		return $_SESSION['count'];
	}



    /**
     * @param AJXP_Node $ajxpNode
     * @return void
     */

    function loadNodeInfo(&$ajxpNode){

		$metaData = $ajxpNode->metadata;

//
// SERVE A CARICARE LE INFO RECUPERATE DA LFC-LS
//

		$metas3=$metaData["total_info"];
		if (isset($metas3)) {
					date_default_timezone_set('Europe/Rome');
					$nodeTotalInfo = trim($metas3);
					$nodeTotalInfo = preg_replace('!\s+!', ' ', $nodeTotalInfo);
					$node_info = explode(" ", $nodeTotalInfo);
					$node_name = $node_info[9];
					$node_type = substr($node_info[1], 0, 1);
					$node_size = $node_info[5];
					if (strpos($node_info[8], ":")){
						$year = date("Y");
						$hour_minutes = $node_info[8];
					} else {
						$year = $node_info[8];
						$hour_minutes = "00:00";
					}
					$node_date = strtotime($node_info[7]." ".$node_info[6]." ".$year." ".$hour_minutes);

		}

         
        if(!isSet($metaData["is_file"])){
	    	if (isset($metas3)) {    		    	
		    	$isLeaf = $node_type!="d";		
		    	} 
		    else {
               $isLeaf = is_file($ajxpNode->getUrl()) || AJXP_Utils::isBrowsableArchive($nodeName);            
               }	
        $metaData["is_file"] = ($isLeaf?"1":"0");
                        
        }else{
            $isLeaf = $metaData["is_file"] == "0" ? true : false;            
        }
		
		$Data["shared_users"] = $node_info[10];
		$Data["shared_vo"] = substr($node_info[1], 4, 1) == "r" ? "true" : "false";
		$Data["shared"] = $Data["shared_vo"]."/".$Data["shared_users"];
		if ($Data["shared_vo"]=="true") {
			$metaData["shared"] = "All VO";	
		} else if ($Data["shared_users"]=="true") {
			$metaData["shared"] = "Some users";
		} else if (isset($metas3)) {
			$metaData["shared"] = "Nobody";
		}
		
		
            $metaData["icon"] = AJXP_Utils::mimetype($nodeName, "image", !$isLeaf);
            if($metaData["icon"] == "folder.png"){
            }
 //       }

  		
        //if($lsOptions["l"]){
/*
        $metaData["file_group"] = @filegroup($ajxpNode->getUrl()) || "unknown";
        $metaData["file_owner"] = @fileowner($ajxpNode->getUrl()) || "unknown";
        $fPerms = @fileperms($ajxpNode->getUrl());
        if($fPerms !== false){
            $fPerms = substr(decoct( $fPerms ), ($isLeaf?2:1));
        }else{
            $fPerms = '0000';
        }
        $metaData["file_perms"] = $fPerms;
*/
        $datemodif = $this->date_modif($ajxpNode->getUrl());
	if (isset($metas3)) {
            $metaData["ajxp_modiftime"] = $node_date;

        }

        $Data["bytesize"] = 0;
        if($isLeaf){
	    if (isset($metas3)) {
	    	$Data["bytesize"] = $node_size;
            } else {
		$Data["bytesize"] = $this->filesystemFileSize($ajxpNode->getUrl());
	    }
        }
        $metaData["filesize"] = AJXP_Utils::roundSize($Data["bytesize"]);
/*        if(AJXP_Utils::isBrowsableArchive($nodeName)){
            $metaData["ajxp_mime"] = "ajxp_browsable_archive";
        }

*/

      $ajxpNode->mergeMetadata($metaData);

    }
        
    

	/**
	 * Test if userSelection is containing a hidden file, which should not be the case!
	 * @param UserSelection $files
	 */
	function filterUserSelectionToHidden($files){
		foreach ($files as $file){
			$file = basename($file);
			if(AJXP_Utils::isHidden($file) && !$this->driverConf["SHOW_HIDDEN_FILES"]){
				throw new Exception("Forbidden");
			}
			if($this->filterFile($file) || $this->filterFolder($file)){
				throw new Exception("Forbidden");
			}
		}
	}
	
	function filterNodeName($nodePath, $nodeName, &$isLeaf, $lsOptions){
		
		$isLeaf = (is_file($nodePath."/".$nodeName) || AJXP_Utils::isBrowsableArchive($nodeName));
		if(AJXP_Utils::isHidden($nodeName) && !$this->driverConf["SHOW_HIDDEN_FILES"]){
			return false;
		}

		$nodeType = "d";

		if($isLeaf){
			if(AJXP_Utils::isBrowsableArchive($nodeName)) $nodeType = "z";
			else $nodeType = "f";
		}		

		if(!$lsOptions[$nodeType]) return false;
		if($nodeType == "d"){			
			if(RecycleBinManager::recycleEnabled() 
				&& $nodePath."/".$nodeName == RecycleBinManager::getRecyclePath()){
					return false;
				}
					
			return !$this->filterFolder($nodeName);

		}else{
			if($nodeName == "." || $nodeName == "..") return false;
			if(RecycleBinManager::recycleEnabled() 
				&& $nodePath == RecycleBinManager::getRecyclePath() 
				&& $nodeName == RecycleBinManager::getCacheFileName()){
				return false;
			}

			return !$this->filterFile($nodeName);
		}

	}
	

    function filterFile($fileName){
    
        $pathParts = pathinfo($fileName);
        if(array_key_exists("HIDE_FILENAMES", $this->driverConf) && !empty($this->driverConf["HIDE_FILENAMES"])){
            if(!is_array($this->driverConf["HIDE_FILENAMES"])) {
                $this->driverConf["HIDE_FILENAMES"] = explode(",",$this->driverConf["HIDE_FILENAMES"]);
            }
            foreach ($this->driverConf["HIDE_FILENAMES"] as $search){
                if(strcasecmp($search, $pathParts["basename"]) == 0) return true;
            }
        }
        if(array_key_exists("HIDE_EXTENSIONS", $this->driverConf) && !empty($this->driverConf["HIDE_EXTENSIONS"])){
            if(!is_array($this->driverConf["HIDE_EXTENSIONS"])) {
                $this->driverConf["HIDE_EXTENSIONS"] = explode(",",$this->driverConf["HIDE_EXTENSIONS"]);
            }
            foreach ($this->driverConf["HIDE_EXTENSIONS"] as $search){
                if(strcasecmp($search, $pathParts["extension"]) == 0) return true;
            }
        }
        return false;
    }

    function filterFolder($folderName){


        if(array_key_exists("HIDE_FOLDERS", $this->driverConf) && !empty($this->driverConf["HIDE_FOLDERS"])){
            if(!is_array($this->driverConf["HIDE_FOLDERS"])) {
                $this->driverConf["HIDE_FOLDERS"] = explode(",",$this->driverConf["HIDE_FOLDERS"]);
            }
            foreach ($this->driverConf["HIDE_FOLDERS"] as $search){
                if(strcasecmp($search, $folderName) == 0) return true;

            }
        }


        return false;
    }
	

/*	function countFiles($dirName, $foldersOnly = false, $nonEmptyCheckOnly = false){
		$handle=opendir($dirName);
		$count = 0;
		while (strlen($file = readdir($handle)) > 0)
		{
			if($file != "." && $file !=".." 
				&& !(AJXP_Utils::isHidden($file) && !$this->driverConf["SHOW_HIDDEN_FILES"])
				&& !($foldersOnly && is_file($dirName."/".$file)) ){
				$count++;
				if($nonEmptyCheckOnly) break;
			}			
		}
		closedir($handle);
		return $count;
	}
*/			
	function date_modif($file)
	{
		$tmp = @filemtime($file) or 0;
		return $tmp;// date("d,m L Y H:i:s",$tmp);
	}
	
	function changeMode($filePath)
	{
		$chmodValue = $this->repository->getOption("CHMOD_VALUE");
		if(isSet($chmodValue) && $chmodValue != "")
		{
			$chmodValue = octdec(ltrim($chmodValue, "0"));
			call_user_func(array($this->wrapperClassName, "changeMode"), $filePath, $chmodValue);
		}		
	}

    function filesystemFileSize($filePath){
        $bytesize = filesize($filePath);
        if(method_exists($this->wrapperClassName, "getLastRealSize")){
            $last = call_user_func(array($this->wrapperClassName, "getLastRealSize"));
            if($last !== false){
                $bytesize = $last;
            }
        }
        if($bytesize < 0){
            $bytesize = sprintf("%u", $bytesize);
        }

        return $bytesize;
    }

	/**
	 * Extract an archive directly inside the dest directory.
	 *
	 * @param string $destDir
	 * @param UserSelection $selection
	 * @param array $error
	 * @param array $success
	 */
/*
	function extractArchive($destDir, $selection, &$error, &$success){
		require_once(AJXP_BIN_FOLDER."/pclzip.lib.php");
		$zipPath = $selection->getZipPath(true);
		$zipLocalPath = $selection->getZipLocalPath(true);
		if(strlen($zipLocalPath)>1 && $zipLocalPath[0] == "/") $zipLocalPath = substr($zipLocalPath, 1)."/";
		$files = $selection->getFiles();

		$realZipFile = call_user_func(array($this->wrapperClassName, "getRealFSReference"), $this->urlBase.$zipPath);
		$archive = new PclZip($realZipFile);
		$content = $archive->listContent();		
		foreach ($files as $key => $item){// Remove path
			$item = substr($item, strlen($zipPath));
			if($item[0] == "/") $item = substr($item, 1);			
			foreach ($content as $zipItem){
				if($zipItem["stored_filename"] == $item || $zipItem["stored_filename"] == $item."/"){
					$files[$key] = $zipItem["stored_filename"];
					break;
				}else{
					unset($files[$key]);
				}
			}
		}
		AJXP_Logger::debug("Archive", $files);
		$realDestination = call_user_func(array($this->wrapperClassName, "getRealFSReference"), $this->urlBase.$destDir);
		AJXP_Logger::debug("Extract", array($realDestination, $realZipFile, $files, $zipLocalPath));
		$result = $archive->extract(PCLZIP_OPT_BY_NAME, $files, 
									PCLZIP_OPT_PATH, $realDestination, 
									PCLZIP_OPT_REMOVE_PATH, $zipLocalPath);
		if($result <= 0){
			$error[] = $archive->errorInfo(true);
		}else{
			$mess = ConfService::getMessages();
			$success[] = sprintf($mess[368], basename($zipPath), $destDir);
		}
	}
*/	
	function copyOrMove($destDir, $selectedFiles, &$error, &$success, $move = false)
	{
		AJXP_Logger::debug("CopyMove", array("dest"=>$destDir));
		$mess = ConfService::getMessages();
	
		foreach ($selectedFiles as $selectedFile)
		{

			$this->copyOrMoveFile($destDir, $_SESSION['home'].$selectedFile, $error, $success, $move);
		}
	}
	
	function renameAction($actionName, $httpVars)
	{
		$filePath = SystemTextEncoding::fromUTF8($httpVars["file"]);
		$newFilename = SystemTextEncoding::fromUTF8($httpVars["filename_new"]);
		return $this->rename($filePath, $newFilename);
	}
	
	function rename($filePath, $filename_new)
	{	
		$nom_fic=basename($filePath);
		$mess = ConfService::getMessages();
		$filename_new=AJXP_Utils::sanitize(SystemTextEncoding::magicDequote($filename_new), AJXP_SANITIZE_HTML_STRICT);
		$filename_new = substr($filename_new, 0, ConfService::getCoreConf("NODENAME_MAX_LENGTH"));
		$old=$this->urlBase."/$filePath";
		if(!$this->isWriteable($old))
		{
			throw new AJXP_Exception($mess[34]." ".$nom_fic." ".$mess[99]);
		}
		$new=dirname($old)."/".$filename_new;
		if($filename_new=="")
		{
			throw new AJXP_Exception("$mess[37]");
		}
		if(file_exists($new))
		{
			throw new AJXP_Exception("$filename_new $mess[43]"); 
		}
		if(!file_exists($old))
		{
			throw new AJXP_Exception($mess[100]." $nom_fic");
		}
        $oldNode = new AJXP_Node($old);
        AJXP_Controller::applyHook("node.before_change", array(&$oldNode));
		rename($old,$new);
        AJXP_Controller::applyHook("node.change", array($oldNode, new AJXP_Node($new), false));
	}
	

	
	function mkDir($crtDir, $newDirName)
	{
		$mess = ConfService::getMessages();
		if($newDirName=="")
		{
			return "$mess[37]";
		}
		if(file_exists($this->urlBase."$crtDir/$newDirName"))
		{
			return "$mess[40]"; 
		}
		if(!$this->isWriteable($this->urlBase."$crtDir"))
		{
			return $mess[38]." $crtDir ".$mess[99];
		}

        $dirMode = 0775;
		$chmodValue = $this->repository->getOption("CHMOD_VALUE");
		if(isSet($chmodValue) && $chmodValue != "")
		{
			$dirMode = octdec(ltrim($chmodValue, "0"));
			if ($dirMode & 0400) $dirMode |= 0100; // User is allowed to read, allow to list the directory
			if ($dirMode & 0040) $dirMode |= 0010; // Group is allowed to read, allow to list the directory
			if ($dirMode & 0004) $dirMode |= 0001; // Other are allowed to read, allow to list the directory
		}
		$old = umask(0);
		mkdir($this->urlBase."$crtDir/$newDirName", $dirMode);
		umask($old);
		return null;		
	}
	


	function createEmptyFile($crtDir, $newFileName, $content = "")
	{
		$mess = ConfService::getMessages();
		if($newFileName=="")
		{
			return "$mess[37]";
		}
		if(file_exists($this->urlBase."$crtDir/$newFileName"))
		{
			return "$mess[71]";
		}
		if(!$this->isWriteable($this->urlBase."$crtDir"))
		{
			return "$mess[38] $crtDir $mess[99]";
		}
		$fp=fopen($this->urlBase."$crtDir/$newFileName","w");
		if($fp)
		{
			if($content != ""){
				fputs($fp, $content);
			}
			if(preg_match("/\.html$/",$newFileName)||preg_match("/\.htm$/",$newFileName))
			{
				fputs($fp,"<html>\n<head>\n<title>New Document - Created By AjaXplorer</title>\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">\n</head>\n<body bgcolor=\"#FFFFFF\" text=\"#000000\">\n\n</body>\n</html>\n");
			}
			$this->changeMode($this->urlBase."$crtDir/$newFileName");
			fclose($fp);
			return null;
		}
		else
		{
			return "$mess[102] $crtDir/$newFileName (".$fp.")";
		}		
	}
	
	

	function download($selectedFiles, &$logMessages)
	{
	
	error_log("sono a download");
	
	$user_home_dir = '/var/www/html/ajp/data/personal/'.$this->user_id.'/';
			if(!file_exists($user_home_dir) || !is_dir($user_home_dir)){
				mkdir($user_home_dir);
			}

//$mess = ConfService::getMessages();
		$message['text']="";
        $message['type']="success";


		foreach ($selectedFiles as $selectedFile)
		{	
			$lfn_file="lfn:/".$_SESSION['home'].$selectedFile;  	
			$ff=$selectedFile;
			error_log("ff is $ff");			
			$file_pieces=explode("/",$selectedFile);
			$file_name = end($file_pieces);
			$fs_file="file:/var/www/html/ajp/data/personal/".$_SESSION['user_id']."/".$file_name;
			
			error_log("lfn_file is $lfn_file");
			error_log("fs_file is $fs_file");	      
			
			$vo_active = $_COOKIE['active'];
			
			$replica_values2=array();
			exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server    /usr/bin/lcg-lr $lfn_file", $replica_values2);
			error_log("++++++++++++++++++++++++");
			error_log(print_r($replica_values,true));
			exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server    /usr/bin/lcg-getturls -p http $replica_values2[0] 2>&1", $getturls_output);
			$pieces3 = explode(" ", $getturls_output[1]);
			if (in_array("not", $pieces3)) {
				error_log("Got Irix");
			}
			error_log("++++++++++++++++++++++++");

			error_log(print_r($getturls_output,true));
				
			$command = "sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys  lcg-cp --vo $vo_active $lfn_file  $fs_file";
			
			error_log("command is $command");
						
        	exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys  /usr/bin/lcg-cp --vo $vo_active $lfn_file  $fs_file", $download_values);
			
			
			
			error_log(print_r($download_values,true));
			
			
			$downloaded_file_name=substr($fs_file, 5);
			if (!file_exists($downloaded_file_name)) {
				$message['text'].="Failed download of file ".$file_name."\n";
				$message['type']="fail";
			} else {
				$message['text'].="File ".$file_name." successfully downloaded into My Files repository\n";
			}

		}
		return $message;
}


	function delete($selectedFiles, &$logMessages)
	{
		$mess = ConfService::getMessages();
		$message['text']="";
		$message['type']="success";
		error_log(print_r($selectedFiles,true));
		foreach ($selectedFiles as $selectedFile)
		{
			$nodes = array();
			$del_output = array();
			$replica_values = array();

			if($selectedFile == "" || $selectedFile == DIRECTORY_SEPARATOR)
			{
				return $mess[120];
			}

			$dirname1 = "lfn:/".$_SESSION['home'].$selectedFile;
			$dirname2 = $_SESSION['home'].$selectedFile;
			$dirname2_pieces = explode("/", $dirname2);
			$itemname = end($dirname2_pieces);
			$message['text'].="- Item ".$itemname." :\n";
			array_pop($dirname2_pieces);
			$parent_dir = implode("/", $dirname2_pieces); 
			$dirname3 = end($dirname2_pieces);
 
			exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lfc-ls $dirname2" , $nodes);
				    
			if (sizeof($nodes)==0){		
				exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-rm -r $dirname2");

				exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-ls $parent_dir", $new_nodes);
				
				foreach ($new_nodes as $node) {
					if ($node==$dirname3){
						$message['text'].="Failure on deletion of directory ".$dirname2."\n";
                                		$message['type']="fail";
					}				
				}
				if ($message['type']=="success") {
					$message['text'].="The directory ".$dirname2." has been deleted\n";
				}

			} else if(sizeof($nodes)==1 && $nodes[0]==$dirname2) {
				exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lcg-lr $dirname1", $replica_values);

				foreach ($replica_values as $value) {

					exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys   /usr/bin/lcg-del -v  $value 2>&1", $del_output);

					if (sizeof($del_output)==0) {
						$message['text'].="Failure on deletion of replica ".$value."\n";
                        $message['type']="fail";
					} else {
						foreach ($del_output as $output_line) {
                        	if (strstr($output_line, $value." - DELETED")!="") {
								$message['text'].="Replica ".$value." has been deleted\n";
								$deletion_ok=true;
                            }
                        }
						if (!$deletion_ok) {
                                $message['text'].="Failure on deletion of replica ".$value."\n";
                                $message['type']="fail";
						}
					}


				}

				if($message['type']=="success")	{
					foreach (file("/var/www/html/ajp/data/files/shared_files.txt") as $name) {
    						if(str_replace("\r\n", "", $name) == $dirname2) {
       							$this->del_line_in_file("/var/www/html/ajp/data/files/shared_files.txt", str_replace("\r\n", "", $name));
						}							
					}
				}
			} else {
				$message['text'].="Attention: the selected folder is not empty. Please delete its content before removing it.";
				$message['type']="fail";
			}		

		$message['text'].="\n";
		}

		return $message;
	}
	

	
	
	function copyOrMoveFile($destDir, $srcFile, &$error, &$success, $move = false)
	{
		$mess = ConfService::getMessages();		

		$destFile = $destDir."/".basename($srcFile);		
		$realSrcFile = $srcFile;

		if(dirname($realSrcFile)==dirname($destFile))
		{
			if($move){
				$error[] = $mess[101];
				return ;
			}else{
				$base = basename($srcFile);
				$i = 1;
				if(is_file($realSrcFile)){
					$dotPos = strrpos($base, ".");
					if($dotPos>-1){
						$radic = substr($base, 0, $dotPos);
						$ext = substr($base, $dotPos);
					}
				}
				// auto rename file
				$i = 1;
				$newName = $base;
				while (file_exists($this->urlBase.$destDir."/".$newName)) {
					$suffix = "-$i";
					if(isSet($radic)) $newName = $radic . $suffix . $ext;
					else $newName = $base.$suffix;
					$i++;
				}
				$destFile = $this->urlBase.$destDir."/".$newName;
			}
		}
			if($move){
                AJXP_Controller::applyHook("node.before_change", array(new AJXP_Node($realSrcFile)));
				if(file_exists($destFile)) unlink($destFile);				


//			  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server  lfc-rename $realSrcFile $destFile");
			  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server   /usr/bin/lfc-rename $realSrcFile $destFile");

				  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys   /usr/bin/lfc-ls $destFile", $new_lfn);


$message['text']="Failed to rename from ".$realSrcFile." to ".$destFile;
$message['type']="fail";
                        foreach ($new_lfn as $lfn) {
                                if (strstr($lfn, $destFile)!="") {
                                        $message['text']="File successfully renamed from ".$realSrcFile." to ".$destFile;
										$message['type']="success";
                                }
                        }
		
				
				
			if ($message['type']=="success") {	
							foreach (file("/var/www/html/ajp/data/files/shared_files.txt") as $name) {
    						if(str_replace("\r\n", "", $name) == $realSrcFile) {
       						$this->del_line_in_file("/var/www/html/ajp/data/files/shared_files.txt", $realSrcFile);
							$handle = fopen("/var/www/html/ajp/data/files/shared_files.txt", "a+");
							fwrite($handle, $destFile."\r\n");
							fclose($handle);
							}
							
						}

			}




				AJXP_Controller::applyHook("node.change", array(new AJXP_Node($realSrcFile), new AJXP_Node($destFile), false));
			}else{
				try{
                    if(call_user_func(array($this->wrapperClassName, "isRemote"))){
                        $src = fopen($realSrcFile, "r");
                        $dest = fopen($destFile, "w");
                        if($dest !== false){
                            while (!feof($src)) {
                                stream_copy_to_stream($src, $dest, 4096);
                            }
                            fclose($dest);
                        }
                        fclose($src);
                    }else{
                        copy($realSrcFile, $destFile);
                    }
					AJXP_Controller::applyHook("node.change", array(new AJXP_Node($realSrcFile), new AJXP_Node($destFile), true));
				}catch (Exception $e){
					$error[] = $e->getMessage();
					return ;					
				}
			}
		
		if($move)
		{
			// Now delete original
			// $this->deldir($realSrcFile); // both file and dir
			$messagePart = $mess[74]." ".SystemTextEncoding::toUTF8($destDir);
			if(RecycleBinManager::recycleEnabled() && $destDir == RecycleBinManager::getRelativeRecycle())
			{
				RecycleBinManager::fileToRecycle($srcFile);
				$messagePart = $mess[123]." ".$mess[122];
			}
			if(isset($dirRes))
			{
				$success[] = $mess[117]." ".SystemTextEncoding::toUTF8(basename($srcFile))." ".$messagePart." (".SystemTextEncoding::toUTF8($dirRes)." ".$mess[116].") ";
			}
			else 
			{
				$success[] = $mess[34]." ".SystemTextEncoding::toUTF8(basename($srcFile))." ".$messagePart;
			}
		}
		else
		{			
			if(RecycleBinManager::recycleEnabled() && $destDir == "/".$this->repository->getOption("RECYCLE_BIN"))
			{
				RecycleBinManager::fileToRecycle($srcFile);
			}
			if(isSet($dirRes))
			{
				$success[] = $mess[117]." ".SystemTextEncoding::toUTF8(basename($srcFile))." ".$mess[73]." ".SystemTextEncoding::toUTF8($destDir)." (".SystemTextEncoding::toUTF8($dirRes)." ".$mess[116].")";	
			}
			else 
			{
				$success[] = $mess[34]." ".SystemTextEncoding::toUTF8(basename($srcFile))." ".$mess[73]." ".SystemTextEncoding::toUTF8($destDir);
			}
		}
		
	}

	// A function to copy files from one directory to another one, including subdirectories and
	// nonexisting or newer files. Function returns number of files copied.
	// This function is PHP implementation of Windows xcopy  A:\dir1\* B:\dir2 /D /E /F /H /R /Y
	// Syntaxis: [$number =] dircopy($sourcedirectory, $destinationdirectory [, $verbose]);
	// Example: $num = dircopy('A:\dir1', 'B:\dir2', 1);

	function dircopy($srcdir, $dstdir, &$errors, &$success, $verbose = false) 
	{
		$num = 0;
		//$verbose = true;
		if(!is_dir($dstdir)) mkdir($dstdir);
		if($curdir = opendir($srcdir)) 
		{
			while($file = readdir($curdir)) 
			{
				if($file != '.' && $file != '..') 
				{
					$srcfile = $srcdir . "/" . $file;
					$dstfile = $dstdir . "/" . $file;
					if(is_file($srcfile)) 
					{
						if(is_file($dstfile)) $ow = filemtime($srcfile) - filemtime($dstfile); else $ow = 1;
						if($ow > 0) 
						{
							try { 
								$tmpPath = call_user_func(array($this->wrapperClassName, "getRealFSReference"), $srcfile);
								if($verbose) echo "Copying '$tmpPath' to '$dstfile'…";
								copy($tmpPath, $dstfile);
								$success[] = $srcfile;
								$num ++;
							}catch (Exception $e){
								$errors[] = $srcfile;
							}
						}
					}
					else
					{
						if($verbose) echo "Dircopy $srcfile";
						$num += $this->dircopy($srcfile, $dstfile, $errors, $success, $verbose);
					}
				}
			}
			closedir($curdir);
		}
		return $num;
	}
	
	function simpleCopy($origFile, $destFile)
	{
		return copy($origFile, $destFile);
	}
	
	public function isWriteable($dir, $type="dir")
	{
		return is_writable($dir);
	}
	
	function deldir($location)
	{
		if(is_dir($location))
		{
            AJXP_Controller::applyHook("node.before_change", array(new AJXP_Node($location)));
			$all=opendir($location);
			while ($file=readdir($all))
			{
				if (is_dir("$location/$file") && $file !=".." && $file!=".")
				{
					$this->deldir("$location/$file");
					if(file_exists("$location/$file")){
						rmdir("$location/$file"); 
					}
					unset($file);
				}
				elseif (!is_dir("$location/$file"))
				{
					if(file_exists("$location/$file")){
						unlink("$location/$file"); 
					}
					unset($file);
				}
			}
			closedir($all);
			rmdir($location);
		}
		else
		{
			if(file_exists("$location")) {
                AJXP_Controller::applyHook("node.before_change", array(new AJXP_Node($location)));
				$test = @unlink("$location");
				if(!$test) throw new Exception("Cannot delete file ".$location);
			}
		}
		if(basename(dirname($location)) == $this->repository->getOption("RECYCLE_BIN"))
		{
			// DELETING FROM RECYCLE
			RecycleBinManager::deleteFromRecycle($location);
		}
	}
	
	/**
	 * Change file permissions 
	 *
	 * @param String $path
	 * @param String $chmodValue
	 * @param Boolean $recursive
	 * @param String $nodeType "both", "file", "dir"
	 */
	function chmod($path, $chmodValue, $recursive=false, $nodeType="both", &$changedFiles)
	{
	    $realValue = octdec(ltrim($chmodValue, "0"));
		if(is_file($this->urlBase.$path)){
			if($nodeType=="both" || $nodeType=="file"){
				call_user_func(array($this->wrapperClassName, "changeMode"), $this->urlBase.$path, $realValue);
				$changedFiles[] = $path;
			}
		}else{
			if($nodeType=="both" || $nodeType=="dir"){
				call_user_func(array($this->wrapperClassName, "changeMode"), $this->urlBase.$path, $realValue);				
				$changedFiles[] = $path;
			}
			if($recursive){
				$handler = opendir($this->urlBase.$path);
				while ($child=readdir($handler)) {
					if($child == "." || $child == "..") continue;
					// do not pass realValue or it will be re-decoded.
					$this->chmod($path."/".$child, $chmodValue, $recursive, $nodeType, $changedFiles);
				}
				closedir($handler);
			}
		}
	}
	



//	function updateUserRole($userId, $roleId, $addOrRemove, $updateSubUsers = false){

	function aclAction($subject, $fileName, $sub_action){
			

				$lfn_file=$_SESSION['home'].$fileName;

				$pieces = explode("/", $lfn_file);
				$file_old = end($pieces);

				$lfn_dir = str_replace($file_old, "", $lfn_file);		



			if ($sub_action=="addACL") {

//				$DN_User = AJXP_Utils::decodeSecureMagic($httpVars["DN_user"]);
				  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys    /usr/bin/lfc-setacl -m u:'$subject':rx,m:rx $lfn_dir");
				  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys    /usr/bin/lfc-setacl -m u:'$subject':rwx,m:rwx,g::--- $lfn_file");


				   exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys   /usr/bin/lfc-getacl $lfn_file", $getacl_output);

$message['text']="Failure on setting ACL to user ".$subject." for file ".$lfn_file;
$message['type']="fail";
                        foreach ($getacl_output as $output_line) {
                                if (strstr($output_line, "user:".$subject)!="") {
										$message['text']="ACL correctly set to user ".$subject." for file ".$lfn_file;
                                        $message['type']="success";
                                }
                        }
              

						if ($message['type']=="success"){

						foreach (file("/var/www/html/ajp/data/files/shared_files.txt") as $name) {
    						if(str_replace("\r\n", "", $name) == $lfn_file) {
    							$shared=true;
    						}
						}

						if(!$shared) {
							$handle = fopen("/var/www/html/ajp/data/files/shared_files.txt", "a+");
							fwrite($handle, $lfn_file."\r\n");
							fclose($handle);
						} 	
						}


                        
             } else if ($sub_action=="delACL") {
error_log("subject is $subject");            
             
             	  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys   /usr/bin/lfc-setacl -d u:'$subject':rwx $lfn_file");
             	
             	  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys   /usr/bin/lfc-getacl $lfn_file", $getacl_output);
             	
//error_log(print_r($getacl_output,true));

$message['text']="ACL correctly removed to user ".$subject." for file ".$lfn_file;
$message['type']="success";
$found_acl=false;
 
//$last=$_SESSION['lastName']; 
 
                        foreach ($getacl_output as $output_line) {
                                if (strstr($output_line, "user:".$subject)!="") {
										$message['text']="Failure on removing ACL to user ".$subject." for file ".$lfn_file;
                                        $message['type']="fail";
                                }
                                if ($message['type']=="success"){
                                
                                	foreach ($_SESSION['lastName'] as $key => $value) {
                                		if (strstr($output_line, "user:".$key)!="") {
											$found_acl=true;
											
                                		}		
                                	}
                                }
                        }
                                                                      
                        
                        if ($found_acl == false){                    
						 	foreach (file("/var/www/html/ajp/data/files/shared_files.txt") as $name) {
    							if(str_replace("\r\n", "", $name) == $lfn_file) {	 					
    							

    							$name_right=str_replace("\r\n", "", $name);
    								$this->del_line_in_file("/var/www/html/ajp/data/files/shared_files.txt", $name_right);
    							}
							}

						}
             
             
             } else if ($sub_action=="addVOshare") {
error_log("sono in aclAction");            
	          exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-setacl -m g::rw- $lfn_file");
             	
             	  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-getacl $lfn_file", $getacl_output);
             	
//error_log(print_r($getacl_output,true));

$message['text']="ACL correctly added to VO ".$vo." for file ".$lfn_file;
$message['type']="success";
 
                        foreach ($getacl_output as $output_line) {
                                if (strstr($output_line, "group::---")!="") {
					$message['text']="Failure on adding ACL to VO for file ".$lfn_file;
                                        $message['type']="fail";
                                }
                        }
    
             } else if ($sub_action=="delVOShare") {
error_log("sono in delVOShare");            
	          exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-setacl -m g::--- $lfn_file");
             	
             	  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-getacl $lfn_file", $getacl_output);
             	
//error_log(print_r($getacl_output,true));


$message['text']="ACL correctly removed to all VO ".$vo." for file ".$lfn_file;
$message['type']="success";
 
                        foreach ($getacl_output as $output_line) {
                                if (substr($output_line, 0, 9)=="group::rw") {
	                                	$message['text']="Failure on removing ACL to all VO for file ".$lfn_file;
                                        $message['type']="fail";
                                }
                        }

                        
    
                        
             } else {
error_log("sono in aclAction delVOshare");

                  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-setacl -m g::--- $lfn_file");
                                     

                  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-getacl $lfn_file", $getacl_output);
                  
                  
//error_log(print_r($getacl_output,true));

$message['text']="ACL correctly removed to VO ".$vo." for file ".$lfn_file;
$message['type']="success";
$found_acl=false;                    
                        foreach ($getacl_output as $output_line) {
                                if (substr($output_line, 0, 9)=="group::rw") {
                                        $message['text']="Failure on removing ACL to VO for file ".$lfn_file;
                                        $message['type']="fail";
                                }
                                if ($message['type']=="success"){
/* IL SEGUENTE CODICE COMMENTATO SERVE PER ELIMINARE ACL ANCHE PER UTENTI ESTERNI AL PORTALE;                                 	
                                		if (strstr($output_line, "user:/")!="") {
                                			$line_pieces = explode(":", $output_line);	
                                			$subject_string = $line_pieces[1];   
                                			error_log("subject_string is $subject_string");       
                                			exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys lfc-setacl -d u:'$subject_string':rwx $lfn_file");
                                		}		
                                	*/
                                	
                                	/* IL SEGUENTE CODICE COMMENTATO SERVE PER ELIMINARE ACL SOLO PER UTENTI DEL PORTALE; EVENTUALI UTENTI NON-PORTALE MANTENGONO LE LORO ACL*/
                                	foreach ($_SESSION['lastName'] as $key => $value) {
                                		if (strstr($output_line, "user:".$key)!="") {
                                			exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-setacl -d u:'$key':rwx $lfn_file");
                                		}		
                                	}
                                	
                                }
                        }
  
 
                  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-getacl $lfn_file", $getacl_output_2);
                  
                        foreach ($getacl_output_2 as $output_line) {
                                if ($message['type']=="success"){    
                       /* IL SEGUENTE CODICE COMMENTATO SERVE PER CONTROLLARE SE CI SONO ANCORA ACL ANCHE PER UTENTI ESTERNI AL PORTALE          	
                                		if (strstr($output_line, "user:/")!="") {
                                			$message['text']="Failure on removing ACL to at least 1 user for file ".$lfn_file;
                                			$message['type']="fail";
                                			$found_acl=true;
                                		}		
                                	*/
                                	/* IL SEGUENTE CODICE COMMENTATO SERVE PER CONTROLLARE SE CI SONO ANCORA ACL SOLO PER UTENTI DEL PORTALE; EVENTUALI ACL DI UTENTI NON-PORTALE NON VENGONO CONTROLLATE*/                          	                                
                                	foreach ($_SESSION['lastName'] as $key => $value) {
                                		if (strstr($output_line, "user:".$key)!="") {
                                			$message['text']="Failure on removing ACL to at least 1 user for file ".$lfn_file;
                                			$message['type']="fail";
                                			$found_acl=true;
                                		}		
                                	}   
                                	                             
                                }
                        }                                              
                        
                        if ($found_acl == false){                    
						 	foreach (file("/var/www/html/ajp/data/files/shared_files.txt") as $name) {
    							if(str_replace("\r\n", "", $name) == $lfn_file) {	 					
    							

    							$name_right=str_replace("\r\n", "", $name);
    								$this->del_line_in_file("/var/www/html/ajp/data/files/shared_files.txt", $name_right);
    							}
							}

						}
             }
             
             
              

//$this->del_line_in_file("/var/www/html/ajp/data/files/shared_files.txt", "/grid/gridit/bencivenni/test1/alignment_out_942");
return $message;

	}




function homeAction ($path, $sub_action){
	
		error_log("path0 is $path");	
		
		if (isset($_COOKIE['root']) && $_COOKIE['root']!="stop" ){
				$path = str_replace("---","/", $_COOKIE['root']).$path;	
				setcookie("root", "", time() - 3600);
		} else if ($path!="/grid/".$_COOKIE['active']) {
			$path=$_SESSION['home'].$path;	
		}
		
		error_log("path is $path");
	
		$user_vo_home_dir = '/var/www/html/ajp/data/plugins/auth.serial/'.$this->user_id.'/';
		$handle2=opendir($user_vo_home_dir);
		while ($file = readdir($handle2))
		{					
			$ourFileName = '/var/www/html/ajp/data/plugins/auth.serial/'.$this->user_id.'/homevo.'.$_COOKIE['active'];
			$ourFileHandle = fopen($ourFileName, 'w') or die("can't open file");
			$arr = file($ourFileName);
			unset($arr[0]);
			$content = $path;
			$fwrite = fwrite($ourFileHandle, $content);
			if (!$fwrite) {
				$message['text']="Attention: failure on setting home to ".$path;
				$message['type']="fail";
			} else {
				$message['text']="Home successfully set to ".$path;
				$message['type']="success";
			}
			fclose($ourFileHandle);
		}
		closedir($handle2);	

		return $message;

}


	

function replicaAction($hostName, $fileName, $sub_action){


        $lfn_file="lfn:".$_SESSION['home'].$fileName;
		if ($sub_action=="doReplica") {
              exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys    /usr/bin/lcg-rep -d $hostName $lfn_file", $output);
			  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys   /usr/bin/lcg-lr $lfn_file", $replicas);

			  $message['text']="Replica failed on ".$hostName;
			  $message['type']="fail";
			  foreach ($replicas as $replica) {
                        	if (strstr($replica, $hostName)!="") {
                                	$message['text']="File successfully replicated on ".$hostName;
                                	$message['type']="success";
                        	}
                	}	
		} else {
			  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys   /usr/bin/lcg-del -s $hostName $lfn_file", $output);
			  exec("sudo X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys   /usr/bin/lcg-lr $lfn_file", $replicas);
			  $message['text']="File successfully deleted from ".$hostName;
			  $message['type']="success";
              foreach ($replicas as $replica) {
                  			if (strstr($replica, $hostName)!="") {
                                    $message['text']="Replica deletion from ".$hostName." failed";
                                    $message['type']="fail";
                            }
                    }
		}		
		$output[0]=$part_out;
		return $message;

}





    function recursivePurge($dirName, $purgeTime){
//error_log("sto per chiamare AJXP_Node di parent 4");
        $handle=opendir($dirName);
        $count = 0;
        while (strlen($file = readdir($handle)) > 0)
        {
            if($file == "" || $file == ".."  || AJXP_Utils::isHidden($file) ){
                continue;
            }
            if(is_file($dirName."/".$file)){
                $time = filemtime($dirName."/".$file);
                $docAge = time() - $time;
                if( $docAge > $purgeTime){
                    $node = new AJXP_Node($dirName."/".$file);
                    AJXP_Controller::applyHook("node.before_change", array($node));
                    unlink($dirName."/".$file);
                    AJXP_Controller::applyHook("node.change", array($node));
                    AJXP_Logger::logAction("Purge", array("file" => $dirName."/".$file));
                    print(" - Purging document : ".$dirName."/".$file."\n");
                }
            }else{
                $this->recursivePurge($dirName."/".$file, $purgeTime);
            }
        }
        closedir($handle);


    }
    


	function del_line_in_file($filename, $text_to_delete)
{
  // split the string up into an array
  $file_array = array();
       
  $file = fopen($filename, 'rt');
  if($file)
  {
    while(!feof($file))
    {
      $val = fgets($file);
      if(is_string($val))
        array_push($file_array, $val);
    }      
     
    fclose($file);
  }
       
  // delete from file
  for($i = 0; $i < count($file_array); $i++)
  {
    if(strstr($file_array[$i], $text_to_delete))
    {
      if($file_array[$i] == $text_to_delete . "\r\n") $file_array[$i] = '';
    }
  }
       
  // write it back to the file
  $file_write = fopen($filename, 'wt');       
  if($file_write)
  {
    fwrite($file_write, implode("", $file_array));
    fclose($file_write);
  }
}

    
}


/*				
exec("sudo chown apache:apache /opt/liferay-portal/tomcat/temp/users/19254/x509up.gridit");			
				
$descriptorspec = array(
   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
   2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
);

$cwd = '/tmp';
$env = array(
'X509_USER_PROXY' => '/opt/liferay-portal/tomcat/temp/users/19254/x509up.gridit',
'LFC_HOST' =>'lfcserver.cnaf.infn.it');

$process = proc_open('lfc-ls -ciLlTu /grid/gridit/', $descriptorspec, $pipes, $cwd, $env);


$nodes = explode("\n", stream_get_contents($pipes[1]));
error_log(print_r($nodes,true));

fclose($pipes[1]);
*/		

?>
