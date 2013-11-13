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
class lfcAccessDriver extends fsAccessDriver {
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
		include ('config.php');
		$this->conf = $config;
		$this->lcg_gfal_infosys = $this->conf['lcg_gfal_infosys'];
		$this->shared_files = '/opt/dm/ajp/data/files/shared_files.txt';
		
		session_start();		
		
		if(!isset($_SESSION['user_id'])) {
			foreach ($_COOKIE as $key => $value) {
				if (substr($key, 0, 18)=="LFR_SESSION_STATE_") {
					$_SESSION['user_id'] = substr($key, 18);
				}
			}
		}
		$this->user_id = $_SESSION['user_id'];	
		$this->user_dir="/opt/dm/ajp/data/personal/".$_SESSION['user_id'];
		$this->user_vo_home_dir='/opt/dm/ajp/data/plugins/auth.serial/'.$this->user_id.'/';
		if(is_dir($this->user_dir)){
		} else { 
			mkdir($this->user_dir, 0644);
		}
		$user_proxy_dir = $this->conf['user_proxy_dir_base_path'].$this->user_id.'/';
		$user_vo_home_dir='/opt/dm/ajp/data/plugins/auth.serial/'.$this->user_id.'/';
		
		if($_COOKIE['vo_cookie']!="None_VO"){
			$vo_cookie=$_COOKIE['vo_cookie'];
			$vo=explode("---", $vo_cookie);
			$_SESSION['vo']=$vo[0];
			$this->vo_active=$vo[0];
			$this->x509_user_proxy = $user_proxy_dir.'/x509up.'.$vo[0];
			$home_path = $_COOKIE['home'];
			$_SESSION['home'] = $home_path;
		}				
		
		$handle=opendir($user_proxy_dir);
		$vo_name=array();
		$no_proxy="true";
		while ($file = readdir($handle))
			if (substr($file, 0, 7)=="x509up.") {
				$vo_name[] = substr($file, 7);
				$no_proxy="false";	
			}
		closedir($handle);
		$i = count($vo_name);
		
