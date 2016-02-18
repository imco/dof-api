<?php

namespace IMCO\CatalogoNOMsApi\Middleware;

use Closure;

class GoogleAnalyticsMiddleware
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response){
        $s = strtoupper(md5($_SERVER['REMOTE_ADDR'])); 
	    $guidText = 
	        substr($s,0,8) . '-' . 
	        substr($s,8,4) . '-' . 
	        substr($s,12,4). '-' . 
	        substr($s,16,4). '-' . 
	        substr($s,20); 

	    //var_dump($guidText);

		$url = 'http://www.google-analytics.com/collect';
		$data = array(
			'v' => '1',
			'tid' => env('TRACKINGCODE', 'UA-XXXXX-1'),
			'cid'=>$guidText,
			't'=>'pageview',
			'dl' => "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

		// use key 'http' even if you send the request to https://...
		$options = array(
		    'http' => array(
		        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		        'method'  => 'POST',
		        'content' => http_build_query($data),
		    ),
		);

		//var_dump($data);
		$context  = stream_context_create($options);
		$result = file_get_contents($url, false, $context);

		if ($result==FALSE){return -1;}

		//var_dump($result);

    }
}