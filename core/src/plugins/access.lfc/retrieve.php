<?php

			error_log(print_r($argv,true));
			
			$selectedFile=$argv[1];
			$x509_user_proxy=$argv[2];
			$lfc_server=$argv[3];
			$home=$argv[4];
			$protocol=$argv[5];
			$server_comp=$argv[6];
			$username=$argv[7];
			$password=$argv[8];
			$random=$argv[9];
			$lcg_gfal_infosys=$argv[10];
			$user_home_dir_big_transfer=$argv[11];
			$numElements=$argv[12];
			$mail=$argv[13];
			$firstName=$argv[14];
			$lastName=$argv[15];
			$user_id=$argv[16];
			$user_home_dir_big_transfer_list=$argv[17];
			$vo_active = $argv[18];
			$pref = $argv[19];
			
			if (substr($pref, 0, 1)!="/" && $pref!='') {
				$pref = "/".$pref;
			}
			if (substr($pref, -1)!="/" && $pref!='') {
				$pref = $pref."/";
			}
			error_log("pref is $pref");
			$port = $argv[20];
			$mycloud = $argv[21];
			$user_home_dir_big_transfer_grid_error=$user_home_dir_big_transfer.'/grid_error';
			$lfn_file="lfn:/".$home.$selectedFile;  
			$ff=$selectedFile;
			$selectedFile= substr($selectedFile, 1);			
			$file_name=$selectedFile;
			$fs_file="file:/var/www/html/ajp/data/personal/".$user_id."/".$file_name;			
			$replica_values2=array();
			exec("sudo X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server /usr/bin/lcg-lr $lfn_file", $replica_values2);
			shuffle($replica_values2);
			error_log(print_r($replica_values2,true));
			$error='true';
			$path=$user_home_dir_big_transfer;
			$server=$server_comp;
			$filename=$fs_file;
			$downloaded_file_name=substr($filename, 5);
			$firstname=$firstName;
			$lastname=$lastName;
			$path_user = str_replace($random, '', $path);		
			$pieces_1 = explode("/", $downloaded_file_name);
			$file=end($pieces_1);
			$server_dest =$protocol."://".$server.":".$port;
			
			
			
			$path_completo=$server_dest.$pref.$file;
			$path_mail=$server_dest.$pref;
			error_log("path_completo is $path_completo");
						
						
			foreach ($replica_values2 as $element2)
			{
			
				if($error='true'){				
					$command = "sudo X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server LCG_GFAL_INFOSYS=$lcg_gfal_infosys  lcg-cp --vo $element2  $fs_file";					
					error_log("command is $command");													
		        	exec("sudo X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server LCG_GFAL_INFOSYS=$lcg_gfal_infosys  /usr/bin/lcg-cp --vo $vo_active $element2  $fs_file --sendreceive-timeout 10 ", $download_values);	
		        	$file_right_size=33333333333333333333;		        			        	
		        	$downloaded_file_name=substr($fs_file, 5);		      
		        	if (!file_exists($downloaded_file_name)) {
		        	error_log("1111111111");
		        	$error=='true';
		        	}
		        	else if (file_exists($downloaded_file_name)){
		        	$size2=filesize($downloaded_file_name);
		        		if ($size2==$file_right_size){
		        			$error=='false';
		        		} else if ($size2<200){
		        		error_log("222222222222");
		        			$error=='true';
		        			unlink($downloaded_file_name);
		        		} else if (200<=$size2 && $size2<=$file_right_size){
		        		error_log("33333333");
			        		exec("sudo X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server LCG_GFAL_INFOSYS=$lcg_gfal_infosys  /usr/bin/lcg-cp --vo $vo_active $element2  $fs_file ", $download_values);			        
			        		$error=='false';	
		        	    }
		            }
		        }	
		    }    		        	
			
			if(!file_exists($downloaded_file_name)){
				
				error_log("sono andato avanti");
				$fd = fopen($user_home_dir_big_transfer_grid_error, 'a');	
				fwrite($fd, $selectedFile.PHP_EOL);
				fclose($fd);
				$str = file_get_contents($user_home_dir_big_transfer_list);
				$str=str_replace($selectedFile,'ooooooooooooo',$str);
				$occ=substr_count($str, 'ooooooooooooo');
				file_put_contents($user_home_dir_big_transfer_list, $str);					
			
			} else {
						
			
				if($protocol=='ftp'|| $protocol=='http'){				
				$ch = curl_init();
				$fp = fopen($downloaded_file_name, 'r');
//				$server_dest ="ftp://".$server."/";
				curl_setopt($ch, CURLOPT_URL, $path_completo);
//				curl_setopt($ch, CURLOPT_URL, $server_dest.$file);
				curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
				curl_setopt($ch, CURLOPT_UPLOAD, 1);
				curl_setopt($ch, CURLOPT_INFILE, $fp);
				curl_setopt($ch, CURLOPT_INFILESIZE, filesize($downloaded_file_name));
				curl_exec ($ch);
				$error_no = curl_errno($ch);
				curl_close ($ch);
				
					if ($error_no == 0) {
						$message = 'ok';
						error_log("ok $protocol");						
						$fd = fopen($path."/ok", 'a');
						fwrite($fd, $file.PHP_EOL);
						fclose($fd);
						unlink($downloaded_file_name);
					} else {
						$message = 'File upload error: $error_no. Error codes explained here http://curl.haxx.se/libcurl/c/libcurl-errors.html';
						error_log("error $protocol");
						$fd = fopen($path."/error", 'a');
						fwrite($fd, $file.PHP_EOL);
						fclose($fd);
					}
							
				} else {
					$filepath=$pref.$file;	
					$connection = ssh2_connect("$server", "$port");
					ssh2_auth_password($connection, "$username", "$password");
					$error_no = ssh2_scp_send($connection, "$downloaded_file_name", "$filepath", 0644);
			
					if ($error_no) {
						$message = 'ok';
						error_log("ok $protocol");						
						$fd = fopen($path."/ok", 'a');
						fwrite($fd, $file.PHP_EOL);
						fclose($fd);
						unlink($path_user.$file);
										
					} else {
						$message = 'File upload error: $error_no. Error codes explained here http://curl.haxx.se/libcurl/c/libcurl-errors.html';
						error_log("error $protocol");
						$fd = fopen($path."/error", 'a');
						fwrite($fd, $file.PHP_EOL);
						fclose($fd);
					}
	
				}
						
			
			$str = file_get_contents($user_home_dir_big_transfer_list);
			$str=str_replace($file,'ooooooooooooo',$str);
			$occ=substr_count($str, 'ooooooooooooo');
			error_log("str is $str");
			file_put_contents($path."/list", $str);	
			error_log("occ is $occ");					
			}
				
			error_log("path is $path");		
			if($occ==$numElements){		
				$str2 = file_get_contents($path."/error");
				error_log("str2 is $str2");
				$error_Files = explode("\n", $str2);
				error_log(print_r($error_Files,true));
				foreach ($error_Files as $error_File){
					error_log("error_File is $error_File");
					if($error_File!=''){
						if($protocol=='ftp'|| $protocol=='webdav'){						
							$ch = curl_init();
							$file=$error_File;
							$fp = fopen($path_user.$file, 'r');
							//$server_dest ="ftp://".$server."/";					
							curl_setopt($ch, CURLOPT_URL, $path_completo);
							curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
							curl_setopt($ch, CURLOPT_UPLOAD, 1);
							curl_setopt($ch, CURLOPT_INFILE, $fp);
							curl_setopt($ch, CURLOPT_INFILESIZE, filesize($path_user.$file));		
							curl_exec ($ch);
							$error_no = curl_errno($ch);
							curl_close ($ch);
								if ($error_no == 0) {
									$message = 'ok';
									error_log("ok $protocol");						
									$fd = fopen($path."/ok", 'a');
									fwrite($fd, $file.PHP_EOL);
									fclose($fd);
									$str = file_get_contents($path."/list");
									$str=str_replace("/".$file,'ooooooooooooo',$str);
									file_put_contents($path."/list", $str);										 
							    } 						    
						 } else {
							$filepath=$pref.$file;
							$connection = ssh2_connect("$server", "$port");
							ssh2_auth_password($connection, "$username", "$password");
							$error_no = ssh2_scp_send($connection, "$downloaded_file_name", "$filepath", 0644);
			
								if ($error_no) {
									$message = 'ok';
									error_log("ok $protocol");						
									$fd = fopen($path."/ok", 'a');
									fwrite($fd, $file.PHP_EOL);
									fclose($fd);
											
								} 			
						}
				unlink($path_user.$file);
				}
				
			}
				
			error_log('finito');
			$lines_ok = file($path."/ok", FILE_IGNORE_NEW_LINES);
			$str_error_ok = "";
			foreach($lines_ok as $line_ok){
				error_log("line is $line_ok");
				$str_error_ok = file_get_contents($path."/ok");
				error_log("err1 is $str_error_ok");
				$str_error_ok=str_replace($line_ok,$line_ok."</br>",$str_error_ok);
				error_log("err2 is $str_error_ok");
				file_put_contents($path."/ok", $str_error_ok);				
				}		
			if ($str_error_ok=="") {
				$display_ok="none";
			} else {
				$display_ok="block";
			}
			
			$lines_grid = file($path."/grid_error", FILE_IGNORE_NEW_LINES);
			$str_error_grid="";
			foreach($lines_grid as $line_grid){
				error_log("line is $line_grid");
				$str_error_grid = file_get_contents($path."/grid_error");
				error_log("err1 is $str_error_grid");
				$str_error_grid=str_replace($line_grid,$line_grid."</br>",$str_error_grid);
				error_log("err2 is $str_error_grid");
				file_put_contents($path."/grid_error", $str_error_grid);				
				}
			if ($str_error_grid=="") {
				$display_grid="none";
			} else {
				$display_grid="block";
			}		
							
			$lines_error = file($path."/error", FILE_IGNORE_NEW_LINES);
			$str_error_other="";
			foreach($lines_error as $line_error){
				error_log("line is $line_error");
				$str_error_other = file_get_contents($path."/error");
				error_log("err1 is $str_error_other");
				$str_error_other=str_replace($line_error,$line_error."</br>",$str_error_other);
				error_log("err2 is $str_error_other");
				file_put_contents($path."/error", $str_error_other);				
				}		
			if ($str_error_other=="") {
				$display_other="none";
			} else {
				$display_other="block";
			}

			$to = "$mail";
			$subject = 'Grid file transfer completed'; 
			$from ='igi-portal@italiangrid.it'; 
			error_log("user is $username");
			error_log("server is $server_comp");
			$body ="
			<html><head></head>
				<body style=background-image:url('http://gridlab07.cnaf.infn.it:8090/images/overlay3.png');>
				<div style=width:90%;background-color:white;margin-left:auto;margin-right:auto;margin-top:10px;border-style:solid;border-width:5px;margin-top:15px;margin-bottom:15px;>
				<div style=padding-left:10px;padding-top:10px;><a href='https://portal.italiangrid.it'><img src='http://gridlab07.cnaf.infn.it:8090/images/logo.png' width='80px' height='40px' alt='' /></a></div> 
						<div style=margin-top:10px;margin-bottom:10px;color:black;padding-left:10px;padding-right:10px;>
							<p>Dear $firstName $lastName,</br></br> this is the summary of the file transfer from Grid to the server specified: <b>$path_mail</b> as user <b>$username</b>.<br/> 
							Reference: $random.<br/>
							<p/>
						</div>
						<div style=background-color:#eee;width:80%;margin:auto;padding-left:20px;border-color:#ccc;border-style:solid;border-width:2px;margin-bottom:10px;border-radius:10px;display:$display_ok>
						    <p style=color:green;font-weight:bold;>The files correctly copied on your server:</p>        
					        <p style=margin-left:20px;>$str_error_ok</p>
					     </div>
					     <div style=background-color:#eee;width:80%;margin:auto;padding-left:20px;border-color:#ccc;border-style:solid;border-width:2px;margin-top:5px;border-radius:10px;display:$display_grid>   
					        <p style=color:red;font-weight:bold;>The files not copied for Grid error reasons:</p>
					        <p style=margin-left:20px;>$str_error_grid</p>
					     </div>   
					     <div style=background-color:#eee;width:80%;margin:auto;padding-left:20px;border-color:#ccc;border-style:solid;border-width:2px;margin-top:10px;border-radius:10px;display:$display_other>      
					        <p style=color:red;font-weight:bold;>The files not copied for errors in server communication:</p>
					        <p style=margin-left:20px;>$str_error_other</p>
					    </div>
					    <div style=margin-top:20px;margin-bottom:20px;color:black;padding-left:10px;padding-right:10px;>
					        Need assistance? Replay this mail to <a tabindex='120' href='mailto:igi-portal-admin@lists.italiangrid.it'>Administrators</a>.
					    </div>
					</div>
				</body>
			</html>
			"; 				        
//			ini_set("sendmail_from", $from);				
			$header  = "MIME-Version: 1.0\r\n";
			$header .= "Content-type: text/html; charset=iso-8859-1\r\n";
			$header .= "From: igi-portal@italiangrid.it";
			
			 if (mail($to, $subject, $body, $header)) {
					 unlink($path."/list");
					 unlink($path."/ok");
					 unlink($path."/error");
					 unlink($path."/grid_error");
					 rmdir($path);						 
			    }
	

	}								
					
			
?>
