<?php
//added by gll 20140924
class Ctrtc{

const ACC_INNER = 10;
const ACC_ESURF = 11;
const ACC_WEIBO = 12;
const ACC_QQ = 13;

public $appid;
public $appkey;
public $restServer="rest.chinartc.com:8090";

private static $_instance;

function __construct($id,$key){
	$this->appid = $id;
	$this->appkey = $key;
}

public static function getInstance($id,$key){
	if(!(self::$_instance instanceof self)){
		self::$_instance = new self($id,$key);
	}
	return self::$_instance;
}

public function generateToken($pid,$username,$terminalType){
	$APIversion = "0.1";
	$authType_Token = 0;
	$appAccountID_Token  = $pid."-".$username;
	$userTerminalType_Token = $terminalType;
	$userTerminalSN_Token = time();
	$grantedCapabilityID_Token = "100<200<301<302<303<400";
	$callbackURL_Token = "www.baidu.com";
	$url_Token = "http://".$this->restServer."/RTC/ws/".$APIversion."/ApplicationID/".$this->appid."/CapabilityToken";
	$headers = "Content-Type: application/json\r\n";
	$headers .= "authorization: RTCAUTH,realm=AppServer,ApplicationId=".$this->appid.",APP_Key=".$this->appkey;
	$postData_Token_arr['authType'] = $authType_Token;
	$postData_Token_arr['appAccountID'] = $appAccountID_Token;
	$postData_Token_arr['userTerminalType'] = $userTerminalType_Token;
	$postData_Token_arr['userTerminalSN'] = $userTerminalSN_Token;
	$postData_Token_arr['grantedCapabiltyID'] = $grantedCapabilityID_Token;
	$postData_Token_arr['callbackURL'] = $callbackURL_Token;
	$postData_Token_json = json_encode($postData_Token_arr);
	$header_Servers = array(
	'http'=>array(
		'header'=>$headers,
		'timeout'=>6
		),
	);
	$result_Token = do_post_request($url_Token,$postData_Token_json,$headers);
	$token_obj = json_decode($result_Token);
	$web_sip_password = $token_obj->capabilityToken;
	
	$token_back =  $web_sip_password."|".$username."|".$pid."|".$this->appid."|".$userTerminalSN_Token."|".$userTerminalType_Token;
	//echo $token_back;
	$base64_token_back = base64_encode($token_back);
	return $base64_token_back;
}

}
	function do_post_request($url,$data,$optional_headers = null)
	{
		$params = array('http'=>array(
				'method'=>'POST',
				'content'=>$data
				)
				);

		if($optional_headers !== null){
			$params['http']['header'] = $optional_headers;
		}
		$ctx = stream_context_create($params);
		$fp = fopen($url,'rb',false,$ctx);
		if(!$fp){
			throw new Exception("Problem with $url,$php_errormsg");
		}
		$response = @stream_get_contents($fp);
		if($response===false){
			throw new Exception("Problem reading data from $url,$php_errormsg");
		}
		return $response;
	}
?>
