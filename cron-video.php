<?php
	//exec('export AWS_ACCESS_KEY=AKIAJN5GRK6GHR4B3C6Q');
	//exec('export AWS_SECRET_KEY=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');
	set_time_limit (18000);
	error_reporting(0);
	define('FTP_HOST', 'ftp01.nba.com');  
	define('FTP_USER', 'IPVG');  
	define('FTP_PASS', 'hp1vg2');  	
	//define('RSS_FILENAME','ipvg.rss');
	if(isset($_GET['rss'])){
		define('RSS_FILENAME',$_GET['rss']);
	}else{
		define('RSS_FILENAME','ipvg.rss');
	}	

//	define('USERNAME','cronuser');
//      define('PASSWORD','nb@p@$$');
        define('USERNAME','nbaawsuser');
        define('PASSWORD','p@ssw0rd_123');
        define('SERVER','nbadb.cgvo8mpef8im.ap-southeast-1.rds.amazonaws.com');
        define('DATABASE','db48516_nba');
	
	header( 'Content-type: text/html; charset=utf-8' );
	print "Start retrieving files \n\r";

	class FTPClient{
		private $connectionId;
		private $loginOk = false;
		private $messageArray = array();
		private function _logMessage($message){
			$this->messageArray[] = $message;
		}
		public function __construct(){

		}
		//============ public
		public function getMessage(){
			return $this->messageArray;
		}
		public function connect ($server, $ftpUser, $ftpPassword, $isPassive = false){  
		        // *** Set up basic connection  
	        
	        	$this->connectionId = ftp_connect($server);  
	        	// *** Login with username and password  
		        $loginResult = ftp_login($this->connectionId, $ftpUser, $ftpPassword);  
		        // *** Sets passive mode on/off (default off)  
		        ftp_pasv($this->connectionId, $isPassive);  
	        	// *** Check connection  
		        if ((!$this->connectionId) || (!$loginResult)) {  
			        $this->_logMessage('FTP connection has failed!');  
	            		$this->_logMessage('Attempted to connect to ' . $server . ' for user ' . $ftpUser, true);  
	            		return false;  
	        	} else {
				$this->_logMessage("Login result : " . $loginResult . "\r");
            			$this->_logMessage('Connected to ' . $server . ', for user ' . $ftpUser . "\r");  
            			$this->loginOk = true;  			
				return $this->loginOk; 	
	        	}  
	    	}  
	    	public function downloadRss($filename){
	    		if(file_exists("/var/www/html/nba/ftp-web/".$filename)){
	    			$date = @date("y_m_d_H_i");
	    			$part = explode(".",$filename);
	    			$new_name = $part[0].$date.".rss";
	    			@rename("/var/www/html/nba/ftp-web/".$filename, "/var/www/html/nba/ftp-web/".$new_name);
				print "rss renamed \r";
	    		}
	    	
	
		
	    		$ret = ftp_nb_get($this->connectionId, "/var/www/html/nba/ftp-web/".$filename, $filename, FTP_BINARY);

			if($ret != FTP_MOREDATA){
				var_dump($ret, $filename); return false;
			} 
		    	while($ret == FTP_MOREDATA){
		    		print ".";
	    			$ret = ftp_nb_continue($this->connectionId);
	    		}
		    	print "\n\r";
		    	if($ret != FTP_FINISHED){
				$this->messageArray = $ret;
    				return false;
	    		}else{
				exec('sudo aws s3 cp /var/www/html/nba/ftp-web/'.$filename.' s3://nbaphfiles/ftp-web/'.$filename.' --acl public-read');	
    				return true;
    			}
	    	}	   
		public function downloadVideoAndImage($filename){
	    		if(file_exists("/var/www/html/nba/ftp-web/".$filename)){
	    			print "Skiping download, file alredy exists: $filename.\n\r";
				//echo  "Skiping download, file alredy exists: $filename.<br />";	
		    		return true;
	    		}else{
	    			print "Downloading $filename\n\r";
		    		//flush();
		    		//ob_flush();
	    			$ret = @ftp_nb_get($this->connectionId, "/var/www/html/nba/ftp-web/".$filename, $filename, FTP_BINARY); 
	    			while($ret == FTP_MOREDATA){
	    				print ".";
	    				//flush();
	    				//ob_flush();
	    				$ret = ftp_nb_continue($this->connectionId);
	    			}
	    			//ob_get_clean();	    		
	    			if($ret != FTP_FINISHED){
	    				print "\n\rdownload failed\n\r";
					//echo "download failed <br/>";
		    			//flush();
		    			//ob_flush();
	    				return false;
	    			}else{
		    			print "\n\rdownload complete\n\r";	
					//echo "download complete <br />";
	    				//flush();
	    				//ob_flush();
					if(!strpos($filename,'.mov')){
						exec('sudo aws s3 cp /var/www/html/nba/ftp-web/'.$filename.' s3://nbaphfiles/ftp-web/'.$filename.'  --acl=public-read');
						 print "aws copy image \n";
					}else{
						exec('sudo aws s3 cp /var/www/html/nba/ftp-web/'.$filename.' s3://nbaphfiles/ftp-web/'.$filename.'  --acl=public-read --content-type=video/mp4');	
						 print "aws copy video \n";
					}
	    				return true;
		    		}
			}	    		
		}	   

		public function destroyFtp(){
	    		ftp_close($this->connectionId);
	    	}
	}

	class DB{
		private $_mysqli;
		public function __construct($server, $username, $password, $database){
			$this->_mysqli = new mysqli($server, $username, $password, $database);		
			if ($mysqli->connect_errno) {
    				printf("Connect failed: %s\n", $mysqli->connect_error);
    				die();
			}
		}		
		public function insert($data){						
			if($this->_seekDataIfExists($data['filename'])){								
				return $this->_updateConfirmed($data);
			}else{
				return $this->_insertConfirmed($data);;
			}
		}
		private function _seekDataIfExists($filename){
			$ret = $this->_mysqli->query("select id from new_videos where filename ='".$filename."'");			
			if($ret->num_rows > 0){
				$return = true;
			}else{
				$return = false;
			}
			//$ret->close();			
			return $return;
		}
		private function _updateConfirmed($data){
			try{
				$ret = $this->_mysqli->query("update new_videos set v_upload_complete=".$data['v_upload_complete'].",s_img_upload_complete=".$data['s_img_upload_complete'].",l_img_upload_complete=".$data['l_img_upload_complete'].";"); 
						
				return true;
			}catch(Exception $e){				
				return false;
			}
		}
		private function _insertConfirmed($data){			
			try{
				$filenameExpl = explode("_",$data['filename']);
				switch($filenameExpl[0]){
					case "recap13":
						$category = "HL"; //highlights
						break;
					case "aotn":
						$category = "TP"; //top plays
						break;
					case "botn":
						$category = "TP"; //top plays
						break;
					case "dotn":
						$category = "TP"; //top plays
						break;
					case "pod":
						$category = "TP"; //top plays
						break;
					case "sotn":
						$category = "TP"; //top plays
						break;
					case "zap":
						$category = "HL"; //highlights
						break;
					case "top10":
						$category = "EP"; //editor's pick
						break;	
					default:
						$category = "HL";
						break;
				}
				var_dump($data);
				$ret = $this->_mysqli->query("insert into new_videos(filename,date_created,description,season,format,content,title,media_game_date,home_team,away_team,duration,player,tags,small_image,large_image,promotional_text,categorization,v_upload_complete,s_img_upload_complete,l_img_upload_complete)". 
						"values ('".$data['filename']."','".date("Y-m-d H:i:s")."','".$this->_mysqli->real_escape_string($data['description'])."','".$data['season']."','".$data['source']."','".$data['content']."','".$this->_mysqli->real_escape_string($data['title'])."','".date("Y-m-d",strtotime($data['media_game_date']))."','".$data['home_team']."','".$data['away_team']."','".date("H:i:s",strtotime($data['duration']))."','".$this->_mysqli->real_escape_string($data['player'])."','".$data['tags']."','".$data['small_image']."','".$data['large_image']."','".$data['promotional_text']."','".$category."',".$data['v_upload_complete'].",".$data['s_img_upload_complete'].",".$data['l_img_upload_complete'].");");				
				return true;
			}catch(Exception $e){				
				return false;
			}
		}

	}
	// *** Create the FTP object  
	$ftpObj = new FTPClient();  
	// *** Connect  
	if(!$ftpObj->connect(FTP_HOST, FTP_USER, FTP_PASS, true)){
	
		var_dump($ftpObj->getMessage());
		die;
	}  

	//download new rss file from nba
	//$ftpObj->downloadRss(RSS_FILENAME);	
	if(!isset($_GET['rss'])){
		if($ftpObj->downloadRss(RSS_FILENAME)){		
			$file = file_get_contents("/var/www/html/nba/ftp-web/". RSS_FILENAME);	
		}else{
			var_dump($ftpObj->getMessage());
			die;
		}
	}else{	
		//clean the ampersand character		
		$file = file_get_contents("ftp-web/". RSS_FILENAME);
	}
	echo "<br />";	
	echo "retrieving from : " . dirname(__FILE__)."/ftp-web/".RSS_FILENAME."<br />";
	echo "<br />";

	$file = preg_replace('/&/', 'and', $file);


	//libxml_use_internal_errors(true);
	$xml = simplexml_load_string($file);	
	if($xml !== false)
	{
	    // Process XML structure here
	    $loopResult = array();
	    $db = new DB(SERVER, USERNAME, PASSWORD, DATABASE);

	    foreach($xml->channel->item as $item){	    	
	    	$loopResult["$item->title"] = array(
				"db" => $db->insert(array(
					'season' => $item->season,
		    		'source' => $item->source_format,
		    		'content' => $item->content,
		    		'title' => $item->title,
		    		'description' => $item->description,
		    		'filename' => $item->filename,
		    		'media_game_date' => $item->media_game_date,
		    		'home_team' => $item->home_team,
		    		'away_team' => $item->away_team,
		    		'duration' => $item->duration,
		    		'player' => $item->player,
		    		'tags' => $item->tags,
		    		'small_image' => $item->small_image_url,
		    		'large_image' => $item->large_image_url,
		    		'promotional_text' => $item->promotional_text,
		    		'v_upload_complete' => $ftpObj->downloadVideoAndImage($item->filename.".".strtolower($item->source_format)),
		    		's_img_upload_complete' => $ftpObj->downloadVideoAndImage($item->small_image_url),
		    		'l_img_upload_complete' => $ftpObj->downloadVideoAndImage($item->large_image_url)
		    		)
				)
			);
			
	    }//foreach	    
	    //print_r($loopResult);
	}else{
		print "Something went wrong! \n\r";
		libxml_use_internal_errors(true);	
	    	foreach(libxml_get_errors() as $error)
	    	{
	        	error_log('Error parsing XML file ' . $xml . ': ' . $error->message);
			print 'Error parsing XML file ' . $xml . ': ' . $error->message;
	    	}
	}

	$ftpObj->destroyFtp();
?>				

