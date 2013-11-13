<?php
//		proc_nice(-5);
		error_log(print_r($argv,true));
		$selectedFile_list=$argv[1];
		$x509_user_proxy=$argv[2];
		$lfc_server=$argv[3];
		$home=$argv[4];
		$random=$argv[5];
		$lcg_gfal_infosys=$argv[6];
		$user_home_dir_transfer=$argv[7];
		$total_files_num=$argv[8];
		$user_id=$argv[9];
		$vo_active = $argv[10];
		$dir_compl = $argv[11];
		$iesimo = $argv[12];
		$total_files_size_list = $argv[13];
		$srtimeout = $argv[14];
		$i=0;
		$user_home_dir_transfer_error=$user_home_dir_transfer."/error";
		$user_home_dir_transfer_ok=$user_home_dir_transfer."/ok";
		$selectedFile_array=explode('***', $selectedFile_list);
		$selectedFile_size=explode('***', $total_files_size_list);		
		foreach ($selectedFile_array as $selectedFile){
//			error_log("selectedFile is $selectedFile");
//			error_log("selectedFile_size is $selectedFile_size");
			$user_home_dir_transfer_iesimo=$user_home_dir_transfer.'/file_'.$iesimo.'.zzz';
			$user_home_dir_transfer_grid_error=$user_home_dir_transfer.'/grid_error';
			$lfn_file="lfn:/".$home."/".$selectedFile;  
			$lfn_file2=$home."/".$selectedFile;
			$selectedFile_pieces=split("/", $selectedFile);
			$file_name=end($selectedFile_pieces);
			$file_name = clean_file_name($file_name);
			$filename="file:/opt/dm/ajp/data/personal/".$user_id."/".$file_name;		
			$random2=rand(10, 99);
			$filename_rand ="file:/opt/dm/ajp/data/personal/".$user_id."/".$random2."_".$file_name;
			$downloaded_file_name_rand=substr($filename_rand, 5);
			$downloaded_file_name=substr($filename, 5);
//			error_log("$dir_compl/$file_name");
			if($selectedFile!=$dir_compl."/".$file_name){
				$file_pieces=explode("/",$selectedFile);
				$new_dir_name="/opt/dm/ajp/data/personal/".$user_id."/".$file_pieces[sizeof($file_pieces)-2];
				if(!is_dir($new_dir_name)){
					mkdir($new_dir_name);
					chmod($new_dir_name, 0775);
				}
				$filename=$new_dir_name."/".$file_name;
				$filename_rand=$new_dir_name."/".$random2."_".$file_name;
				$downloaded_file_name=$filename;
				$downloaded_file_name_rand=$filename_rand;
			}						
			$replica_values2=array();
			exec("sudo -u tomcat X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server /usr/bin/lcg-lr $lfn_file", $replica_values2);
			shuffle($replica_values2);
			$error='true';
			$path=$user_home_dir_transfer;
			$path_user = str_replace($random, '', $path);		
			$user_dir="/opt/dm/ajp/data/personal/".$user_id."/";
			foreach ($replica_values2 as $element2)
			{	
				if($error=="true"){
					$size1=$selectedFile_size[$i];
	                $command="sudo -u tomcat X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server LCG_GFAL_INFOSYS=$lcg_gfal_infosys  lcg-cp --sendreceive-timeout 5 --connect-timeout 5 --bdii-timeout 5 --srm-timeout 5 --vo $vo_active $element2 $filename_rand";
		            error_log("command is $command");
	                exec("sudo -u tomcat X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server LCG_GFAL_INFOSYS=$lcg_gfal_infosys  lcg-cp --sendreceive-timeout 5 --vo $vo_active $element2 $filename_rand > /dev/null &");
					usleep(4800000);
			        if (file_exists($downloaded_file_name_rand)){ 
				        if (filesize($downloaded_file_name_rand!=$size1)){  
//			                $command="sudo -u tomcat X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server LCG_GFAL_INFOSYS=$lcg_gfal_infosys  /usr/bin/lcg-cp --sendreceive-timeout 90 --vo $vo_active $element2  $filename ";
//			                error_log($command);
						    exec("sudo -u tomcat X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server LCG_GFAL_INFOSYS=$lcg_gfal_infosys  /usr/bin/lcg-cp --sendreceive-timeout $srtimeout --vo $vo_active $element2  $filename ", $download_values);	
				        	$size2=filesize($downloaded_file_name);
				        	error_log("size1 is $size1 and size2 is $size2");
				        	if($size2==$size1){
					        	$error='false';	
				        	}
				        } else {
				        	$pieces_downloaded_file_name_rand=explode("/", $downloaded_file_name_rand);
				        	$file_rand=end($pieces_downloaded_file_name_rand);
				        	$file_rand_ok=substr($file_rand, 3);
				        	array_pop($pieces_downloaded_file_name_rand);
				        	$pieces_downloaded_file_name_rand[]=$file_rand_ok;
				        	$downloaded_file_name_rand_ok=implode("/",$pieces_downloaded_file_name_rand);
				        	rename($downloaded_file_name_rand, $downloaded_file_name_rand_ok);
				        	$error='false';
				        }			        
			        } else {
			        	$error='true';
		        	}		        
		        }	
		    }  
			if(!file_exists($downloaded_file_name)||$error!='false'){
				$fd = fopen($user_home_dir_transfer_error, 'a');
				fwrite($fd, $file_name.PHP_EOL);
				fclose($fd);
			} else {
				$fd = fopen($user_home_dir_transfer_ok, 'a');
				fwrite($fd, $file_name.PHP_EOL);
				fclose($fd);
			}
			touch("$user_home_dir_transfer_iesimo");
			unlink($downloaded_file_name_rand);
			$iesimo++;
			$i++;
//			error_log("iesimo is $iesimo");
		}
		function clean_file_name($filename) {
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
?>