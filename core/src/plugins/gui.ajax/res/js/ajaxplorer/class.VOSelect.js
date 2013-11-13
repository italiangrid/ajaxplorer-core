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

/**
 * A selector for displaying repository list. Will hook to ajaxplorer:repository_list_refreshed.
 */
Class.create("VOSelect", {
	__implements : "IAjxpWidget",
	_defaultString:' No VO',
	_defaultIcon : 'users-icon.png',
	/**
	 * Constructor
	 * @param oElement HTMLElement Anchor
	 */
	initialize : function(oElement){
		this.element = oElement;
		this.element.ajxpPaneObject = this;
		this.show = true;
		this.createGui();
	},
			 	

generateVOSelect : function() {
	var BrowserDetect = {
	init: function () {
		this.browser = this.searchString(this.dataBrowser) || "An unknown browser";
		this.version = this.searchVersion(navigator.userAgent)
			|| this.searchVersion(navigator.appVersion)
			|| "an unknown version";
		this.OS = this.searchString(this.dataOS) || "an unknown OS";
	},
	searchString: function (data) {
		for (var i=0;i<data.length;i++)	{
			var dataString = data[i].string;
			var dataProp = data[i].prop;
			this.versionSearchString = data[i].versionSearch || data[i].identity;
			if (dataString) {
				if (dataString.indexOf(data[i].subString) != -1)
					return data[i].identity;
			}
			else if (dataProp)
				return data[i].identity;
		}
	},
	searchVersion: function (dataString) {
		var index = dataString.indexOf(this.versionSearchString);
		if (index == -1) return;
		return parseFloat(dataString.substring(index+this.versionSearchString.length+1));
	},
	dataBrowser: [
		{
			string: navigator.userAgent,
			subString: "Chrome",
			identity: "Chrome"
		},
		{ 	string: navigator.userAgent,
			subString: "OmniWeb",
			versionSearch: "OmniWeb/",
			identity: "OmniWeb"
		},
		{
			string: navigator.vendor,
			subString: "Apple",
			identity: "Safari",
			versionSearch: "Version"
		},
		{
			prop: window.opera,
			identity: "Opera",
			versionSearch: "Version"
		},
		{
			string: navigator.vendor,
			subString: "iCab",
			identity: "iCab"
		},
		{
			string: navigator.vendor,
			subString: "KDE",
			identity: "Konqueror"
		},
		{
			string: navigator.userAgent,
			subString: "Firefox",
			identity: "Firefox"
		},
		{
			string: navigator.vendor,
			subString: "Camino",
			identity: "Camino"
		},
		{		// for newer Netscapes (6+)
			string: navigator.userAgent,
			subString: "Netscape",
			identity: "Netscape"
		},
		{
			string: navigator.userAgent,
			subString: "MSIE",
			identity: "Explorer",
			versionSearch: "MSIE"
		},
		{
			string: navigator.userAgent,
			subString: "Gecko",
			identity: "Mozilla",
			versionSearch: "rv"
		},
		{ 		// for older Netscapes (4-)
			string: navigator.userAgent,
			subString: "Mozilla",
			identity: "Netscape",
			versionSearch: "Mozilla"
		}
	],
	dataOS : [
		{
			string: navigator.platform,
			subString: "Win",
			identity: "Windows"
		},
		{
			string: navigator.platform,
			subString: "Mac",
			identity: "Mac"
		},
		{
			   string: navigator.userAgent,
			   subString: "iPhone",
			   identity: "iPhone/iPod"
	    },
		{
			string: navigator.platform,
			subString: "Linux",
			identity: "Linux"
		}
	]

};
	
	BrowserDetect.init();
	
//	var repo = ajaxplorer.user.getActiveRepository();
//if(repo==repo_code){
	var allcookies = document.cookie;
	cookiearray  = allcookies.split('; ');
    for(var i=0; i<cookiearray.length; i++){
    	name = cookiearray[i].split('=')[0];
        value = cookiearray[i].split('=')[1];
		if (name=="vo_cookie") {
			vo_cookie_array  = value.split('---');
			for(var y=0; y<vo_cookie_array.length; y++){
			vo = vo_cookie_array[y];	
				if (vo!="") {	
					if(BrowserDetect.browser=="Chrome"||BrowserDetect.browser=="Safari"){
						document.getElementById('vo_chooser').innerHTML+='<option style="padding-right:20px;" value="'+vo+'">VO: '+vo+'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>';
		
					}  else if(BrowserDetect.browser=="Explorer"){
						document.getElementById('vo_chooser').innerHTML+='<option style="padding-right:20px;" value="'+vo+'">VO: '+vo+'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>';		
						document.getElementById('vo_chooser').style.paddingTop="6px";
						document.getElementById('patch').style.marginLeft="-61px";	
						document.getElementById('patch').style.width="19px";
					} else {		
						document.getElementById('vo_chooser').innerHTML+='<option style="padding-right:20px; " value="'+vo+'">VO: '+vo+'&nbsp;</option>';
						document.getElementById('vo_chooser').style.paddingTop="8px";
					} 		
				}			
			}
		}
	}

	
	Event.observe('vo_chooser', 'change', function(event) {
    var e = document.getElementById('vo_chooser');
    var cookie_value = e.options[e.selectedIndex].value;		    
    var vo_cookie = GetCookie('vo_cookie');
    vo_cookie=cookie_value+"---"+vo_cookie.replace("---"+cookie_value,"");
    document.cookie = "vo_cookie="+escape(vo_cookie)+";secure";
	document.cookie = "home="+escape('/grid/'+cookie_value)+";secure";
	document.cookie = "vo_changed="+escape('true')+";secure";
	delete_cookie('ajxp_wall');
	delete_cookie('ajxp_jsreload');
	var target='/';
	window.ajaxplorer.goTo(target);	
	window.ajaxplorer.fireContextRefresh();

});
//}
},
	
	
		/**
	 * Implementation of the IAjxpWidget methods
	 */	
	getDomNode : function(){
		return this.element;
	},
	
	/**
	 * Implementation of the IAjxpWidget methods
	 */	
	destroy : function(){
		this.element = null;
	},
	
	/**
	 * Resize widget
	 */
	resize : function(){
		var parent = this.element.getOffsetParent();
		if(parent.getWidth() < this.currentRepositoryLabel.getWidth()*3.5){
			this.showElement(false);
		}else{
			this.showElement(true);
		}
	},

		
	/**
	 * Creates the HTML
	 */
	createGui : function(){
		
		this.generateVOSelect();
		this.icon = new Element('img', {
			id:'repo_icon',
			src:resolveImageSource(this._defaultIcon,'/images/actions/ICON_SIZE', 16),
			width:16,
			height:16,
			align:'absmiddle'
		});
		this.label = new Element('input', {
			 type:"text", 
			 name:"VO", 
			 value:this._defaultString, 
			 id:"repo_path"
		});
		this.currentRepositoryLabel = new Element('div', {id:'repository_form'});
		this.currentRepositoryLabel.insert(this.icon);
		this.currentRepositoryLabel.insert(this.label);
		this.element.insert(this.currentRepositoryLabel);
		this.button = simpleButton(
			'repository_goto', 
			'inlineBarButton', 
			200, 
			200, 
			ajxpResourcesFolder + '/images/arrow_down.png', 
			16,
			'inline_hover', null, true);
		this.button.setStyle({marginRight:'7px'});		
		this.button.select('img')[0].setStyle({height:'6px', width:'10px', marginLeft:'1px', marginRight:'1px', marginTop:'8px'});
		this.element.insert(this.button);
		
	},
	
		/**
	 * Show/hide element
	 * @param show Boolean
	 */
		
	
	showElement : function(show){
		this.show = show;
		if(show){
			this.currentRepositoryLabel.show();
			if(this.repoMenu) this.repoMenu.options.leftOffset = -127;
		}
		else{
			this.currentRepositoryLabel.hide();
			if(this.repoMenu) this.repoMenu.options.leftOffset = 0;
		}
		if(!this.repoMenu){
			this.observeOnce("createMenu", function(){this.showElement(this.show);}.bind(this));
		}
	},
	/**
	 * Utilitary
	 * @returns Integer
	 */
	getActualWidth : function(){
		if(this.currentRepositoryLabel.visible()) return this.element.getWidth();
		else return this.button.getWidth() + 10;
	}
	

	});
