
var repo_code="e0ea3d10879f74dd9da65af0b375557a";

function check_protocol(id) {
	var e = document.getElementById("download1");
	var protocol_selected = e.options[e.selectedIndex].value;
	if (protocol_selected=="ftp") {
		document.getElementById("download5").placeholder = "Es: /folder";
	} else if (protocol_selected=="sftp"){
		document.getElementById("download5").placeholder = "Es: /home/user/folder";
	} else if (protocol_selected=="http"){
		document.getElementById("download5").placeholder = "Es: /folder";
	}
}

function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}

function getURLParameter(name) {
  return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null
}

function setCookie(c_name,value,exdays){
	var exdate=new Date();
	exdate.setDate(exdate.getDate() + exdays);
	var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
	document.cookie=c_name + "=" + c_value;
}


function SetCookie(cookieName,cookieValue,nDays) {
 var today = new Date();
 var expire = new Date();
 if (nDays==null || nDays==0) nDays=1;
 expire.setTime(today.getTime() + 3600000*24*nDays);
 document.cookie = cookieName+"="+escape(cookieValue)
                 + ";expires="+expire.toGMTString()+";secure";
}


function delete_cookie(name){
    document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}


function getCookieVal (offset) {
	var endstr = document.cookie.indexOf (";", offset);
	if (endstr == -1) { 
		endstr = document.cookie.length; 
	}
	return unescape(document.cookie.substring(offset, endstr));
 }


function GetCookie (name) {
  var arg = name + "=";
  var alen = arg.length;
  var clen = document.cookie.length;
  var i = 0;
  while (i < clen) {
    var j = i + alen;
    if (document.cookie.substring(i, j) == arg) {
      return getCookieVal (j);
      }
    i = document.cookie.indexOf(" ", i) + 1;
    if (i == 0) break; 
    }
  return null;
  }


function hasClass(ele,cls) {
	return ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)'));
}


function showHideVOChooser(){
//	var req = new XMLHttpRequest();
//req.open('GET', document.location, false);
//req.send(null);
//var headers = req.getAllResponseHeaders().toLowerCase();
//alert(headers);
alert(getURLParameter('repository_id'));
	if (typeof(getURLParameter('repository_id')) != "undefined" && getURLParameter('repository_id') !== null){
		var ele1 = document.getElementById("vo_chooser_form");	
	        var cookies = GetCookie("vo_cookie");
		        var n=cookies.match(/---/g);
	                if (n != null && getURLParameter('repository_id')==repo_code){
	                        ele1.style.display = "block";
	                } else {
	                        ele1.style.display = "none";
	                }
	}
}

function showHideVOChooser2(){
}


function listCookies() {
    var theCookies = document.cookie.split(';');
    var aString = '';
    for (var i = 1 ; i <= theCookies.length; i++) {
        aString += i + ' ' + theCookies[i-1] + "\n";
    }
    return aString;
}


function showHideRepoChooser(){
		var ele2 = document.getElementById("global_toolbar");    			      
		
		if (GetCookie("is_admin")=="true") {
			ele2.style.display = "block";
		} else {
			ele2.style.display = "none";
		}
		
} 
