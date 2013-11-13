<?php

			error_log(print_r($argv,true));
			
			$lfn_file=$argv[1];
			$x509_user_proxy=$argv[2];
			$lfc_server=$argv[3];
			$se_dest=$argv[4];
			$lcg_gfal_infosys=$argv[5];
			$mail=$argv[6];
			$firstName=$argv[7];
			$lastName=$argv[8];
			$vo_active = $argv[9];
			$filename = $argv[10];
			
			exec("sudo X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server LCG_GFAL_INFOSYS=$lcg_gfal_infosys  /usr/bin/lcg-rep -d $se_dest $lfn_file", $output);
			exec("sudo X509_USER_PROXY=$x509_user_proxy LFC_HOST=$lfc_server LCG_GFAL_INFOSYS=$lcg_gfal_infosys  /usr/bin/lcg-lr $lfn_file", $replicas);
				
			$message['text']="Replica failed on ".$se_dest;
			$message['type']="fail";
			$display_ok="none";
			$display_fail="block";
			foreach ($replicas as $replica) {
	        	if (strstr($replica, $se_dest)!="") {
                	$message['text']="File successfully replicated on ".$se_dest;
                	$message['type']="success";
                	$display_ok="block";
                	$display_fail="none";
	        	}
	    	}	
										
			$to = "$mail";
			$subject = 'Grid file replica completed'; 
			$from ='igi-portal@italiangrid.it'; 
			$body ="
			<html><head></head>
				<body style=background-image:url('http://gridlab07.cnaf.infn.it:8090/images/overlay3.png');>
				<div style=width:90%;background-color:white;margin-left:auto;margin-right:auto;margin-top:10px;border-style:solid;border-width:5px;margin-top:15px;margin-bottom:15px;>
				<div style=padding-left:10px;padding-top:10px;><a href='https://portal.italiangrid.it'><img src='http://gridlab07.cnaf.infn.it:8090/images/logo.png' width='80px' height='40px' alt='' /></a></div> 
						<div style=margin-top:10px;margin-bottom:10px;color:black;padding-left:10px;padding-right:10px;>
							<p>Dear $firstName $lastName,</br></br><p/>
						</div>
						<div style=background-color:#eee;width:80%;margin:auto;padding-left:20px;border-color:#ccc;border-style:solid;border-width:2px;margin-bottom:10px;border-radius:10px;display:$display_ok>
						    <p style=color:green;font-weight:bold;>The file $filename has been correctly replicated on $se_dest</p>        
					    </div>
					    <div style=background-color:#eee;width:80%;margin:auto;padding-left:20px;border-color:#ccc;border-style:solid;border-width:2px;margin-top:5px;border-radius:10px;display:$display_fail>   
					        <p style=color:red;font-weight:bold;>The file $filename has not been correctly replicated on $se_dest</p>
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
			mail($to, $subject, $body, $header);	
								
								
?>
