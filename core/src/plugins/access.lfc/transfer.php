<?php			
		
		$protocol=$argv[1];
		
		
		$server_comp=$argv[2];
		$pieces_2=explode(":", $server_comp);
		
		$server=$pieces_2[0];
		$pref=$pieces_2[1];
		
		
		$username=$argv[3];
		$password=$argv[4];					
		$filename=$argv[5];
		$random=$argv[6];
		$path=$argv[7];
		$downloaded_file_name=substr($filename, 5);
		$numElements=$argv[8];
		$mail=$argv[9];
		$firstname=$argv[10];
		$lastname=$argv[11];
		$path_user = str_replace($random, '', $path);		
		$pieces_1 = explode("/", $downloaded_file_name);
		$file=end($pieces_1);
		
		error_log("pref is $pref");
		error_log("path_user is $path_user");
		error_log("protocol is $protocol");
		error_log("server is $server");
		error_log("username is $username");
		error_log("password is $password");	
		error_log("filename is $filename");	
		error_log("random is $random");
		error_log("path is $path");
		error_log("downloaded_file_name is $downloaded_file_name");
		error_log("file is $file");
		
		if($protocol=='ftp'){				
			$ch = curl_init();
			$fp = fopen($downloaded_file_name, 'r');
			$server_dest ="ftp://".$server."/";
			curl_setopt($ch, CURLOPT_URL, $server_dest.$pref.$file);
			curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
			curl_setopt($ch, CURLOPT_UPLOAD, 1);
			curl_setopt($ch, CURLOPT_INFILE, $fp);
			curl_setopt($ch, CURLOPT_INFILESIZE, filesize($downloaded_file_name));
			curl_exec ($ch);
			$error_no = curl_errno($ch);
			curl_close ($ch);
			
			if ($error_no == 0) {
				$message = 'ok';
				error_log("ok ftp");						
				$fd = fopen($path."/ok", 'a');
				fwrite($fd, $file.PHP_EOL);
				fclose($fd);
				unlink($path_user.$file);
								
			} else {
				$message = 'File upload error: $error_no. Error codes explained here http://curl.haxx.se/libcurl/c/libcurl-errors.html';
				error_log("error ftp");
				$fd = fopen($path."/error", 'a');
				fwrite($fd, $file.PHP_EOL);
				fclose($fd);
			}
						
		} else {
			$filepath=$pref.$file;	
			$connection = ssh2_connect("$server", 22);
			ssh2_auth_password($connection, "$username", "$password");
			$error_no = ssh2_scp_send($connection, "$downloaded_file_name", "$filepath", 0644);
	
			if ($error_no) {
				$message = 'ok';
				error_log("ok sftp");						
				$fd = fopen($path."/ok", 'a');
				fwrite($fd, $file.PHP_EOL);
				fclose($fd);
				unlink($path_user.$file);
								
			} else {
				$message = 'File upload error: $error_no. Error codes explained here http://curl.haxx.se/libcurl/c/libcurl-errors.html';
				error_log("error sftp");
				$fd = fopen($path."/error", 'a');
				fwrite($fd, $file.PHP_EOL);
				fclose($fd);
			}

	    }		
		
		$str = file_get_contents($path."/list");
		$str=str_replace($file,'ooooooooooooo',$str);
		file_put_contents($path."/list", $str);			
		$occ=substr_count($str, 'ooooooooooooo');					
		if($occ==$numElements){		
			$str2 = file_get_contents($path."/error");
			$error_Files = explode("\n", $str2);
			error_log(print_r($error_Files,true));
			foreach ($error_Files as $error_File){
				error_log("error_File is $error_File");
				if($error_File!=''){
				if($protocol=='ftp'){
				
					error_log("il file non copiato is $error_File");
					$ch = curl_init();
					$pieces_1 = explode("/", $error_File);
					$file=end($pieces_1);
					$fp = fopen($path_user.$file, 'r');
					$server_dest ="ftp://".$server."/";					
					curl_setopt($ch, CURLOPT_URL, $server.$pref.$file);
					curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
					curl_setopt($ch, CURLOPT_UPLOAD, 1);
					curl_setopt($ch, CURLOPT_INFILE, $fp);
					curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file));		
					curl_exec ($ch);
					$error_no = curl_errno($ch);
					curl_close ($ch);
		
					if ($error_no == 0) {
						$message = 'ok';						
						$fd = fopen($path."/ok", 'a');
						fwrite($fd, $file.PHP_EOL);
						fclose($fd);
						$str = file_get_contents($path."/error");
						$str=str_replace("/".$file,'ooooooooooooo',$str);
						file_put_contents($path."/error", $str);			 
				    } 
				
				 } else {
					$filepath=$pref.$file;
					$connection = ssh2_connect("$server", 22);
					ssh2_auth_password($connection, "$username", "$password");
					$error_no = ssh2_scp_send($connection, "$downloaded_file_name", "$filepath", 0644);
	
					if ($error_no) {
						$message = 'ok';
						error_log("ok sftp");						
						$fd = fopen($path."/ok", 'a');
						fwrite($fd, $file.PHP_EOL);
						fclose($fd);
						unlink($path_user.$file);
									
					} else {
						$message = 'File upload error: $error_no. Error codes explained here http://curl.haxx.se/libcurl/c/libcurl-errors.html';
						error_log("error sftp");
						$fd = fopen($path."/error", 'a');
						fwrite($fd, $file.PHP_EOL);
						fclose($fd);
					}					
				}
			
			unlink($path_user.$file);
			}
			
			}
			
			
			$message='finito';
			
			mail ( "$mail" , "Trasferimento file" , "$message");
//			unlink($path."/list");
//			unlink($path."/ok");
//			unlink($path."/error");
//			unlink($path."/grid_error");
//			rmdir($path);				
		}

    
?>