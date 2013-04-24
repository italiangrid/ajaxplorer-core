<?php

			error_log(print_r($argv,true));
			
			$selectedFile=$argv[1];
			$x509_user_proxy=$argv[2];
			$lfc_server=$argv[3];
			$home=$argv[4];
			$protocol=$argv[5];

			$server_comp=$argv[6];
//			$server=$server_comp;

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
//			$pieces_2=explode(":", $server_comp);		
//			$server=$pieces_2[0];
//			$pref=$pieces_2[1];
//if (isset($argv[19])) {
//			$pref = $argv[19];
//} else {
//			$pref = "";
//}


			$filename=$fs_file;
			$downloaded_file_name=substr($filename, 5);
			$firstname=$firstName;
			$lastname=$lastName;
			$path_user = str_replace($random, '', $path);		
			$pieces_1 = explode("/", $downloaded_file_name);
			$file=end($pieces_1);
			
			
			
				$username="anonymous";
		        $password = "anonymous";
		        $server = "omii005-vm01.cnaf.infn.it";
		        $pref="free/";
		        $server_dest ="http://".$server.":8085/";
				$path_completo=$server_dest.$pref.$file;
				
				
				
						
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
				
	
				$ch = curl_init();
				$fp = fopen($downloaded_file_name, 'r');
				error_log("pathcompleto is $path_completo");
				curl_setopt($ch, CURLOPT_URL, $path_completo);
				curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
				curl_setopt($ch, CURLOPT_UPLOAD, 1);
				curl_setopt($ch, CURLOPT_INFILE, $fp);
				curl_setopt($ch, CURLOPT_INFILESIZE, filesize($downloaded_file_name));
				curl_exec ($ch);
				$error_no = curl_errno($ch);
				curl_close ($ch);



					if ($error_no == 0) {
						$message = 'ok';
						error_log("ok webdav");						
						$fd = fopen($path."/ok", 'a');
						fwrite($fd, $file.PHP_EOL);
						fclose($fd);
						unlink($path_user.$file);
										
					} else {
						$message = 'File upload error: $error_no. Error codes explained here http://curl.haxx.se/libcurl/c/libcurl-errors.html';
						error_log("error webdav");
						$fd = fopen($path."/error", 'a');
						fwrite($fd, $file.PHP_EOL);
						fclose($fd);
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
												
											$ch = curl_init();
				$fp = fopen($downloaded_file_name, 'r');
				error_log("pathcompleto is $path_completo");
				curl_setopt($ch, CURLOPT_URL, $path_completo);
				curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
				curl_setopt($ch, CURLOPT_UPLOAD, 1);
				curl_setopt($ch, CURLOPT_INFILE, $fp);
				curl_setopt($ch, CURLOPT_INFILESIZE, filesize($downloaded_file_name));
				curl_exec ($ch);
				$error_no = curl_errno($ch);
				curl_close ($ch);
							curl_close ($ch);
								if ($error_no == 0) {
									$message = 'ok';						
									$fd = fopen($path."/ok", 'a');
									fwrite($fd, $file.PHP_EOL);
									fclose($fd);
									$str = file_get_contents($path."/list");
									$str=str_replace("/".$file,'ooooooooooooo',$str);
									file_put_contents($path."/list", $str);										 
							    } 						    
						 				
						unlink($path_user.$file);
				}
				
			}
				
			error_log('finito');
			$lines11 = file($path."/ok", FILE_IGNORE_NEW_LINES);
			foreach($lines11 as $line11){
				error_log("line is $line11");
				$str_error11 = file_get_contents($path."/ok");
				error_log("err1 is $str_error11");
				$str_error11=str_replace($line11,$line11."</br>",$str_error11);
				error_log("err2 is $str_error11");
				file_put_contents($path."/ok", $str_error11);				
				}		
			
			$lines22 = file($path."/grid_error", FILE_IGNORE_NEW_LINES);
			foreach($lines22 as $line22){
				error_log("line is $line22");
				$str_error22 = file_get_contents($path."/grid_error");
				error_log("err1 is $str_error22");
				$str_error22=str_replace($line22,$line22."</br>",$str_error22);
				error_log("err2 is $str_error22");
				file_put_contents($path."/grid_error", $str_error22);				
				}		
							
			$lines33 = file($path."/error", FILE_IGNORE_NEW_LINES);
			foreach($lines33 as $line33){
				error_log("line is $line33");
				$str_error33 = file_get_contents($path."/error");
				error_log("err1 is $str_error33");
				$str_error33=str_replace($line33,$line33."</br>",$str_error33);
				error_log("err2 is $str_error33");
				file_put_contents($path."/error", $str_error33);				
				}		

			$to = "$mail";
			$subject = 'Grid file transfer completed'; 
			$from ='igi-portal@italiangrid.it'; 
			error_log("user is $username");
			error_log("server is $server_comp");
			$body ="
			<html>
				<body> 
						<div style=margin-top:10px;margin-bottom:10px> 
						<p>Dear $firstName $lastName,</br></br> this is the summary of the file transfer from Grid to <b>$protocol://$server_comp</b> as user <b>$username</b> (ref. $random):<p/>
						</div>
						<div style=background-color:#eee;width:80%;margin:auto;padding-left:20px;border-color:#ccc;border-style:solid;border-width:2px;margin-bottom:5px;>
						    <p style=color:green;font-weight:bold;>The files correctly copied on your server:</p>        
					        <p style=margin-left:20px;>$str_error11</p>
					     </div>
					     <div style=background-color:#eee;width:80%;margin:auto;padding-left:20px;border-color:#ccc;border-style:solid;border-width:2px;margin-top:5px;>   
					        <p style=color:red;font-weight:bold;>The files not copied for Grid error reasons:</p>
					        <p style=margin-left:20px;>$str_error22</p>
					     </div>   
					     <div style=background-color:#eee;width:80%;margin:auto;padding-left:20px;border-color:#ccc;border-style:solid;border-width:2px;margin-top:5px;>      
					        <p style=color:red;font-weight:bold;>The files not copied for other reasons:</p>
					        <p style=margin-left:20px;>$str_error33</p>
					    </div>
					    <div style=margin-top:10px;margin-bottom:10px>
					        Need assistance? Replay this mail to <a tabindex='120' href='mailto:igi-portal-admin@lists.italiangrid.it'>Administrators</a>.
					    </div>
				</body>
			</html>
			"; 				        
			ini_set("sendmail_from", $from);				
			$header  = "MIME-Version: 1.0\r\n";
			$header .= "Content-type: text/html; charset: utf8\r\n";
			 if (mail($to, $subject, $body, $header)) {
					 unlink($path."/list");
					 unlink($path."/ok");
					 unlink($path."/error");
					 unlink($path."/grid_error");
					 rmdir($path);						 
			    }
	

	}								
							
?>
