<?php

require_once (AJXP_INSTALL_PATH.'/plugins/access.dropbox/dropbox-php/autoload.php');
require_once (AJXP_INSTALL_PATH.'/server/classes/interface.AjxpWrapper.php');

class dropboxWrapper implements AjxpWrapper {
	
	/**
	 * 
	 * @var Dropbox_API
	 */
	private static $dropbox;
	private static $oauth;
	
	private static $crtDirContent = array();
	private static $crtDirIndex = 0;

	private static $crtHandle;
	private static $crtTmpFile;
	private static $crtWritePath;
	
	function __construct() {
	}
	
	public function initPath($ajxpPath){
		if(empty(self::$dropbox)){
			$repo = ConfService::getRepository();
			$consumerKey = $repo->getOption('CONSUMER_KEY');
			$consumerSecret = $repo->getOption('CONSUMER_SECRET');
			$email = $repo->getOption('USER');
			$pass = $repo->getOption("PASS");
			
			self::$oauth = new Dropbox_OAuth_PEAR($consumerKey, $consumerSecret);
			self::$dropbox = new Dropbox_API(self::$oauth);
			$tokens = self::$dropbox->getToken($email, $pass); 
			self::$oauth->setToken($tokens);			
		}
		$path = parse_url($ajxpPath, PHP_URL_PATH);
		if($path == "") return "/";
		return $path;
	}
	
	static function staticInitPath($ajxpPath){
		$tmpObject = new dropboxWrapper();
		return $tmpObject->initPath($ajxpPath);
	}

	protected function metadataToStat($metaEntry){
		AJXP_Logger::debug("Stating ", $metaEntry);
		$mode = 0666;
		if(intval($metaEntry["is_dir"]) == 1) $mode += 0040000;
		else $mode += 0100000;
		$time = strtotime($metaEntry["modified"]);
		$size = intval($metaEntry["bytes"]);
		$keys = array(
			'dev' => 0, 
			'ino' => 0, 
			'mode' => $mode, 
			'nlink' => 0, 
			'uid' => 0, 
			'gid' => 0, 
			'rdev' => 0, 
			'size' => $size, 
			'atime' => $time, 
			'mtime' => $time, 
			'ctime' => $time,
			'blksize' => 0, 
			'blocks' => 0 
		);		
		AJXP_Logger::debug("Stat value", $keys);
		return $keys;
	}
	
	static public function copyFileInStream($path, $stream) {
		$path = self::staticInitPath($path);
		$data = self::$dropbox->getFile($path);
		fwrite($stream, $data, strlen($data));		
	}
	
	static public function isRemote(){
		return true;
	}

	static public function getRealFSReference($path) {
		$tmpFile = AJXP_TMP_DIR."/".rand();
		$path = self::staticInitPath($path);
		file_put_contents($tmpFile, self::$dropbox->getFile($path));
		return $tmpFile;
	}

	static public function changeMode($path, $chmodValue) {
	
	}
	
	
	public function rename($path_from, $path_to) {
		$path1 = $this->initPath($path_from);
		$path2 = $this->initPath($path_to);
		AJXP_Logger::debug("FROM > TO ".$path_from."-".$path_to);
		AJXP_Logger::debug("1 > 2 ".$path1."-".$path2);
		self::$dropbox->copy($path1, $path2);
		self::$dropbox->delete($path1);
	}
	
	public function mkdir($path, $mode, $options) {
		$path = $this->initPath($path);
		self::$dropbox->createFolder($path);				
	}
	
	public function rmdir($path, $options) {
		$path = $this->initPath($path);
		self::$dropbox->delete($path);		
	}
	
	public function unlink($path) {
		$path = $this->initPath($path);
		self::$dropbox->delete($path);
	}
	
	public function url_stat($path, $flags) {
		AJXP_Logger::debug("STATING $path");
		$path = $this->initPath($path);
		$meta = null;
		if(self::$crtDirContent != null){
			foreach (self::$crtDirContent as $metaEntry){
				if($metaEntry["path"] == $path){
					$metaEntry = $meta;
					break;
				}
			}
		}
		if(empty($meta)){
			try{
				$meta = self::$dropbox->getMetaData($path);
			}catch(Dropbox_Exception_NotFound $nf){
				return false;
			}
		}				
		return $this->metadataToStat($meta);
	}
	
	public function dir_opendir($path, $options) {
		$path = $this->initPath($path);
		$metadata = self::$dropbox->getMetaData($path);
		AJXP_Logger::debug("CONTENT for $path", $metadata);
		self::$crtDirContent = $metadata["contents"];		
		return true;
	}
	
	public function dir_readdir() {
		//return false;
		if(self::$crtDirIndex == count(self::$crtDirContent)-1) return false;
		$meta = self::$crtDirContent[self::$crtDirIndex];
		self::$crtDirIndex ++;
		return basename($meta["path"]);
	}
	
	public function dir_rewinddir() {
		self::$crtDirIndex = 0;
	}
	
	public function dir_closedir() {
		self::$crtDirContent = array();
		self::$crtDirIndex = 0;
	}
	
	
	public function stream_flush() {
		return flush(self::$crtHandle);
	}
	
	public function stream_read($count) {
		return fread(self::$crtHandle, $count);	
	}
		
	public function stream_seek($offset, $whence = SEEK_SET) {
		return fseek(self::$crtHandle, $offset, $whence);
	}
	
	public function stream_write($data) {
		return fwrite(self::$crtHandle, $data);
	}
	
	public function stream_close() {
		$res = fclose(self::$crtHandle);
		if(self::$crtWritePath != null){
			$path = $this->initPath(self::$crtWritePath);
			try{
				$postRes = self::$dropbox->putFile($path, self::$crtTmpFile);
			}catch(Dropbox_Exception $dE){
				AJXP_Logger::debug("Post to $path failed :".$dE->getMessage());
			}

		}
		unlink(self::$crtTmpFile);
		return $res;	
	}
	
	public function stream_tell() {
		return ftell(self::$crtHandle);
	}
		
	public function stream_eof() {
		return feof(self::$crtHandle);
	}
	
	public function stream_stat() {
		return true;
	}
	
	public function stream_open($path, $mode, $options, &$opened_path) {		
		if(strstr($mode, "r") !== false){
			self::$crtTmpFile = self::getRealFSReference($path);
			self::$crtWritePath = null;
		}else{
			self::$crtTmpFile = AJXP_TMP_DIR."/".rand();
			self::$crtWritePath = $path;
		}
		self::$crtHandle = fopen(self::$crtTmpFile, $mode);
		return true;
	}	
}

?>