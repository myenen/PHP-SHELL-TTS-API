<?php

class shellapi {
	public static $customercode    	= ""; 
	public static $userid    		= ""; 
	public static $password    		= ""; 
	public static $branchcode    	= ""; 
	public static $url				= "https://tts.turkiyeshell.com/TTS/TTSWebServices.asmx";
	public static $post				= "";


	public static function service($service,$param){
		
		self::$url	= self::$url."?op=".$service;
		self::$post	= self::xmlparameters($service,$param);
		$request 	= self::request();

		if (!is_array($request)) {
			return ["err" => $request];
		}
		
		$request 	= $request[$service."Response"][$service."Result"];
		if($request["PROCESSRESULT"]["Success"] == "false") {
			return ["err" => $request["PROCESSRESULT"]["Message"]];
		}
		
		return $request;
		
	}	
	
	

	public static function request()
	{

		$ch 		= curl_init();

		$header 	= array(
		"Content-Type: text/xml;charset=UTF-8"
		);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, self::$post);
		curl_setopt($ch, CURLOPT_URL, self::$url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);


		$result = curl_exec($ch);

		if (curl_errno($ch)) {
			$error_msg = curl_error($ch);
			return $error_msg;
		}

		$result = self::soaptoobject($result);

		if (!($result)) {
			return "Sorgu sonucu  Xml belgesi okunamadı!";
		}

		if (isset($result->Fault)) {
			return strval($result->Fault->Reason->Text);
		}
		return self::objecttoarray($result->children());

	}
	
	public static function soaptoobject($xml)
	{
		$xml = simplexml_load_string($xml);
		return ($xml->children('soap', true)->Body);
		
	}
	
	public static function objecttoarray($obj)
	{
		return json_decode(json_encode($obj), true);
	}	
	
	public static function getxmlfile($f) 
	{
		return file_get_contents(__DIR__."/shellxml/".$f.".txt");
	}
	
	public static function setxmlparam($xml,$param)
	{
		
		$arr = [];
		foreach ($param as $k=>$v) {
			$arr["{".$k."}"]	=	$v;			
		}		

		return strtr($xml,$arr);				
	}
	
	public static function xmlparameters($xml,$parameter)
	{
		$xml = self::getxmlfile($xml);
	
		preg_match_all('#\{(.*?)\}#',$xml,$param);
		$param =  array_fill_keys($param[1],"");

		foreach ($param as $k=>$v) {
			$param[$k] = isset(self::${$k}) ? self::${$k} : "";
		}
		$param = array_merge($param,$parameter);
		return self::setxmlparam($xml,$param);
	}
	
			
}

?>