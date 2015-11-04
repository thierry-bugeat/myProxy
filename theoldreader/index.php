<?php
/**
 * Copyright 2015 Thierry BUGEAT
 * 
 * This file is part of myProxy.
 * 
 * myProxy is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * myProxy is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with myProxy.  If not, see <http://www.gnu.org/licenses/>.
 */

session_start();
ob_start();

$url = (isset($_POST['url'])) ? $_POST['url'] : $_GET['url']; 
$$cookieFile = '/tmp/proxy-cookie-tor-'.session_id();
$myDomain = ($_SERVER['HTTPS'] == 'on') ? 'https://'.$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST'];

/* --- Cookie domain --- */

$urlParts = explode('/', preg_replace('/((http(s|):\/\/|)(www\.|))/', '', $url));
$cookieDomain = $urlParts[0];

/* --- Curl request --- */

$curlSession = curl_init();

curl_setopt($curlSession, CURLOPT_URL, $url);
curl_setopt($curlSession, CURLOPT_HEADER, 1);

/* --- Include client headers --- */

$_curlHeaders = array(
    'P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"',
    'Access-Control-Allow-Origin: *',
    'Access-Control-Allow-Headers: Authorization'
);

$_allHeaders = getallheaders();

if (isset($_allHeaders['Authorization'])) {
    $_curlHeaders[] = 'Authorization: '.$_allHeaders['Authorization'];
} else if (isset($_GET['myAuth'])) {
    $_curlHeaders[] = 'Authorization: GoogleLogin auth='.$_GET['myAuth'];
    unset($_GET['myAuth']);
}

/*foreach($_allHeaders as $_key => $_value) {
    $_curlHeaders[] = $_key.': '.$_value;
}*/

curl_setopt($curlSession, CURLOPT_HTTPHEADER, $_curlHeaders);

/* --- */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $postParams = "?";
	foreach ($_POST as $key => $value) { $postParams .= $key.'='.$value.'&'; }
    rtrim ($postParams, '&');

    curl_setopt($curlSession, CURLOPT_POST, count($_POST));
    curl_setopt($curlSession, CURLOPT_POSTFIELDS, $postParams);

} else if (isset($_GET['method']) && ($_GET['method'] == 'post')) {

    $curlParams = "?";
	foreach ($_GET as $key => $value) { $curlParams .= $key.'='.$value.'&'; }
    rtrim ($curlParams, '&');

    curl_setopt($curlSession, CURLOPT_POST, count($_GET));
    curl_setopt($curlSession, CURLOPT_POSTFIELDS, $curlParams);
} else {
    curl_setopt($curlSession, CURLOPT_HTTPGET, 1);
}

curl_setopt($curlSession, CURLOPT_RETURNTRANSFER,1);
curl_setopt($curlSession, CURLOPT_TIMEOUT,30);
curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 1);
//curl_setopt($curl_handle, CURLOPT_COOKIESESSION, true ); // Test : Ajouté par moi :)
curl_setopt($curlSession, CURLOPT_COOKIEJAR, $cookieFile); 
curl_setopt($curlSession, CURLOPT_COOKIEFILE, $cookieFile);

//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false); // Follow redirects

foreach ($_COOKIE as $_key => $_value) {
	if (is_array($_value)) {
		$_value = serialize($_value);
	}
	curl_setopt($curlSession, CURLOPT_COOKIE, "$_key=$_value; domain=.$cookieDomain ; path=/");
}

$response = curl_exec ($curlSession);
$redirectURL = curl_getinfo($response, CURLINFO_EFFECTIVE_URL);

/* --- Check connection --- */ 

if (curl_error($curlSession)){
    print curl_error($curlSession);
} else {
	$response = str_replace("HTTP/1.1 100 Continue\r\n\r\n", "", $response);

	list($_headerString, $_body) = explode("\r\n\r\n", $response, 2); 

    // --- Redirection 302 ? ---

    if (is_numeric(strpos($_headerString, ' 302 '))){
        $rows = explode("\n", $_headerString);
        foreach ($rows as $row) {
            list($key, $value) = explode(': ', $row, 2);
            $_headerStrings[$key] = $value;
        }
        var_dump($_headerStrings);
    }

	// handle headers - simply re-outputing them

    $_headers = split(chr(10), $_headerString); 
    //var_dump($_headers);
    _saveFile("/tmp/proxy-headers-".session_id().".txt", json_encode($_headers));
    $_location = '';
	foreach ($_headers as $key => $value) {
        $value = str_replace($url, $myDomain, $value); 
        header(trim($value));
	}

    // Rewrite all hard coded urls to ensure the links still work!
    
    $_body = str_replace($url, $myDomain, $_body);

    // test thierry replace urls
    
    //$regex = "/(\"|')(http|https|ftp|ftps)(\:\/\/[a-zA-Z0-9\-\.\/_?=&%,\+]+)(\"|')/";
    $regex = "/(http|https|ftp|ftps)(\:\/\/[a-zA-Z0-9\-\.\/_?=&%,\+]+)/";

    $text = preg_replace_callback($regex, _urlencode, $_body);

    if ($location != "") {
        print '<base href="'.$_location.'">';
    }
    print $_body;
    //print $text;
}

curl_close ($curlSession);

/* ================= */
/* --- Functions --- */
/* ================= */

function _urlencode($matches){
    return 'proxy.php?url='.urlencode($matches[1].$matches[2]);
}

function _urlencode2($matches){
    return $matches[1].'proxy.php?url='.urlencode($matches[2].$matches[3]).$matches[4];
}

function _saveFile($filename, $content) {
    $fh = fopen($filename, "w");
    fwrite($fh, $content);
    fclose($fh);
}

?>
