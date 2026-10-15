<?php
Class ForceDownload{
	public $filename;
	public $dir;
	public $path;
	public $download_name;
	public $size;
	public $time_file;
	public $mime_type;
	public $extension;
	public $http_reponse;
	
	public function __construct($dir, $filename){
		$dir = trim( (string) $dir );
		$this->dir 				= (string) $dir; 
		$this->filename 		= basename( (string) $filename );
		$this->path 			= $this->dir . $this->filename;	
		$this->download_name 	= $this->filename;
		$this->mime_type 		= 'application/octet-stream';
		// checking file
		$this->file_checked();
		}
		
	protected function file_checked(){
		if( $this->filename == '' ){
			$this->http_reponse = 406; // not acceptable
			return FALSE;
			}
		if( !is_file($this->path) ){
			$this->http_reponse = 404; // not found
			return FALSE;
			}
		else if( !is_readable($this->path) ){
			$this->http_reponse = 403; // forbidden
			return FALSE;
			}
			
		$info = pathinfo( $this->path );
		$this->extension = $info['extension'];
		$this->size = filesize($this->path);
		$this->time_file = date( 'r', filemtime( $this->path ) );
		$this->http_reponse = 200;
		return TRUE;
		}

	protected function headers_for_download(){
		header('Content-Type: ' . $this->mime_type);
		header('Content-Disposition: attachment; filename="'. $this->download_name .'"'); 
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . $this->size);
		header('Last-Modified: ' . $this->time_file);
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		return;
		}
		
	public function set_download_name($name){
		$this->download_name = preg_replace( 
									array("/\s+/", "/[^-\.\w]+/"), 
									array("_", ""), 
									trim($name));
		}
	
	public function download(){ 
		if( $this->http_reponse === 200 ){
			$this->headers_for_download(); 
			
			/*end headers*/
			if( !@readfile( $this->path ) ){
				$this->http_reponse = 500; // internal server error
				return FALSE;
				}
			return TRUE;
			}
		return FALSE;
		}
		
	public function get_error(){
		switch($this->http_reponse){
			case 200: $error = null; break;
			case 406: $error = 'Undefined file'; break;
			case 404: $error = 'File not found'; break;
			case 403: $error = 'Access denied'; break;
			default: $error = 'Unknown error'; break;
			}
		return $error;
		}
	}
