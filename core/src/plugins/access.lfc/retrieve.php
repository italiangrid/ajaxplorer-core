<?php
	proc_nice(15);
	error_log(print_r($argv,true));
	$selectedFile_list=$argv[1];
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
	$vo_active = $argv[17];
	$pref = $argv[18];
	if (substr($pref, 0, 1)!="/" && $pref!='') {
		$pref = "/".$pref;
	}
	if (substr($pref, -1)!="/" && $pref!='') {
		$pref = $pref."/";
	}
	$port = $argv[19];
	$mycloud = $argv[20];
	$iesimo = $argv[21];
	$dir = $argv[22];
	$total_files_size_list = $argv[23];
	$x=0;
	$dir_selected= end(explode('/', substr($dir, 0, -1)));
	if ($dir_selected=="") {
		$dir_selected = "/";
	} else {
		$dir_selected = "/".$dir_selected."/"; 
	}       	
    require_once('/opt/dm/ajp/plugins/access.lfc/phpmailer/class.phpmailer.php');
    require_once('/opt/dm/ajp/plugins/access.lfc/fpdf/fpdf.php');
		
	$selectedFile_array=explode('***', $selectedFile_list);
	$selectedFile_size=explode('***', $total_files_size_list);
//	error_log(print_r($selectedFile_size,true));
	foreach ($selectedFile_array as $selectedFile){
		$user_home_dir_big_transfer_grid_error=$user_home_dir_big_transfer.'/grid_error';
		$lfn_file="lfn:/".$home."/".$selectedFile;  
		$lfn_file2=$home."/".$selectedFile;
		$selectedFile_pieces=split("/", $selectedFile);
		$file_name=end($selectedFile_pieces);		
		$file_name = clean_file_name($file_name);
		$filename="file:/opt/dm/ajp/data/personal/".$user_id."/".$file_name;
		$random2=rand(5, 15);
		$filename_rand ="file:/opt/dm/ajp/data/personal/".$user_id."/".$random2."_".$file_name;
		$downloaded_file_name_rand=substr($filename_rand, 5);
		$replica_values2=array();
		exec("sudo -u tomcat X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server /usr/bin/lcg-lr $lfn_file", $replica_values2);
		shuffle($replica_values2);
		$error='true';
		$path=$user_home_dir_big_transfer;
		$server=$server_comp;
		$downloaded_file_name=substr($filename, 5);
		$firstname=$firstName;
		$lastname=$lastName;
		$path_user = str_replace($random, '', $path);		
		$pieces_1 = explode("/", $downloaded_file_name);
		$file=end($pieces_1);
		$server_dest =$protocol."://".$server.":".$port;
//$path_completo=$server_dest.$pref;
		$path_completo=$server_dest.$pref.$dir_selected.$file;
		error_log("path_completo is $path_completo");
		$path_mail=$server_dest.$pref;
		$user_dir="/opt/dm/ajp/data/personal/".$user_id."/";
	    $file_lock=$user_home_dir_big_transfer.'/lock';   	
		exec("sudo -u tomcat X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server /usr/bin/lcg-lr $lfn_file", $replica_values2);
		shuffle($replica_values2);		
		foreach ($replica_values2 as $element2)
		{
			if($error=="true"){
				$size1=$selectedFile_size[$x];
				exec("sudo -u tomcat X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server LCG_GFAL_INFOSYS=$lcg_gfal_infosys  lcg-cp --sendreceive-timeout 4 --vo $vo_active $element2 $filename_rand > /dev/null &");
				usleep(3800000);
		        if (file_exists($downloaded_file_name_rand)){                
//	                $command="sudo -u tomcat X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server LCG_GFAL_INFOSYS=$lcg_gfal_infosys  /usr/bin/lcg-cp --sendreceive-timeout 10 --connect-timeout 300 --bdii-timeout 300 --srm-timeout 300 --vo $vo_active $element2  $filename ";
//	                error_log($command);
					if ($size1<524288000) {
						$srtimeout=300;
					} else if($size1>=524288000 && $size1<1610612736){
						$srtimeout=600;
					} else if($size1>=1610612736 && $size1<16106127360){
						$srtimeout=1200;
					} else {
						$srtimeout=3600;
					}
				    exec("sudo -u tomcat X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server LCG_GFAL_INFOSYS=$lcg_gfal_infosys  /usr/bin/lcg-cp --sendreceive-timeout $srtimeout --connect-timeout 300 --bdii-timeout 300 --srm-timeout 300 --vo $vo_active $element2  $filename ", $download_values);	
		        	$size2=filesize($downloaded_file_name);
		        	error_log("size1 is $size1 and size2 is $size2");
		        	if($size2==$size1){
			        	$error='false';	
		        	}
		        unlink($downloaded_file_name_rand);	
	        	} else {
		        	$error='true';	
	        	}		        
	        }	
	    }    		        	
		if(!file_exists($downloaded_file_name)||$error!='false'){
			$fd = fopen($user_home_dir_big_transfer_grid_error, 'a');
			fwrite($fd, $dir_selected.$file_name.PHP_EOL);
			fclose($fd);
		} else {
			if($protocol=='ftp'|| $protocol=='http'){	
				$ch = curl_init();
				$fp = fopen($downloaded_file_name, 'r');
error_log("path_completo is $path_completo");
				curl_setopt($ch, CURLOPT_URL, $path_completo);
				curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
				curl_setopt($ch, CURLOPT_UPLOAD, 1);
				curl_setopt($ch, CURLOPT_INFILE, $fp);
				curl_setopt($ch, CURLOPT_INFILESIZE, filesize($downloaded_file_name));
				curl_exec ($ch);
				$error_no = curl_errno($ch);
				curl_close ($ch);
				if ($error_no == 0) {
					$fd = fopen($path."/ok", 'a');
					fwrite($fd, $dir_selected.$file.PHP_EOL);
					fclose($fd);
				} else {
					$fd = fopen($path."/error", 'a');
					fwrite($fd, $dir_selected.$file.PHP_EOL);
					fclose($fd);
				}
			} else {
				$filepath=$pref.$dir_selected.$file;
				$connection = ssh2_connect("$server", "$port");
				ssh2_auth_password($connection, "$username", "$password");
				$error_no = ssh2_scp_send($connection, "$downloaded_file_name", "$filepath", 0644);
				if ($error_no) {
					$fd = fopen($path."/ok", 'a');
					fwrite($fd, $dir_selected.$file.PHP_EOL);
					fclose($fd);
				} else {
					$fd = fopen($path."/error", 'a');
					fwrite($fd, $dir_selected.$file.PHP_EOL);
					fclose($fd);
				}
			}		
		}
		unlink($downloaded_file_name);						
		if ($handle = opendir($user_dir)) {
		    while (false !== ($entry = readdir($handle))) {
		        if ($entry != "." && $entry != "..") {
		        }
		    }
		    closedir($handle);
		}    
		$user_home_dir_big_transfer_iesimo=$user_home_dir_big_transfer.'/file_'.$iesimo.'.zzz';
		touch("$user_home_dir_big_transfer_iesimo");
		$occ = count(glob($user_home_dir_big_transfer.'/*.zzz'));
		error_log("occ is $occ");
		$iesimo++;
		$x++;
		error_log("iesimo is $iesimo");
	}	
		
	if($occ==($numElements)){			
		if(unlink($file_lock)){
			error_log('finitoooooooo1');
			$filePath = "$path/myPdf.pdf"; 
			$pdf = new FPDF();
			$pdf->AddPage();
			
			$lines_ok = file($path."/ok", FILE_IGNORE_NEW_LINES);
			sort($lines_ok);
			$str_error_ok = "";
			$ok_i=0;
			foreach($lines_ok as $line_ok){
				if ($ok_i==0) {
					$pdf->SetTextColor(0,170,0);
					$pdf->SetFont('Arial','B',14);
					$pdf->Cell(40,10,'Files correctly copied!');
					$pdf->Ln();
				}
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','B',11);
				$pdf->Cell(40,10,$line_ok);
				$pdf->Ln(6);
				$str_error_ok.=$line_ok."<br />";
				$ok_i++;				
				}		
			if ($str_error_ok=="") {
				$display_ok="none";
			} else {
				$display_ok="block";
			}
			$lines_grid = file($path."/grid_error", FILE_IGNORE_NEW_LINES);
			sort($lines_grid);
			$str_error_grid="";
			$grid_err_i=0;
			foreach($lines_grid as $line_grid){
				if ($grid_err_i==0) {
					$pdf->Ln(16);
					$pdf->SetTextColor(255,0,0);
					$pdf->SetFont('Arial','B',14);
					$pdf->Cell(40,10,'Files not copied for Grid error reasons!');
					$pdf->Ln();
				}
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','B',11);
				$pdf->Cell(40,10,$line_grid);
				$pdf->Ln(6);
				$str_error_grid.=$line_grid."<br />";
				$grid_err_i++;				
				}
			if ($str_error_grid=="") {
				$display_grid="none";
			} else {
				$display_grid="block";
			}						
			$lines_error = file($path."/error", FILE_IGNORE_NEW_LINES);
			sort($lines_error);
			$str_error_other="";
			$other_err_i=0;
			foreach($lines_error as $line_error){
				if ($other_err_i==0) {
					$pdf->Ln(16);
					$pdf->SetTextColor(255,128,0);
					$pdf->SetFont('Arial','B',14);
					$pdf->Cell(40,10,'Files not copied for other error reasons!');
					$pdf->Ln();
				}
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('Arial','B',11);
				$pdf->Cell(40,10,$line_error);
				$pdf->Ln(6);
				$str_error_other.=$line_error."<br />";
				$other_err_i++;				
				}		
			if ($str_error_other=="") {
				$display_other="none";
			} else {
				$display_other="block";
			}				
			$pdf->Output($filePath,'F');
			$to = $mail;
			$subject = 'Grid file transfer completed'; 
			$from ='igi-portal@italiangrid.it'; 
			$ok_i_perc=round(($ok_i/($numElements))*100, 1);
			$grid_err_i_perc=round(($grid_err_i/($numElements))*100, 1);
			$other_err_i_perc=round(($other_err_i/($numElements))*100, 1);
			if($numElements<20){				
				$body ="
				<html><head></head>
					<body>
					<div style=background-color:white;margin-left:auto;margin-right:auto;margin-top:10px;margin-top:15px;margin-bottom:15px;>
							<div style=padding-left:10px;padding-top:10px;><a href='https://portal.italiangrid.it'><img src='http://gridlab07.cnaf.infn.it:8090/images/logo.png' width='80px' height='40px' alt='' /></a></div> 
							<div style=margin-top:10px;margin-bottom:10px;color:black;padding-left:10px;padding-right:10px;>
								<p>Dear $firstName $lastName,</br></br> this is the summary of the file transfer from Grid to the specified server: <b>$path_mail</b> as user <b>$username</b>.<br/> 
								Transaction ID: $random.<br/>
								<p/>
							</div>
							<div style=background-color:#eee;width:80%;margin:auto;padding-left:20px;border-color:#ccc;border-style:solid;border-width:2px;margin-bottom:10px;border-radius:10px;display:$display_ok>
							    <p style=color:green;font-weight:bold;>$ok_i files correctly copied on your server ($ok_i_perc%):</p>        
						        <p style=margin-left:20px;>$str_error_ok</p> 
						     </div>
						     <div style=background-color:#eee;width:80%;margin:auto;padding-left:20px;border-color:#ccc;border-style:solid;border-width:2px;margin-top:5px;border-radius:10px;display:$display_grid>   
						        <p style=color:red;font-weight:bold;>$grid_err_i files not copied for Grid error reasons ($grid_err_i_perc%):</p>
						        <p style=margin-left:20px;>$str_error_grid</p> 
						     </div>   
						     <div style=background-color:#eee;width:80%;margin:auto;padding-left:20px;border-color:#ccc;border-style:solid;border-width:2px;margin-top:10px;border-radius:10px;display:$display_other>      
						        <p style=color:red;font-weight:bold;>$other_err_i files not copied for errors in server communication ($other_err_i_perc%):</p>
						        <p style=margin-left:20px;>$str_error_other</p> 
						    </div>
						    <div style=margin-top:20px;margin-bottom:20px;color:black;padding-left:10px;padding-right:10px;>
						        Need assistance? Replay this mail to <a tabindex='120' href='mailto:igi-portal-admin@lists.italiangrid.it'>Administrators</a>.
						    </div>
					</div>
				</body>
			</html>
			"; 	
			} else {									
				$body ="
				<html><head></head>
					<body>
						<div style=background-color:white;margin-left:auto;margin-right:auto;margin-top:10px;border-bottom:solid 5px #A3A3A3;margin-top:15px;margin-bottom:15px;>
							<div style=padding-left:10px;padding-top:10px;><a href='https://portal.italiangrid.it'><img src='http://gridlab07.cnaf.infn.it:8090/images/logo.png' width='80px' height='40px' alt='' /></a></div> 
							<div style=margin-top:10px;margin-bottom:10px;color:black;padding-left:10px;padding-right:10px;>
								<p>Dear $firstName $lastName,</br></br> this is the summary of the file transfer from Grid to the specified server: <b>$path_mail</b> as user <b>$username</b>.<br/> 
								Transaction ID: $random.<br/>
								<p/>
							</div>
							<div style=background-color:#eee;width:80%;margin:auto;padding:20px;border-color:#ccc;border-style:solid;border-width:2px;margin-bottom:10px;border-radius:10px;>
								<div style=color:black;font-weight:bold;>On $numElements files selected:</div> 
								
							    <div style=color:green;font-weight:bold;margin-left:20px;margin-top:10px;display:$display_ok;>$ok_i files correctly copied on your server ($ok_i_perc%)</div>        
	
							    <div style=color:red;font-weight:bold;margin-left:20px;margin-top:10px;display:$display_grid;>$grid_err_i files not copied for Grid error reasons ($grid_err_i_perc%)</div>
						     						        
							    <div style=color:orange;font-weight:bold;margin-left:20px;margin-top:10px;display:$display_other;>$other_err_i files not copied for errors in server communication ($other_err_i_perc%)</div>
						    
						    </div>
						    <div style=margin-top:20px;margin-bottom:20px;color:black;padding-left:10px;padding-right:10px;>
						    	In attachment the transactions report.<br/><br/>
						        Need assistance? Replay this mail to <a tabindex='120' href='mailto:igi-portal-admin@lists.italiangrid.it'>Administrators</a>.
						    </div>
						</div>
					</body>
				</html>
				"; 				        
			}

			$email = new PHPMailer();
			$email->IsSMTP(); // telling the class to use SMTP
			$email->Host       = "postino.cnaf.infn.it"; // SMTP server
			$email->SMTPDebug  = 2;				
			$email->From      = $from;
			$email->FromName = 'IGI Portal';
			$email->Subject   = $subject;
			$email->Body      = $body;
			$email->IsHTML(true);
			if($to=="marco.bencivenni@cnaf.infn.it"){
				$to="marco.bencivenni@gmail.com";
			}
			$email->AddAddress($to);
			if($numElements>=20){
				$email->AddAttachment( $filePath , 'report.pdf' );
			}
   			if($email->Send()){
					 deleteDir($path);
					 error_log("finitoooooo2");	
			}	 else {
				$error_mail = $mail->ErrorInfo;
				error_log("error is $error_mail");
			}
		}	
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
	            unlink($file);
	        }
	    }
	    rmdir($dirPath);
	}

?>