		if($no_proxy=="false" && $_COOKIE['vo_cookie']!="None_VO"){
			$new_vos = array_diff($vo_name, $vo);
			$new_vos2 = array_diff($vo, $vo_name);
			if (!empty($new_vos) || !empty($new_vos2)){
				$vo_name = str_replace($this->vo_active, "xxxx", $vo_name);
				array_unshift($vo_name, $this->vo_active);
				$vo_name = array_diff($vo_name, array('xxxx'));
				$vo_cookies = implode($vo_name, "---");	
				setcookie("vo_cookie", $vo_cookies, 0, '', '', true, false);
				$proxy_dir = $user_vo_home_dir."homevo.".$vo_name[0];
				if(file_exists($proxy_dir)){
					$file1 = fopen($user_vo_home_dir."homevo.".$vo_name[0], 'rt');
					while(!feof($file1)) {
						$home = fgets($file1);
					}      
					fclose($file1);
					setcookie("home", $home, 0, '', '', true, false);
					setcookie("user_home", $home, 0, '', '', true, false);
					$_SESSION['home']=$home;
				} else { 		
					$handle2=opendir($user_vo_home_dir);
					$ourFileName = $user_vo_home_dir.'homevo.'.$vo_name[0];
					$ourFileHandle = fopen($ourFileName, 'w') or die("can't open file");
					$content = "/grid/".$vo_name[0];
					fwrite($ourFileHandle, $content);
					fclose($ourFileHandle);
					$_SESSION['home'] = "/grid/".$vo_name[0];
					setcookie("home", $_SESSION['home'], 0, '', '', true, false);
					setcookie("user_home", $_SESSION['home'], 0, '', '', true, false);
					closedir($handle2);	
				}
			}			
		$_SESSION['lfc_server']="lfcserver.cnaf.infn.it";	
		$this->lfc_server="lfcserver.cnaf.infn.it";
		} else if ($no_proxy=="false" && ($_COOKIE['vo_cookie']=="None_VO" || !isset($_COOKIE['vo_cookie']))){
			if ($i>1) {
			error_log("maggiore di 1");
				$vo_cookies = implode($vo_name, "---");
				setcookie("vo_cookie", $vo_cookies, 0, '', '', true, false);
				$x509_user_proxy = $user_proxy_dir.'/x509up.'.$vo_name[0];		
				$proxy_dir = $user_vo_home_dir."homevo.".$vo_name[0];
				if(file_exists($proxy_dir)){
					$file1 = fopen($user_vo_home_dir."homevo.".$vo_name[0], 'rt');
					while(!feof($file1)) {
						$home = fgets($file1);
					}      
				fclose($file1);
				setcookie("home", $home, 0, '', '', true, false);
				setcookie("user_home", $home, 0, '', '', true, false);
				$_SESSION['home']=$home;
				} else { 		
					$handle2=opendir($user_vo_home_dir);
					$ourFileName = $user_vo_home_dir.'homevo.'.$vo_name[0];
					$ourFileHandle = fopen($ourFileName, 'w') or die("can't open file");
					$content = "/grid/".$vo_name[0];
					fwrite($ourFileHandle, $content);
					fclose($ourFileHandle);
					$_SESSION['home'] = "/grid/".$vo_name[0];
					setcookie("home", $home, 0, '', '', true, false);
					setcookie("user_home", $home, 0, '', '', true, false);
					closedir($handle2);	
				}
			} else if ($i=1){
				$vo_cookies=$vo_name[0];
				setcookie("vo_cookie", $vo_cookies, 0, '', '', true, false);
				$x509_user_proxy = $user_proxy_dir.'/x509up.'.$vo_name[0];		
				$proxy_dir = $user_vo_home_dir."homevo.".$vo_name[0];
				$this->vo_active=$vo_name[0];
				if(file_exists($proxy_dir)){
					$file1 = fopen($user_vo_home_dir."homevo.".$vo_name[0], 'rt');
					while(!feof($file1)) {
						$home = fgets($file1);
					}      
					fclose($file1);
					setcookie("home", $home, 0, '', '', true, false);
					setcookie("user_home", $home, 0, '', '', true, false);
					$_SESSION['home']=$home;
				} else { 		
					$handle2=opendir($user_vo_home_dir);
					$ourFileName = $user_vo_home_dir.'homevo.'.$vo_name[0];
					$ourFileHandle = fopen($ourFileName, 'w') or die("can't open file");
					$content = "/grid/".$vo_name[0];
					fwrite($ourFileHandle, $content);
					fclose($ourFileHandle);
					$_SESSION['home'] = "/grid/".$vo_name[0];
					setcookie("home", $home, 0, '', '', true, false);
					setcookie("userhome", $home, 0, '', '', true, false);
					closedir($handle2);	
				}
			}
		$this->vo_active=$vo_name[0];
		$command2 = "sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys lcg-infosites --vo $this->vo_active lfc";
//		error_log("command2 is $command2");
		exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lcg-infosites --vo $this->vo_active lfc", $lfc_values);
		$_SESSION['lfc_server'] = $lfc_values[0];
		$this->lfc_server = $_SESSION['lfc_server'];	
		} else {
			setcookie("vo_cookie", "None_VO", 0, '', '', true, false);
			setcookie("home", "/", 0, '', '', true, false);
			setcookie("user_home", "/", 0, '', '', true, false);
		}
	    @include_once("HTTP/WebDAV/Client.php");
		if(is_array($this->pluginConf)) {
			$this->driverConf = $this->pluginConf;
		} else {
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

		$dir_compl=$dir;			
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
		
			$path_user_dir="/opt/dm/ajp/data/personal/".$_SESSION['user_id'];
			$perm=fileperms($path_user_dir);
			if($perm!=16893){
				exec ("find $path_user_dir -type d -exec chmod 0775 {} +");
			}
			
			$path_user_list_download=$path_user_dir."/list_download.txt";
			if (!is_file($path_user_list_download)){
//				touch($path_user_list_download);
			}
			
			$X509_USER_PROXY=$this->x509_user_proxy;
			$lfc_server=$this->lfc_server;
			$home=$_SESSION['home'];
			$LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys;
			$user_id=$_SESSION['user_id'];
			$vo_active = $_SESSION['vo'];
	
			if($_REQUEST["check_proxy"]=='true'){
				$fileSizes=$_REQUEST["fileSizes"];
				$total_files_size=$_REQUEST["total_files_size"];
				if(($fileSizes>=1000 && $fileSizes<2000) || ($total_files_size>=10737418240 && $total_files_size<21474836480)){
					$proxytimeleft_max<3600;
				} else if (($fileSizes>=2000 && $fileSizes<4000) || ($total_files_size>=21474836480 && $total_files_size<32212254720)){
					$proxytimeleft_max<7200;
				} else if ($fileSizes>=4000 || $total_files_size>=32212254720){
					$proxytimeleft_max<14400;
				}
				$user_proxy_file=$this->conf['user_proxy_dir_base_path'].$this->user_id.'/'."x509up.".$vo_active;
				exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/voms-proxy-info -file $user_proxy_file -timeleft 2>&1", $proxytimeleft);
				$pippona=$proxytimeleft[0];
				error_log("pippona is $pippona");
				if ($proxytimeleft[0]<$proxytimeleft_max || $proxytimeleft[0]<600){
				$con = mysql_connect($this->conf['db_server'], $this->conf['db_portal_username'], $this->conf['db_portal_password']);	
				if (!$con) {
				  die('Could not connect: ' . mysql_error());
				}								
				mysql_select_db($this->conf['db_portal_name'], $con);			
				$id=$_SESSION['user_id'];
				$data=mysql_query('SELECT idVO from VO where VO="'.$vo_active.'"');
				if($data === FALSE) {
					die(mysql_error()); // TODO: better error handling
				}			
				$info = mysql_fetch_array($data);
				$idVO = $info['idVO'];
				$result	="";
		        $result = $result."<VOInfo idVO=\"$idVO\"></VOInfo>";
			    return AJXP_XMLWriter::write($result , $print);				
				break;
				}
			}
			
			if($_REQUEST["count_files_in_dir"]=='true'){
				$i=0;
				$x=0;
				$z=0;
				$size_tot=0;
				$offset=array();
				$offset[]=0;
				foreach($_REQUEST as $key => $value) {
					if (substr($key, 0, 4)=="dir_") {
						$selectedDir=$value;
						$dir=$_SESSION['home'].$selectedDir;
						$command="sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-ls -il $dir";
						exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-ls -il $dir" , $nodeTotalInfo[$x]);
						foreach ($nodeTotalInfo[$x] as $singlenodeTotalInfo) {
							$singlenodeTotalInfo = trim($singlenodeTotalInfo);
					        $singlenodeTotalInfo = preg_replace('!\s+!', ' ', $singlenodeTotalInfo);
					        $node_info = explode(" ", $singlenodeTotalInfo);
					        $node_type = substr($node_info[1], 0, 1);
					        if($node_type!="d"){
							    $size=$node_info[5];
						        $size_tot=$size_tot+$size;
						        $i++;
					        }
					   }
					   $x++;
					   $offset[]=$i;
					}
				}
				$result	="";
		        $result = $result."<DirInfo Files_tot=\"$i\" Size=\"$size_tot\">";
		        foreach($offset as $offset_i){
			        $result = $result."<Offset dir_num=\"$z\" offset=\"$offset_i\" />";
			        $z++;
		        }
		        $result = $result."</DirInfo>";
			    return AJXP_XMLWriter::write($result , $print);			
			}
			
	
			if(($_REQUEST["cloud"])=='true'){
				error_log("sono dentro a cloud");
				$random=$_REQUEST['random'];
				$port=$_REQUEST['port'];
				$mycloud="true";
				$username="anonymous";
		        $password = "anonymous";
		        $server_cloud = $this->conf['cloud_server'];
		        $folder="/free/";
		        $protocol ="http";
		        $port ="8085";
		        $i=0;
				$numElements=0;
				if($selection->isEmpty()) {
					throw new AJXP_Exception("", 113);
				}
						
				$con = mysql_connect($this->conf['db_server'], $this->conf['db_liferay_username'], $this->conf['db_liferay_password']);					
				if (!$con) {
					 die('Could not connect: ' . mysql_error());
				}				
				mysql_select_db($this->conf['db_liferay_name'], $con);			
				$id=$_SESSION['user_id'];
				$data=mysql_query("SELECT User_.emailAddress, User_.firstName, User_.lastName FROM User_ WHERE User_.userId=$id");
				if($data === FALSE) {
					die(mysql_error()); // TODO: better error handling
				}			
				$info = mysql_fetch_array($data);
				$lastName = $info['lastName'];
				$firstName = $info['firstName'];
				$mail = $info['emailAddress'];					
				$path_user_dir_cloud_transfer=$path_user_dir.$random;
				$path_user_dir_cloud_transfer_finished=$path_user_dir_cloud_transfer.'/ok';
				$path_user_dir_cloud_transfer_error=$path_user_dir_cloud_transfer.'/error';
				$path_user_dir_cloud_transfer_grid_error=$path_user_dir_cloud_transfer.'/grid_error';
	
				mkdir("$path_user_dir_cloud_transfer", 0777);
				touch("$path_user_dir_cloud_transfer_finished");
				touch("$path_user_dir_cloud_transfer_error");
				touch("$path_user_dir_cloud_transfer_grid_error");
	/*			
				foreach ($selection->getFiles() as $selectedFile) {
				$numElements=$numElements+1;
				if (($fd = fopen($path_user_dir_cloud_transfer_list, 'a')) !== false){ 
						$str2[$i] = substr($selectedFile, 1);
				    	fwrite($fd, $str2[$i].PHP_EOL);
				    	$c=$selectedFiles[$i];
				    	fclose($fd);
	 			    }
	 			 }
	*/	
	 			foreach ($selection->getFiles() as $selectedFile){  
					exec("php /opt/dm/ajp/plugins/access.lfc/retrieve.php '$selectedFile' '$X509_USER_PROXY' '$lfc_server' '$home' '$protocol' '$server_cloud' '$username' '$password' '$random' '$LCG_GFAL_INFOSYS' '$path_user_dir_cloud_transfer' '$numElements' '$mail' '$firstName' '$lastName' '$vo_active' '$folder' '$port' '$mycloud' '$i'> /dev/null &");
					$i++;
			
				}
			}
	
			if(($_REQUEST["big"])=='true'){
				error_log("sono in big");				
				$protocol=$_REQUEST['protocol'];
				$username=$_REQUEST['username'];
				$server=$_REQUEST['server'];
				$folder=$_REQUEST['folder'];
				if($protocol=="sftp"){
					if($username=="root" && $folder==""){
                                		$folder="/root/";
                                	} else if ($username!="root" && $folder==""){
                                        	$folder="/home/".$username;
                                	}
				}
				$password=$_REQUEST['password'];
				$random=$_REQUEST['random'];
				$offset_file=$_REQUEST['offset_file'];
				$offset_dir=$_REQUEST['offset_dir'];
				$file_num=$_REQUEST['files_num'];
				error_log("filenum is $file_num");
				$port=$_REQUEST['port'];
				$retrieve_ok="false";
				$only_file_num=0;
				$_SESSION['total_sizes_files']=array();
				if($port!=""){
					$port=$port;
				}
				else {
					if($protocol=="http"){$port="80";}
					if($protocol=="ftp"){$port="21";}
					if($protocol=="sftp"){$port="22";}	
				}
				
				if(isSet($httpVars["sub_action"]) && $httpVars["sub_action"]=="check_conn") {
					
					if($protocol=="sftp"){						
						function handler_conn() {
						}
						set_error_handler("handler_conn");
						$test_conn = ssh2_connect($server, $port);
						function handler_auth() {}
						set_error_handler("handler_auth");
						$test_auth = ssh2_auth_password($test_conn, $username, $password);
						if (!$test_conn) {
							$message['type']="ERROR";
		                    $message['text']="Server not reachable. Please check server name and port.";
		                    $message['category']="conn";
						}
						if (!$test_auth) {
		                    $message['type']="ERROR";
		                    $message['text']="Username or password not correct. Please check them.";
		                    $message['category']="auth";
		                }					
						if (!$test_conn || !$test_auth) {
							AJXP_XMLWriter::header();
							AJXP_XMLWriter::sendMessage($message['type']=="success"?$message['text']:$message['text'], $message['type']=="success"?$message['text']:$message['text']);
							AJXP_XMLWriter::close();
							break;
						}
					}  else {
						
					}
					
				}	
//				error_log("sono andato avanti");			
				$path_user_dir_big_transfer=$path_user_dir."/".$random;
				$path_user_dir_big_transfer_finished=$path_user_dir_big_transfer.'/ok';
				$path_user_dir_big_transfer_error=$path_user_dir_big_transfer.'/error';
				$path_user_dir_big_transfer_grid_error=$path_user_dir_big_transfer.'/grid_error';
				$path_user_dir_big_transfer_lock=$path_user_dir_big_transfer.'/lock';
				if(!is_dir($path_user_dir_big_transfer)){
		//			error_log("creo la dir $path_user_dir_big_transfer");
					mkdir("$path_user_dir_big_transfer", 0777);
					touch("$path_user_dir_big_transfer_finished");
					touch("$path_user_dir_big_transfer_error");
					touch("$path_user_dir_big_transfer_grid_error");
					touch("$path_user_dir_big_transfer_lock");
				}
				if(isset($_REQUEST['dir'])){
					$total_sizes_files_in_dir=array();
					$total_files_size=array();
					$dir=$_REQUEST['dir'];
					$dir_array=explode("/", $dir);
					$dir2=end($dir_array);
					$path=$folder."/".$dir2;
					if($protocol=="sftp"){
				/*		if($_REQUEST['username']=="root" && $_REQUEST['folder']==""){
							$path_test="/root/".$path;
						} else if ($_REQUEST['username']!="root" && $_REQUEST['folder']==""){
							$path_test="/home/".$_REQUEST['username'].$path;
							error_log("big path_test is $path_test");
						} else if ($_REQUEST['folder']!=""){
							$path_test=$path;
						}
				*/
						$path_test=$path;
						$connection = ssh2_connect("$server", "$port");
						ssh2_auth_password($connection, "$username", "$password");
						$sftp = ssh2_sftp($connection);
	//					if(!file_exists('ssh2.sftp://' . $sftp . $path_test)){
						if(!file_exists('ssh2.sftp://' . $sftp . $path)){
						error_log("path is $path");
//							$ok=ssh2_sftp_mkdir($sftp, $path_test);
							$ok=ssh2_sftp_mkdir($sftp, $path);
							if (!$ok) {
							error_log("dir_esco");
								exit(-1);
							}
						}	
					} else {
						error_log("$protocol://$username:$password@$server:$port"."/"."$path");
						if(!is_dir("$protocol://$username:$password@$server:$port"."/"."$path")){						
							if($protocol=="ftp"){
								$conn_id = ftp_connect($server, $port);
								$login_result = ftp_login($conn_id, $username, $password);
								$ok=ftp_mkdir($conn_id, $path);
								if (!$ok) {
									error_log("dir_esco");
									exit(-1);
								}
							} else {
								
							}
						}	
					}
					$filename_dir=array();
					$size_array=array();
					$lfn_path_dir=$_SESSION['home'].$dir;
					exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-ls -il $lfn_path_dir" , $nodeTotalInfo);
					$l=0;
					foreach ($nodeTotalInfo as $singlenodeTotalInfo) {
						$singlenodeTotalInfo = trim($singlenodeTotalInfo);
				        $singlenodeTotalInfo = preg_replace('!\s+!', ' ', $singlenodeTotalInfo);
				        $node_info = explode(" ", $singlenodeTotalInfo);
				        $node_type = substr($node_info[1], 0, 1);
				        if($node_type!="d"){
				       		$filesize=$node_info[5];
						    $filename=$dir."/".$node_info[9];
						    array_push($filename_dir, $filename);
						    $total_sizes_files_in_dir[]=$filesize;
						    $l++;
				        }
				    }
				    $file_num_dir=$l;
				} else {
					foreach($_REQUEST as $key => $value) {
                        if (substr($key, 0, 5)=="file_") {			
							$selectedDir=$value;
							$only_file_num++;
						} else if (substr($key, 0, 5)=="size_") {
							$_SESSION['total_sizes_files'][]=$value;
						}
					}
				}
				if(isset($_REQUEST['dir'])){
					error_log(print_r($total_sizes_files_in_dir,true));
					foreach ($total_sizes_files_in_dir as $value) {
						$_SESSION['total_sizes_files'][]=$value;
					}
				}	
				$total_files_size = $_SESSION['total_sizes_files'];
			//	error_log(print_r($total_files_size,true));
				$db_server=$this->conf['db_server'];
				$db_liferay_username=$this->conf['db_liferay_username'];
				$db_liferay_password=$this->conf['db_liferay_password'];
				$db_liferay_name=$this->conf['db_liferay_name'];
			//error_log("db_server $db_liferay_username $db_liferay_password db_liferay_name");
				$mycloud="false";
				$command5="mysql_connect($this->conf['db_server'], $this->conf['db_liferay_username'], $this->conf['db_liferay_password']);";
				$con = mysql_connect($this->conf['db_server'], $this->conf['db_liferay_username'], $this->conf['db_liferay_password']);	
			//error_log("db_server $db_liferay_username $db_liferay_password db_liferay_name $con");
				if (!$con) {
				  die('Could not connect: ' . mysql_error());
				}								
				mysql_select_db($this->conf['db_liferay_name'], $con);			
				$id=$_SESSION['user_id'];
				$data=mysql_query("SELECT User_.emailAddress, User_.firstName, User_.lastName FROM User_ WHERE User_.userId=$id");
				if($data === FALSE) {
					die(mysql_error()); // TODO: better error handling
				}			
				$info = mysql_fetch_array($data);
				$lastName = $info['lastName'];
				$firstName = $info['firstName'];
				$mail = $info['emailAddress'];	
				$numElements=0;
				$selectedFile_list="";
				$dir=$dir."/";								
				$x=0;
				$z=1;
				$num_max_files=50;
				$num_max_files_in_dir=50;
				if(isset($_REQUEST['dir'])){
					$i=$offset_dir;
					foreach($filename_dir as $selectedFile){
											
						if($file_num_dir<=$num_max_files_in_dir){
							$num_max_files_in_dir=$file_num_dir;
						} else if($file_num_dir>$num_max_files_in_dir && $file_num_dir<=2*$num_max_files_in_dir){
							$num_max_files_in_dir=round($file_num_dir/2, 0, PHP_ROUND_HALF_UP);
						} else {
							$num_max_files_in_dir=round($file_num_dir/3, 0, PHP_ROUND_HALF_UP);
						} 										
						if((($x+1)<($num_max_files_in_dir*$z)) && $x!=($file_num_dir-1)){
							if($selectedFile_list==""){
								$selectedFile_list=$selectedFile;
								$total_files_size_list=$total_files_size[$x];
							} else {
								$selectedFile_list=$selectedFile_list."***".$selectedFile;
								$total_files_size_list=$total_files_size_list."***".$total_files_size[$x];
							}
							$x++;
							$i++;
						}else{
							if($selectedFile_list==""){
								$selectedFile_list=$selectedFile;
								$total_files_size_list=$total_files_size[$x];
							} else {
								$selectedFile_list=$selectedFile_list."***".$selectedFile;
								$total_files_size_list=$total_files_size_list."***".$total_files_size[$x];
							}
							$k=$num_max_files_in_dir*($z-1)+$offset_dir;
							exec("php /opt/dm/ajp/plugins/access.lfc/retrieve.php '$selectedFile_list' '$X509_USER_PROXY' '$lfc_server' '$home' '$protocol' '$server' '$username' '$password' '$random' '$LCG_GFAL_INFOSYS' '$path_user_dir_big_transfer' '$file_num' '$mail' '$firstName' '$lastName' '$user_id' '$vo_active' '$folder' '$port' '$mycloud' '$k' '$dir' '$total_files_size_list'> /dev/null &");
							$selectedFile_list="";
							$i++;
							$x++;
							$z++;
						}
					}		
				} else {
					$dir="";
					error_log("only_file_num is $only_file_num");
					foreach($_REQUEST as $key => $value) {
						if (substr($key, 0, 5)=="file_") {
							$selectedFile=$value;
							$i=substr($key, 5)+$offset_file;
							if($only_file_num<=$num_max_files){
								$num_max_files=$only_file_num;
							} else if($only_file_num>$num_max_files && $only_file_num<=2*$num_max_files){
								$num_max_files=round($only_file_num/2, 0, PHP_ROUND_HALF_UP);
							} else {
								$num_max_files=round($only_file_num/3, 0, PHP_ROUND_HALF_UP);
							} 																					
							if((($i+1)<($num_max_files+$offset_file)) && $i!=($only_file_num-1)){
							//	error_log("sono qua 1 $i e $z");
								if($selectedFile_list==""){
									$selectedFile_list=$selectedFile;
									$total_files_size_list=$total_files_size[$i];

								} else {
									$selectedFile_list=$selectedFile_list."***".$selectedFile;
									$total_files_size_list=$total_files_size_list."***".$total_files_size[$i];
								}
							}else{
						//		error_log("sono qua 2 $i e $z");
								if($selectedFile_list==""){
									$selectedFile_list=$selectedFile;
									$total_files_size_list=$total_files_size[$i];
								} else {
									$selectedFile_list=$selectedFile_list."***".$selectedFile;
									$total_files_size_list=$total_files_size_list."***".$total_files_size[$i];
								}
								exec("php /opt/dm/ajp/plugins/access.lfc/retrieve.php '$selectedFile_list' '$X509_USER_PROXY' '$lfc_server' '$home' '$protocol' '$server' '$username' '$password' '$random' '$LCG_GFAL_INFOSYS' '$path_user_dir_big_transfer' '$file_num' '$mail' '$firstName' '$lastName' '$user_id' '$vo_active' '$folder' '$port' '$mycloud' '$offset_file' '$dir' '$total_files_size_list'> /dev/null &");
								$selectedFile_list="";
							}
						}
				 	}
				}				
			} 
			
			
			if(($_REQUEST["PC"])=='true') {
				$random = $_REQUEST["random"];
				$path_user_dir_transfer=$path_user_dir."/".$random;
				$path_user_dir_transfer_ok=$path_user_dir_transfer.'/ok';
				$path_user_dir_transfer_error=$path_user_dir_transfer.'/error';
				if(!is_dir($path_user_dir_transfer)){
		//			error_log("creo la dir $path_user_dir_transfer");
					mkdir("$path_user_dir_transfer", 0777);
					touch("$path_user_dir_transfer_ok");
					touch("$path_user_dir_transfer_error");
				}
				$keep = $_REQUEST["keep"];
				if (!isset($_REQUEST["local"]) || $_REQUEST["local"]!="true") { 
					$_SESSION['total_files']=array();
					$_SESSION['total_items']=array();
					$_SESSION['total_sizes_files']=array();
					$total_sizes_files_in_dir=array();
					$only_files=$selection->getFiles();
					if(sizeof($only_files)!=0){
						$_SESSION['total_files']=$only_files;
						$_SESSION['total_items']=$only_files;
					}
					
					foreach($_REQUEST as $key => $value) {
						if (substr($key, 0, 4)=="dir_") {
							$selectedDir=$value;
							$dir=$_SESSION['home'].$selectedDir;
							error_log("dir is $dir");
		//					$command="sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-ls -il $dir";
		//					error_log("command is $command");
							exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-ls -il $dir" , $nodeTotalInfo);
		//					error_log(print_r($nodeTotalInfo,true));
							foreach ($nodeTotalInfo as $singlenodeTotalInfo) {
								$singlenodeTotalInfo = trim($singlenodeTotalInfo);
						        $singlenodeTotalInfo = preg_replace('!\s+!', ' ', $singlenodeTotalInfo);
						        $node_info = explode(" ", $singlenodeTotalInfo);
						        $node_type = substr($node_info[1], 0, 1);
						        if($node_type!="d"){
								    $filesize=$node_info[5];
								    $filename=$node_info[9];
							        $_SESSION['total_files'][]=$selectedDir."/".$filename;
							        $total_sizes_files_in_dir[]=$filesize;
						        }
						    	}
						    	$nodeTotalInfo=array();
						    	$_SESSION['total_items'][]=$selectedDir;
						} else if (substr($key, 0, 5)=="file_") {
							$selectedDir=$value;
						} else if (substr($key, 0, 5)=="size_") {
                            $_SESSION['total_sizes_files'][]=$value;
                                                }	
					}
					foreach ($total_sizes_files_in_dir as $value) {
                            $_SESSION['total_sizes_files'][]=$value;
                    }
                    $srtimeout=60;
                    foreach ($_SESSION['total_files'] as $value) {
                            if($value>524288000){
	                            $srtimeout=90;
                            } 
                    }
					$total_files=$_SESSION['total_files'];
					$total_files_num = $_REQUEST["files_num"];
					$total_files_size = $_SESSION['total_sizes_files'];
					error_log(print_r($total_files_size,true));					
					error_log("total_files_num is $total_files_num");
					error_log("selectedDir is $selectedDir");
					$dir_pieces=explode('/',$selectedDir);
					$num=sizeof($dir_pieces)-1;
					unset($dir_pieces[$num]);
					$dir_compl=implode('/', $dir_pieces);
//					error_log("dir_compl is $dir_compl");
					$selectedFile_list="";
					$total_files_size_list="";
					$x=0;
					$z=1;
					if ($total_files_num<=2){
						$num_max_files=$total_files_num;
					} else {
						$num_max_files=round($total_files_num/3, 0, PHP_ROUND_HALF_UP);
					}
					error_log("num_max_files is $num_max_files");
					foreach($total_files as $selectedFile){
						if((($x+1)<($num_max_files*$z)) && $x!=($total_files_num-1)){
							if($selectedFile_list==""){
								$selectedFile_list=$selectedFile;
								$total_files_size_list=$total_files_size[$x];
							} else {
								$selectedFile_list=$selectedFile_list."***".$selectedFile;
								$total_files_size_list=$total_files_size_list."***".$total_files_size[$x];
							}
							$x++;
						}else{
							if($selectedFile_list==""){
								$selectedFile_list=$selectedFile;
								$total_files_size_list=$total_files_size[$x];
							} else {
								$selectedFile_list=$selectedFile_list."***".$selectedFile;
								$total_files_size_list=$total_files_size_list."***".$total_files_size[$x];
							}
							$k=$num_max_files*($z-1);
							exec("php /opt/dm/ajp/plugins/access.lfc/download.php '$selectedFile_list' '$X509_USER_PROXY' '$lfc_server' '$home' '$random' '$LCG_GFAL_INFOSYS' '$path_user_dir_transfer' '$total_files_num' '$user_id' '$vo_active' '$dir_compl' '$k' '$total_files_size_list' '$srtimeout'> /dev/null &");
							$selectedFile_list="";
							$x++;
							$z++;
						}
					}		
					$sec=5;
					sleep($sec);
					$occ = count(glob($path_user_dir_transfer.'/*.zzz'));
					error_log("occ is $occ");
					while($occ!=$total_files_num){
						$occ = count(glob($path_user_dir_transfer.'/*.zzz'));
						sleep($sec);
						$sec=5;
						error_log("occ is $occ");
					}			
					error_log("son andato avanti");
					$message['text']="";
					
					$lines_err = file($path_user_dir_transfer_error, FILE_IGNORE_NEW_LINES);
					sort($lines_err);
					foreach($lines_err as $line_err){
						$message['text'].="<span style='color:red;'>Failed download of file ".$line_err.". Maybe the Storage Element is not working.</span>\n";
						$message['type']="fail";
					}
					$lines_ok = file($path_user_dir_transfer_ok, FILE_IGNORE_NEW_LINES);
					sort($lines_ok);
					foreach($lines_ok as $line_ok){
						$message['text'].="File ".$line_ok." successfully downloaded.\n";
						$message['type']="success";
					}
				//$message = $this->download($_SESSION['total_files'], $logMessages, $_REQUEST, $dir_compl);
					AJXP_XMLWriter::header();
					AJXP_XMLWriter::sendMessage($message['type']=="success"?$message['text']:null, $message['type']=="success"?null:$message['text']);
		            AJXP_XMLWriter::close();
		            error_log("path_user_dir_transfer is $path_user_dir_transfer");
					$this->deleteDir($path_user_dir_transfer);
				} else {
					foreach ($_SESSION['total_items'] as $selectedFile) {
	//					$lfn_file="lfn:/".$_SESSION['home'].$selectedFile;
		                $ff=$selectedFile;
		                error_log("ff is $ff");
		                $file_pieces=explode("/",$selectedFile);
		                $file_name = end($file_pieces);
		                $file_name = $this->clean_file_name($file_name);
		                $fs_file=$this->user_dir."/".$file_name;
						$file_names[] = $file_name;
						$fs_files[] = $fs_file;
					}
					AJXP_Logger::logAction("Download", array("files"=>$selection));
		            @set_error_handler(array("HTMLWriter", "javascriptErrorHandler"), E_ALL & ~ E_NOTICE);
		            @register_shutdown_function("restore_error_handler");
		            $zip = false;
		            if($selection->isUnique()){
						if(is_dir($fs_files[0])) {
		                   $zip = true;
		                   $base = basename($fs_files[0]);
		                   $dir .= "/".dirname($fs_files[0]);
		                }
		            }else{
		                $zip = true;
		            }
					if($zip){
		//          Make a temp zip and send it as download
			            $loggedUser = AuthService::getLoggedUser();
						$file=$path_user_dir.'/tmpDownload.tar';
						error_log("il tar si trova in $file");
						$file_selected=$selection->getFiles();
						$files_to_remove=false;
						$err_message = "";
						$i=0;
			// 			Remove from array the files that were not successfully downloaded from Grid
						foreach($fs_files as $file_to_download) {
							if (!file_exists($file_to_download)) {
								unset($file_names[$i]);
								$err_message .= "Cannot download file ".$file_names[$i].".\n";
								$files_to_remove=true;
							}
							$i++;
						}
						include ('Tar.php');        // import class
						$obj = new Archive_Tar($file); // name of archive
						$files = $fs_files;   // files to store in archive
						error_log(print_r($files,true));
						$zipFile=$obj->createModify($files, '', $path_user_dir.'/');                                
			            if(!$zipFile) throw new AJXP_Exception("Error while compressing");
			            register_shutdown_function("unlink", $file);
			            $localName = "Files.tar";
			            error_log("1111111");
						fsAccessDriver::readFile($file, "force-download", $localName, false, false, true);
						if ($keep!="true" && $request["big"]!='true') {
							foreach($file_names as $file_to_delete) {
								if(is_file($path_user_dir.'/'.$file_to_delete)){
									unlink($path_user_dir.'/'.$file_to_delete);
									error_log("elimino il file $path_user_dir/$file_to_delete");
								}
								if(is_dir($path_user_dir.'/'.$file_to_delete)){
									$this->deleteDir($path_user_dir.'/'.$file_to_delete);
							error_log("elimino la dir $path_user_dir/$file_to_delete");
								}
							}
						}
		            } else {
						$localName = "";
		                AJXP_Controller::applyHook("dl.localname", array("ajxp.fs://206182a90c0e9e28d801417ea03715df/".$file_names[0], &$localName, $this->wrapperClassName));
		                error_log("3333333");
		                fsAccessDriver::readFile("ajxp.fs://206182a90c0e9e28d801417ea03715df/".$file_names[0], "force-download", $localName);
		                if ($keep!="true") {
							unlink($path_user_dir.'/'.$file_names[0]);	
						}
		            }
				}						
			}
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
				if ($httpVars["path"]=="/grid/".$_SESSION['vo']) {
					$home=$httpVars["path"]."/";
				} else {
					$home=$_COOKIE['home'].$httpVars["path"]."/";
				}	
				setcookie("home", $home, 0, '', '', true, false);
				setcookie("user_home", $home, 0, '', '', true, false);
				$path=$httpVars["path"];
				$_SESSION['home']=$home;
				$_SESSION['home_changed']="true";
				return ;	
			} else {
//				$user_vo_home_dir = '/opt/dm/ajp/data/plugins/auth.serial/'.$this->user_id.'/';
				$file = fopen($this->user_vo_home_dir."homevo.".$_SESSION['vo'], 'rt');
				while(!feof($file)) {
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
				if($selection->isEmpty()) {
					throw new AJXP_Exception("", 113);
				}
				$logMessages = array();		
				$con = mysql_connect($this->conf['db_server'], $this->conf['db_portal_username'], $this->conf['db_portal_password']);
				if (!$con) {
				    die('Could not connect: ' . mysql_error());
				}
				mysql_select_db($this->conf['db_portal_name'], $con);
				
				$data=mysql_query("select userInfo.userId, firstName, lastName, mail, subject from (((userInfo join certificate on userInfo.userId = certificate.userId) join userToVO on userInfo.userId = userToVO.userId) join VO on userToVO.idVO = VO.idVO) where VO.VO = '".$_SESSION['vo']."'");
				$result = "";
				$node = AJXP_Utils::decodeSecureMagic($httpVars["file"]);
				while($info = mysql_fetch_array( $data )) {	
					$subject = $info['subject'];
					$lastName[$subject] = $info['lastName'];
					$firstName[$subject] = $info['firstName'];
				}
			
				$_SESSION['lastName']=$lastName;
				$lfn_file=$_SESSION['home'].$httpVars['file'];
				
				exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys   /usr/bin/lfc-getacl $lfn_file", $getacl_output);
				
				$vo_share="false";
				foreach ($getacl_output as $output_line) {
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
				
			$X509_USER_PROXY=$this->x509_user_proxy;
			$lfc_server=$this->lfc_server;
			$LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys;
			$vo_active = $_SESSION['vo'];
			if(isSet($httpVars["sub_action"]) && ($httpVars["sub_action"]=="doReplica" || $httpVars["sub_action"]=="delReplica")) {
            	$act = "add";
                $messId = "73";
            	$message=$this->replicaAction($httpVars["hostName"], $httpVars["fileName"], $httpVars["sub_action"]);

                AJXP_XMLWriter::header();
//              AJXP_XMLWriter::sendMessage($mess["ajxp_conf.".$messId].$httpVars["user_id"], null);
				AJXP_XMLWriter::sendMessage($message['type']=="success"?$message['text']:null, $message['type']=="success"?null:$message['text']);
    	        AJXP_XMLWriter::close();
    	        return ;
			
			} else if(isSet($httpVars["sub_action"]) && ($httpVars["sub_action"]=="doReplicaRemote")) {
				
				$SE_dest = $httpVars["hostName"];
				$selectedFile = $httpVars["fileName"];
				$con = mysql_connect($this->conf['db_server'], $this->conf['db_liferay_username'], $this->conf['db_liferay_password']);					
				if (!$con) {
					 die('Could not connect: ' . mysql_error());
				}				
				mysql_select_db($this->conf['db_liferay_name'], $con);			
				$id=$_SESSION['user_id'];
				$data=mysql_query("SELECT User_.emailAddress, User_.firstName, User_.lastName FROM User_ WHERE User_.userId=$id");
				if($data === FALSE) {
					die(mysql_error()); // TODO: better error handling
				}			
				$info = mysql_fetch_array($data);
				$lastName = $info['lastName'];
				$firstName = $info['firstName'];
				$mail = $info['emailAddress'];	
				$lfn_file="lfn:".$_SESSION['home'].$selectedFile;
				
				exec("php /opt/dm/ajp/plugins/access.lfc/replicate.php '$lfn_file' '$X509_USER_PROXY' '$lfc_server' '$SE_dest' '$LCG_GFAL_INFOSYS' '$mail' '$firstName' '$lastName' '$vo_active' '$selectedFile'> /dev/null &");
				
			} else {
				if($selection->isEmpty())
                {
                        throw new AJXP_Exception("", 113);
                }
                $logMessages = array();
                $lfn_file=$_SESSION['home'].$httpVars['file'];
                $command4 = "sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lcg-lr lfn:$lfn_file";
                error_log("command4 is $command4");
                exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lcg-lr lfn:$lfn_file", $srm_list);
                
                
                error_log("000000000000000");	
				error_log(print_r($srm_list,true));


                foreach ($srm_list as $srm) {
                    $srm_substr = str_replace("srm://", "", $srm);
                    $srm_pieces = explode("/", $srm_substr);	
                    $se = $srm_pieces[0];
                    $se_used_list[] = $se;
                }
                $command4 = "sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lcg-infosites --vo $vo_active -v 4 se";
                error_log("command4 is $command4");
                exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lcg-infosites --vo $vo_active -v 4 se", $se_site_row_list);
                
                error_log("11111111111111111");	
				error_log(print_r($se_site_row_list,true));

				$y=0;
				for ($i=2; $i<sizeof($se_site_row_list); $i++) {
					$se_site_list[$y] = preg_split("/[\s]+/", $se_site_row_list[$i]);
					$total_list[$y] = array($se_site_list[$y][1], $se_site_list[$y][0]);
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

			if($selection->isEmpty()) {
				throw new AJXP_Exception("", 113);
			}
			$logMessages = array();
			$lfn_file="lfn:/".$_SESSION['home'].$httpVars['file'];
			$lfn2_file=$_SESSION['home'].$httpVars['file'];  	
			
        	exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lcg-lr $lfn_file", $replica_values);
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lcg-lg $lfn_file", $guid_values);
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lfc-getacl $lfn2_file", $permission_values);				
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
			
			if($selection->isEmpty()) {

				throw new AJXP_Exception("", 113);
			}
			$success = $error = array();
			$dest = $_SESSION['home'].AJXP_Utils::decodeSecureMagic($httpVars["dest"]);

			if($selection->inZip()) {
				// Set action to copy anycase (cannot move from the zip).
				$action = "copy";
				$this->extractArchive($dest, $selection, $error, $success);
			} else {

				$this->copyOrMove($dest, $selection->getFiles(), $error, $success, ($action=="move"?true:false));
			}
			
			if(count($error)) {					
				throw new AJXP_Exception(SystemTextEncoding::toUTF8(join("\n", $error)));
			} else {
				$logMessage = join("\n", $success);
				AJXP_Logger::logAction(($action=="move"?"Move":"Copy"), array("files"=>$selection, "destination"=>$dest));
			}
			$reloadContextNode = true;
            if(!(RecycleBinManager::getRelativeRecycle() ==$dest && $this->driverConf["HIDE_RECYCLE"] == true)) {
				$base = $_SESSION['home'];
				$destOK= str_replace($base , "/", $dest);
	            $reloadDataNode = $destOK;
            }
			
		break;



		//------------------------------------
		//	DELETE
		//------------------------------------
		case "delete";
		
			if($selection->isEmpty()) {
				throw new AJXP_Exception("", 113);
			}
			$logMessages = array();
			$errorMessage = $this->delete($selection->getFiles(), $logMessages);
			if(count($logMessages)) {
				$logMessage = join("\n", $logMessages);
			}

			$reloadContextNode = true;
			$xmlBuffer='';
			$xmlBuffer .= AJXP_XMLWriter::sendMessage($errorMessage['type']=="success"?$errorMessage['text']:null, 		$errorMessage['type']=="success"?null:$errorMessage['text'], false);
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
			$change_home =	AJXP_Utils::decodeSecureMagic($httpVars["change_home"]);
			error_log($change_home);				
			$pieces = explode("/", $file);				
			$file_old = end($pieces);				
			$file_new = str_replace($file_old , $filename_new, $file);
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lfc-rename $file $file_new");																
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys  /usr/bin/lfc-ls $file_new", $new_lfn);

			$message['text']="Failed to rename from ".$file." to ".$file_new;
			$message['type']="fail";
            foreach ($new_lfn as $lfn) {
            	if (strstr($lfn, $file_new)!="") {
                	$message['text']="File successfully renamed from ".$file." to ".$file_new;
					$message['type']="success";
                }
            }
			if ($message['type']=="success") {	
				foreach (file($this->shared_files) as $name) {
    				if(str_replace("\r\n", "", $name) == $file) {
   						$this->del_line_in_file($this->shared_files, $file);
						$handle = fopen($this->shared_files, "a+");
						fwrite($handle, $file_new."\r\n");
						fclose($handle);
					}
							
				}
			}			 	
			if($change_home=="true"){
					error_log($change_home);
					$message = $this->homeAction("/".$httpVars["filename_new"], $httpVars["change_home"]);
					setcookie("home", $file_new, 0, '', '', true, false);
					setcookie("user_home", $file_new, 0, '', '', true, false);
					$_SESSION['home']=$file_new;
					$_SESSION['home_changed']="true";
				}
	//		$reloadContextNode = true;
		
		break;


		
		//------------------------------------
		//	CREATE DIR
		//------------------------------------
		
		case "mkdir";
		    
			$messtmp="";
			$dirname=AJXP_Utils::decodeSecureMagic($httpVars["dirname"], AJXP_SANITIZE_HTML_STRICT);
			$dirname = substr($dirname, 0, ConfService::getCoreConf("NODENAME_MAX_LENGTH"));			
			$newdir = $_SESSION['home'].$dir."/".$dirname;

error_log("newdir is $newdir");

			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lfc-mkdir -m 777 $newdir");

			if(isSet($error)) {
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
//					$user_vo_home_dir = '/opt/dm/ajp/data/plugins/auth.serial/'.$this->user_id.'/';
			$handle2=opendir($this->user_vo_home_dir);
/*
			while ($file = readdir($handle2)) {							
				if (substr($file, 0, 5)=="group"){
					$file = fopen($this->user_vo_home_dir."group", 'rt');
					while(!feof($file))
				    {
				      $home_path = fgets($file);
				    }      
				    fclose($file);
//						$ourFileName = '/opt/dm/ajp/data/plugins/auth.serial/'.$this->user_id.'/homevo.'.$_SESSION['vo'];
				    $ourFileName = $this->user_vo_home_dir.'homevo.'.$_SESSION['vo'];	
				    $ourFileHandle = fopen($ourFileName, 'w') or die("can't open file");
				    $content = $home_path;
				    fwrite($ourFileHandle, $content);
				    fclose($ourFileHandle);
				    $_SESSION['home'] = $home_path;

				    exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lfc-ls $home_path" , $nodes);
				    if (sizeof($nodes)==0){		
					    $newdir1 = $_SESSION['home']."/";
					    $newdir2 = $_SESSION['home']."/input/";		
					    $newdir3 = $_SESSION['home']."/output/";	
						exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lfc-mkdir -m 700 $newdir1", $output1);
						exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lfc-mkdir -m 700 $newdir2", $output2);
						exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lfc-mkdir -m 700 $newdir3", $output3);
				  	}				  	
				    unlink($this->user_vo_home_dir.'group');
				    $file_exist=true;			
		 		}
			}
*/			
		} else if ($_COOKIE['ajxp_wall']=="true" && $_SESSION['home']!="/grid/".$_SESSION['vo'] && $_COOKIE['ajxp_jsreload']=="true") {
			setcookie("ajxp_jsreload", "", time() - 3600);
		}	
		if(!isSet($dir) || $dir == "/") $dir = "";
		$lsOptions = $this->parseLsOptions((isSet($httpVars["options"])?$httpVars["options"]:"a"));
		$startTime = microtime();				
		$dir = AJXP_Utils::securePath(SystemTextEncoding::magicDequote($dir));
		if ($dir==""||$dir=="/") {
			$path_right = $_SESSION['home'];
			$path = "";
		} else {
			$path = ($dir!= ""?($dir[0]=="/"?"":"/").$dir:"");
			if ($_SESSION['home_changed']=="true"){
				$path_right = $_SESSION['home'];
			} else {
			$path_right = $_SESSION['home'].$dir_compl;
			}
		}			
        $nonPatchedPath = $path;
        if($this->wrapperClassName == "lfcAccessWrapper") {
            $nonPatchedPath = lfcAccessWrapper::unPatchPathForBaseDir($path);
        }
		$threshold = $this->repository->getOption("PAGINATION_THRESHOLD");
		if(!isSet($threshold) || intval($threshold) == 0) $threshold = 200;
		$limitPerPage = $this->repository->getOption("PAGINATION_NUMBER");
		if(!isset($limitPerPage) || intval($limitPerPage) == 0) $limitPerPage = 200;
		$limitPerPageOLD = $limitPerPage;
		$pippone= $_SESSION['home_changed'];
		if ($page>1 && (!isset($_SESSION['home_changed']) || $_SESSION['home_changed']!="true")) {
			$countFiles = $_SESSION['count'];
			$limitPerPage = $_SESSION['new_limitPerPage'][$page-1];
		} else {
			$countFiles = $this->countFiles($path_right, $limitPerPage, $threshold, !$lsOptions["f"]);
			$limitPerPage = $_SESSION['new_limitPerPage'][0];
			
			if ($_SESSION['home_changed']=="true") {
				$page=1;
				$_SESSION['home_changed']="false";
			}
		}	
		if($countFiles > $threshold){				
			$offset = 0;
			$crtPage = 1;
			if(isSet($page)){
				for ($i=1; $i<$page; $i++) {
					$crtPage = $page;
					$newlimit=$_SESSION['new_limitPerPage'][$i-1];
				}
			}
			$totalPages = floor($countFiles / $limitPerPageOLD) + 1;
		} else {
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
		if (isset($page)) {
			$nodes = $_SESSION['nodes'][$page-1];
		} else {
			$nodes = $_SESSION['nodes'][0];
		}
//          error_log(print_r($nodes, true));
		if (isset($nodes)) {
			foreach ($nodes as $nodeTotalInfo){
				$nodeTotalInfo = trim($nodeTotalInfo);
                $nodeTotalInfo = preg_replace('!\s+!', ' ', $nodeTotalInfo);
                $node_info = explode(" ", $nodeTotalInfo);
                array_push($node_names, $node_info[9]);
				$nodeName = $node_info[9];	
	            $node_total_info[$node_info[9]] = $nodeTotalInfo;
	
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
				foreach (file($this->shared_files) as $name) {
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
	               if($node->isLeaf()) {
	                if(AJXP_Utils::isBrowsableArchive($nodeName)) {
	                    if($lsOptions["f"] && $lsOptions["z"]) {
	                        $nodeType = "f";
	                    } else {
	                        $nodeType = "z";
	                    }
	                }
	                else $nodeType = "f";
	            }
							
				$fullList[$nodeType][$nodeName] = $node;	
				$fullListOne[] = $node;
				$cursor ++;
			}	
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
		if(isset($logMessage) || isset($errorMessage)) {
			$xmlBuffer .= AJXP_XMLWriter::sendMessage((isSet($logMessage)?$logMessage:null), (isSet($errorMessage)?$errorMessage:null), false);			
		}				
		if($reloadContextNode) {
			if(!isSet($pendingSelection)) $pendingSelection = "";
			$xmlBuffer .= AJXP_XMLWriter::reloadDataNode("", $pendingSelection, false);
		}
		if(isSet($reloadDataNode)) {
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
			if(strchr($optionString, $key)!==false) {
				$lsOptions[$key] = true;
			} else {
				$lsOptions[$key] = false;
			}
		}
		if($lsOptions["a"]) {
			$lsOptions["d"] = $lsOptions["z"] = $lsOptions["f"] = true;
		}
		return $lsOptions;
	}


	
	function countFiles($path_right, $limitPerPage, $foldersOnly = false, $nonEmptyCheckOnly = false){
		$_SESSION['nodes']=array();
		$folders=array();
		$_SESSION['count'] = 0;
		$command = "sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server lfc-ls -cilTu $path_right";
		error_log("command is $command");
		$_SESSION['path']=$path_right;
		exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lfc-ls -il $path_right" , $_SESSION['nodes']);
		$_SESSION['count']=count($_SESSION['nodes']);
		if($_SESSION['count'] > $threshold){
			$nodes = $_SESSION['nodes'];
			$_SESSION['node_names']=array();
			$node_names=array();
			foreach ($nodes as $nodeTotalInfo){
				$nodeTotalInfo2 = trim($nodeTotalInfo);
				$nodeTotalInfo2 = preg_replace('!\s+!', ' ', $nodeTotalInfo2);
				$node_info = explode(" ", $nodeTotalInfo2);
				$node_type = substr($node_info[1], 0, 1);
				if($node_type=="d"){ 
					array_push($folders, $nodeTotalInfo);											
				}																	
			}			
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
			$_SESSION['nodes']=$super_nodes;
		}	
		return $_SESSION['count'];
	}



    /**
     * @param AJXP_Node $ajxpNode
     * @return void
     */

    function loadNodeInfo(&$ajxpNode){

		$metaData = $ajxpNode->metadata;
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
        if(!isSet($metaData["is_file"])) {
	    	if (isset($metas3)) {    		    	
		    	$isLeaf = $node_type!="d";		
		    } else {
               $isLeaf = is_file($ajxpNode->getUrl()) || AJXP_Utils::isBrowsableArchive($nodeName);            
            }	
            $metaData["is_file"] = ($isLeaf?"1":"0");
                        
        } else {
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
//        if($metaData["icon"] == "folder.png"){
//        }
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
        $metaData["bytesize"] = $Data["bytesize"];
        $metaData["filesize"] = AJXP_Utils::roundSize($Data["bytesize"]);
        $ajxpNode->mergeMetadata($metaData);

    }
        
    

	/**
	 * Test if userSelection is containing a hidden file, which should not be the case!
	 * @param UserSelection $files
	 */
	function filterUserSelectionToHidden($files) {
		foreach ($files as $file) {
			$file = basename($file);
			if(AJXP_Utils::isHidden($file) && !$this->driverConf["SHOW_HIDDEN_FILES"]) {
				throw new Exception("Forbidden");
			}
			if($this->filterFile($file) || $this->filterFolder($file)) {
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
			if(RecycleBinManager::recycleEnabled() && $nodePath."/".$nodeName == RecycleBinManager::getRecyclePath()){
				return false;
			}					
			return !$this->filterFolder($nodeName);
		} else {
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
	


	function date_modif($file) {
		$tmp = @filemtime($file) or 0;
		return $tmp;// date("d,m L Y H:i:s",$tmp);
	}
	
	
	
	function changeMode($filePath) {
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



	function copyOrMove($destDir, $selectedFiles, &$error, &$success, $move = false) {
		AJXP_Logger::debug("CopyMove", array("dest"=>$destDir));
		$mess = ConfService::getMessages();
		foreach ($selectedFiles as $selectedFile) {
			$this->copyOrMoveFile($destDir, $_SESSION['home'].$selectedFile, $error, $success, $move);
		}
	}
	
	
	
	function renameAction($actionName, $httpVars) {
		$filePath = SystemTextEncoding::fromUTF8($httpVars["file"]);
		$newFilename = SystemTextEncoding::fromUTF8($httpVars["filename_new"]);
		return $this->rename($filePath, $newFilename);
	}
	
	
	
	function rename($filePath, $filename_new) {	
		$nom_fic=basename($filePath);
		$mess = ConfService::getMessages();
		$filename_new=AJXP_Utils::sanitize(SystemTextEncoding::magicDequote($filename_new), AJXP_SANITIZE_HTML_STRICT);
		$filename_new = substr($filename_new, 0, ConfService::getCoreConf("NODENAME_MAX_LENGTH"));
		$old=$this->urlBase."/$filePath";
		if(!$this->isWriteable($old)) {
			throw new AJXP_Exception($mess[34]." ".$nom_fic." ".$mess[99]);
		}
		$new=dirname($old)."/".$filename_new;
		if($filename_new=="") {
			throw new AJXP_Exception("$mess[37]");
		}
		if(file_exists($new)) {
			throw new AJXP_Exception("$filename_new $mess[43]"); 
		}
		if(!file_exists($old)) {
			throw new AJXP_Exception($mess[100]." $nom_fic");
		}
        $oldNode = new AJXP_Node($old);
        AJXP_Controller::applyHook("node.before_change", array(&$oldNode));
		rename($old,$new);
        AJXP_Controller::applyHook("node.change", array($oldNode, new AJXP_Node($new), false));
	}
	

	
	function mkDir($crtDir, $newDirName) {
		$mess = ConfService::getMessages();
		if($newDirName=="") {
			return "$mess[37]";
		}
		if(file_exists($this->urlBase."$crtDir/$newDirName")) {
			return "$mess[40]"; 
		}
		if(!$this->isWriteable($this->urlBase."$crtDir")) {
			return $mess[38]." $crtDir ".$mess[99];
		}

        $dirMode = 0775;
		$chmodValue = $this->repository->getOption("CHMOD_VALUE");
		if(isSet($chmodValue) && $chmodValue != "") {
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
	


	function createEmptyFile($crtDir, $newFileName, $content = "") {
		$mess = ConfService::getMessages();
		if($newFileName=="") {
			return "$mess[37]";
		}
		if(file_exists($this->urlBase."$crtDir/$newFileName")) {
			return "$mess[71]";
		}
		if(!$this->isWriteable($this->urlBase."$crtDir")) {
			return "$mess[38] $crtDir $mess[99]";
		}
		$fp=fopen($this->urlBase."$crtDir/$newFileName","w");
		if($fp) {
			if($content != ""){
				fputs($fp, $content);
			}
			if(preg_match("/\.html$/",$newFileName)||preg_match("/\.htm$/",$newFileName)) {
				fputs($fp,"<html>\n<head>\n<title>New Document - Created By AjaXplorer</title>\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">\n</head>\n<body bgcolor=\"#FFFFFF\" text=\"#000000\">\n\n</body>\n</html>\n");
			}
			$this->changeMode($this->urlBase."$crtDir/$newFileName");
			fclose($fp);
			return null;
		} else {
			return "$mess[102] $crtDir/$newFileName (".$fp.")";
		}		
	}
	
	
/*
	function download($selectedFiles, &$logMessages, $request, $dir_compl) {

		if(!file_exists($this->user_dir) || !is_dir($this->user_dir)){
			mkdir($this->user_dir);
		}
		$X509_USER_PROXY=$this->x509_user_proxy;
		$lfc_server=$this->lfc_server;
		$home=$_SESSION['home'];
		$LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys;
		$user_id=$_SESSION['user_id'];
		$vo_active = $_SESSION['vo'];
		$message['text']="";
        $message['type']="success";
		$not_failure=true;
		foreach ($selectedFiles as $selectedFile) {	
			$lfn_file="lfn:/".$_SESSION['home'].$selectedFile;  	
			$ff=$selectedFile;
			error_log("ff is $ff");			
			$file_pieces=explode("/",$selectedFile);
			$file_name = end($file_pieces);
			
			$cip1=$dir_compl."/".$file_name;
			error_log("cip1 is $cip1");
			error_log("selectedFile is $selectedFile");

			
			if($selectedFile!=$dir_compl."/".$file_name){
				$new_dir_name=$this->user_dir."/".$file_pieces[sizeof($file_pieces)-2];
				error_log("new_dir is $new_dir_name");
				if(!is_dir($new_dir_name)){
					mkdir($new_dir_name);
					chmod($new_dir_name, 0775);
				}
				$dir_dest=$new_dir_name;
			}else{
				$dir_dest=$this->user_dir;
			}			
			
			$file_name = $this->clean_file_name($file_name);
//			$fs_file=$this->conf['user_dir_base_path'].$_SESSION['user_id']."/".$file_name;
			$fs_file = $dir_dest."/".$file_name;
			error_log("lfn_file is $lfn_file");
			error_log("fs_file is $fs_file");	      			
			$vo_active = $_SESSION['vo'];
			$replica_values2=array();
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lcg-lr $lfn_file", $replica_values2);
			shuffle($replica_values2);
			$downloaded_file_name=$fs_file;
			$error=='true';			
			foreach ($replica_values2 as $element2) {
				if($error='true'){
					$random2=rand(5, 15);
					$fs_file2 = $dir_dest."/".$random2.$file_name;
					exec("php /opt/dm/ajp/plugins/access.lfc/download.php '$element2' '$X509_USER_PROXY' '$lfc_server' '$LCG_GFAL_INFOSYS' '$vo_active' '$fs_file2' > /dev/null &");
		        	sleep(3);
		        	if (file_exists($fs_file2)){
			        	$error='false';
						$command = "sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys  /usr/bin/lcg-cp --vo $vo_active $element2  $fs_file ";
						error_log("command is $command");		
		        		exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys  /usr/bin/lcg-cp --vo $vo_active $element2  $fs_file ", $download_values);
			        error_log("fsfile is $fs_file");	
		        	}
		        } 
		    }    		        	
			if (!file_exists($fs_file)) {
				$message['text'].="<span style='color:red;'>Failed download of file ".$file_name.". Maybe the Storage Element is not working.</span>\n";
			} else {
				$message['text'].="File ".$file_name." successfully downloaded.\n";
				$not_failure=false;
			}

		}
		if ($not_failure) {
			$message['type']="fail";
		}
		error_log("appena prima che finisca la funzione download");
		return $message;
	}
*/

	function delete($selectedFiles, &$logMessages) {
		$mess = ConfService::getMessages();
		$message['text']="";
		$message['type']="success";
		error_log(print_r($selectedFiles,true));
		foreach ($selectedFiles as $selectedFile) {
			$nodes = array();
			$del_output = array();
			$replica_values = array();
			if($selectedFile == "" || $selectedFile == DIRECTORY_SEPARATOR) {
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
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server /usr/bin/lfc-ls $dirname2" , $nodes);
			if (sizeof($nodes)==0){
			error_log("11111111111");		
				exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-rm -r $dirname2");
				exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-ls $parent_dir", $new_nodes);
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
			$command3 = "sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lcg-lr $dirname1";
			error_log("command3 is $command3");
				exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lcg-lr $dirname1", $replica_values);
				if (!empty($replica_values)){
				error_log("2222222222222");
					foreach ($replica_values as $value) {
						exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lcg-del -v  $value 2>&1", $del_output);
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
				} else {
				error_log("3333333333333");
					exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys lfc-rm -r $dirname2", $del_output);
				}
				if($message['type']=="success")	{
					foreach (file($this->shared_files) as $name) {
    					if(str_replace("\r\n", "", $name) == $dirname2) {
       						$this->del_line_in_file($this->shared_files, str_replace("\r\n", "", $name));
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
	

	
	
	function copyOrMoveFile($destDir, $srcFile, &$error, &$success, $move = false) {
		$mess = ConfService::getMessages();		
		$destFile = $destDir."/".basename($srcFile);		
		$realSrcFile = $srcFile;
		if(dirname($realSrcFile)==dirname($destFile)) {
			if($move) {
				$error[] = $mess[101];
				return ;
			} else {
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
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server   /usr/bin/lfc-rename $realSrcFile $destFile");

			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys   /usr/bin/lfc-ls $destFile", $new_lfn);


			$message['text']="Failed to rename from ".$realSrcFile." to ".$destFile;
			$message['type']="fail";
            foreach ($new_lfn as $lfn) {
                if (strstr($lfn, $destFile)!="") {
                	$message['text']="File successfully renamed from ".$realSrcFile." to ".$destFile;
					$message['type']="success";
                }
            }
			if ($message['type']=="success") {	
				foreach (file($this->shared_files) as $name) {
					if(str_replace("\r\n", "", $name) == $realSrcFile) {
						$this->del_line_in_file($this->shared_files, $realSrcFile);
						$handle = fopen($this->shared_files, "a+");
						fwrite($handle, $destFile."\r\n");
						fclose($handle);
					}
				}
			}
			AJXP_Controller::applyHook("node.change", array(new AJXP_Node($realSrcFile), new AJXP_Node($destFile), false));
		} else {
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
			} catch (Exception $e) {
				$error[] = $e->getMessage();
				return ;					
			}
		}
		
		if($move) {
			// Now delete original
			// $this->deldir($realSrcFile); // both file and dir
			$messagePart = $mess[74]." ".SystemTextEncoding::toUTF8($destDir);
			if(RecycleBinManager::recycleEnabled() && $destDir == RecycleBinManager::getRelativeRecycle()) {
				RecycleBinManager::fileToRecycle($srcFile);
				$messagePart = $mess[123]." ".$mess[122];
			}
			if(isset($dirRes)) {
				$success[] = $mess[117]." ".SystemTextEncoding::toUTF8(basename($srcFile))." ".$messagePart." (".SystemTextEncoding::toUTF8($dirRes)." ".$mess[116].") ";
			} else  {
				$success[] = $mess[34]." ".SystemTextEncoding::toUTF8(basename($srcFile))." ".$messagePart;
			}
		} else {			
			if(RecycleBinManager::recycleEnabled() && $destDir == "/".$this->repository->getOption("RECYCLE_BIN")) {
				RecycleBinManager::fileToRecycle($srcFile);
			}
			if(isSet($dirRes)) {
				$success[] = $mess[117]." ".SystemTextEncoding::toUTF8(basename($srcFile))." ".$mess[73]." ".SystemTextEncoding::toUTF8($destDir)." (".SystemTextEncoding::toUTF8($dirRes)." ".$mess[116].")";	
			} else {
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
	
	
	function simpleCopy($origFile, $destFile) {
		return copy($origFile, $destFile);
	}
	
	
	public function isWriteable($dir, $type="dir") {
		return is_writable($dir);
	}
	
	
	function deldir($location) {
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
	



	function aclAction($subject, $fileName, $sub_action){
		$lfn_file=$_SESSION['home'].$fileName;
		$pieces = explode("/", $lfn_file);
		$file_old = end($pieces);
		$lfn_dir = str_replace($file_old, "", $lfn_file);		
		if ($sub_action=="addACL") {
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-setacl -m u:'$subject':rx,m:rx $lfn_dir");
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-setacl -m u:'$subject':rwx,m:rwx,g::--- $lfn_file");

			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-setacl -m o::--- $lfn_file");
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-getacl $lfn_file", $getacl_output);
			$message['text']="Failure on setting ACL to user ".$subject." for file ".$lfn_file;
			$message['type']="fail";
		    foreach ($getacl_output as $output_line) {
		            if (strstr($output_line, "user:".$subject)!="") {
							$message['text']="ACL correctly set to user ".$subject." for file ".$lfn_file;
		                    $message['type']="success";
		            }
		    }			
			if ($message['type']=="success"){
				foreach (file($this->shared_files) as $name) {
					if(str_replace("\r\n", "", $name) == $lfn_file) {
						$shared=true;
					}
				}
				if(!$shared) {
					$handle = fopen($this->shared_files, "a+");
					fwrite($handle, $lfn_file."\r\n");
					fclose($handle);
				} 	
			}
		} else if ($sub_action=="delACL") {
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-setacl -d u:'$subject':rwx $lfn_file");
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-getacl $lfn_file", $getacl_output);
			$message['text']="ACL correctly removed to user ".$subject." for file ".$lfn_file;
			$message['type']="success";
			$found_acl=false;
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
			 	foreach (file($this->shared_files) as $name) {
					if(str_replace("\r\n", "", $name) == $lfn_file) {	 					
						$name_right=str_replace("\r\n", "", $name);
						$this->del_line_in_file($this->shared_files, $name_right);
					}
				}
			}
		} else if ($sub_action=="addVOshare") {
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-setacl -m g::rwx $lfn_file");
		//	exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-setacl -d m:rwx $lfn_file");
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-getacl $lfn_file", $getacl_output);
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
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-setacl -m g::--- $lfn_file");
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-setacl -m o::--- $lfn_file");
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-getacl $lfn_file", $getacl_output);
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
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-setacl -m g::--- $lfn_file");
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-setacl -m o::--- $lfn_file");
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-getacl $lfn_file", $getacl_output);
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
                			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys lfc-setacl -d u:'$subject_string':rwx $lfn_file");
                		}		
                	*/
                	/* IL SEGUENTE CODICE COMMENTATO SERVE PER ELIMINARE ACL SOLO PER UTENTI DEL PORTALE; EVENTUALI UTENTI NON-PORTALE MANTENGONO LE LORO ACL*/
                	foreach ($_SESSION['lastName'] as $key => $value) {
                		if (strstr($output_line, "user:".$key)!="") {
                			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-setacl -d u:'$key':rwx $lfn_file");
                		}		
                	}
                }
            }
            exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys /usr/bin/lfc-getacl $lfn_file", $getacl_output_2);
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
			 	foreach (file($this->shared_files) as $name) {
					if(str_replace("\r\n", "", $name) == $lfn_file) {	 					
						$name_right=str_replace("\r\n", "", $name);
						$this->del_line_in_file($this->shared_files, $name_right);
					}
				}
			}
        }
        return $message;
	}




	function homeAction ($path, $sub_action){
			error_log("homeaction path is $path");
		if ($path!="/grid/".$_SESSION['vo']) {
			$path=$_SESSION['home'].$path;	
		}
//		$user_vo_home_dir = $this->conf['user_vo_home_dir_base_path'].$this->user_id.'/';
		$handle2=opendir($this->user_vo_home_dir);
		while ($file = readdir($handle2))
		{					
			$ourFileName = $this->user_vo_home_dir.'homevo.'.$_SESSION['vo'];
			$ourFileHandle = fopen($ourFileName, 'w') or die("can't open file");
			$arr = file($ourFileName);
			unset($arr[0]);
			$content = $path."/";
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
			if($vo_active=="gridit" && $hostName=="darkstorm.cnaf.infn.it"){
			$command="sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys  /usr/bin/lcg-rep --connect-timeout 10 -S GRIDIT_NORMAL -d $hostName $lfn_file";
			error_log("command is $command");
				exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys  /usr/bin/lcg-rep --connect-timeout 10 -S GRIDIT_NORMAL -d $hostName $lfn_file", $output);
			} else {
				exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys  /usr/bin/lcg-rep --connect-timeout 10 -d $hostName $lfn_file", $output);
			}
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys  /usr/bin/lcg-lr $lfn_file", $replicas);
			$message['text']="Replica failed on ".$hostName;
			$message['type']="fail";
			foreach ($replicas as $replica) {
	        	if (strstr($replica, $hostName)!="") {
                	$message['text']="File successfully replicated on ".$hostName;
                	$message['type']="success";
	        	}
	    	}	
		} else {
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys   /usr/bin/lcg-del -s $hostName $lfn_file", $output);
			exec("sudo -u tomcat X509_USER_PROXY=$this->x509_user_proxy LFC_HOST=$this->lfc_server LCG_GFAL_INFOSYS=$this->lcg_gfal_infosys   /usr/bin/lcg-lr $lfn_file", $replicas);
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
        $handle=opendir($dirName);
        $count = 0;
        while (strlen($file = readdir($handle)) > 0) {
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
            }else {
                $this->recursivePurge($dirName."/".$file, $purgeTime);
            }
        }
        closedir($handle);
    }
    


	function del_line_in_file($filename, $text_to_delete) {
		// split the string up into an array
		$file_array = array();
		$file = fopen($filename, 'rt');
		if($file) {
			while(!feof($file)) {
			  $val = fgets($file);
			  if(is_string($val))
			    array_push($file_array, $val);
			}      		 
			fclose($file);
		}
		// delete from file
		for($i = 0; $i < count($file_array); $i++) {
			if(strstr($file_array[$i], $text_to_delete)) {
				if($file_array[$i] == $text_to_delete . "\r\n") $file_array[$i] = '';
			}
		}
		// write it back to the file
		$file_write = fopen($filename, 'wt');       
		if($file_write) {
			fwrite($file_write, implode("", $file_array));
			fclose($file_write);
		}
	}

	function deleteDir($dirPath) {
	    if (! is_dir($dirPath)) {
	        throw new InvalidArgumentException("$dirPath must be a directory");
	    }
	    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
	        $dirPath .= '/';
	    }
	    $files = glob($dirPath . '*', GLOB_MARK);
	    foreach ($files as $file) {
	        if (is_dir($file)) {
	            self::deleteDir($file);
	        } else {
	        error_log("elimino $file");
	            unlink($file);
	        }
	    }
	    rmdir($dirPath);
	}
/*
    function rrmdir($dir) {
	   if (is_dir($dir)) {
	     $objects = scandir($dir);
	     foreach ($objects as $object) {
	       if ($object != "." && $object != "..") {
	         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
	       }
	     }
	     reset($objects);
	     rmdir($dir);		     
	  }
	}		
*/
function clean_file_name($filename)
        {
                $bad = array(
                                                "<!--",
                                                "-->",
                                                "'",
                                                "<",
                                                ">",
                                                '"',
                                                '&',
                                                '$',
                                                '=',
                                                ';',
                                                '?',
                                                ' ',
                                                ':',
                                                "\n",
                                                "\r",
                                                "%20",
                                                "%22",
                                                "%3c",          // <
                                                "%253c",        // <
                                                "%3e",          // >
                                                "%0e",          // >
                                                "%28",          // (
                                                "%29",          // )
                                                "%2528",        // (
                                                "%26",          // &
                                                "%24",          // $
                                                "%3f",          // ?
                                                "%3b",          // ;
                                                "%3d",          // =
                                                "(",
                                                ")",
                                        );
                                       
                $filename = str_replace($bad, '', $filename);
 
                return stripslashes($filename);
        }

function replace_accents($string) 
{ 
  return str_replace( array('à','á','â','ã','ä', 'ç', 'è','é','ê','ë', 'ì','í','î','ï', 'ñ', 'ò','ó','ô','õ','ö', 'ù','ú','û','ü', 'ý','ÿ', 'À','Á','Â','Ã','Ä', 'Ç', 'È','É','Ê','Ë', 'Ì','Í','Î','Ï', 'Ñ', 'Ò','Ó','Ô','Õ','Ö', 'Ù','Ú','Û','Ü', 'Ý'), array('a','a','a','a','a', 'c', 'e','e','e','e', 'i','i','i','i', 'n', 'o','o','o','o','o', 'u','u','u','u', 'y','y', 'A','A','A','A','A', 'C', 'E','E','E','E', 'I','I','I','I', 'N', 'O','O','O','O','O', 'U','U','U','U', 'Y'), $string); 
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
