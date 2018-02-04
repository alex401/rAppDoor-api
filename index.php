<?php
require 'vendor/autoload.php';

//******************************
// Init
//-----------------
//
// init variables & app
//
//******************************

$app = new Slim\App();

//******************************
// Handling CORS
//-----------------
//
// Access-Control-Allow-Methods /GET/POST.
//
//******************************

$app->add(function($request, $response, $next) {
    $route = $request->getAttribute("route");

    $methods = [];

    if (!empty($route)) {
        $pattern = $route->getPattern();

        foreach ($this->router->getRoutes() as $route) {
            if ($pattern === $route->getPattern()) {
                $methods = array_merge_recursive($methods, $route->getMethods());
            }
        }
        //Methods holds all of the HTTP Verbs that a particular route handles.
    } else {
        $methods[] = $request->getMethod();
    }

    $response = $next($request, $response);


    return $response->withHeader("Access-Control-Allow-Methods", implode(",", $methods));
});

//******************************
// get app list
//-----------------
//
// get the list of a controller
//
//******************************

$app->get('/v1/applications', function ($request,$response) {

   try{

    // call with 10 minutes cache to FS API side
     $result = getContent('application-pwc.txt','http://pwc.loc:8090/controller/rest/applications?output=json','10');
    // call direct to API
    // $result = getUrl("http://pwc.loc:8090/controller/rest/applications?output=json");

       if($result){
        return $result;
        }else{
         return $response->withJson(array('status' => 'Something wrong happened'),422);
       }
   }
   catch(\Exception $ex){
       return $response->withJson(array('error' => $ex->getMessage()),422);
   }

});


// Utils

//caching on file system
function getContent($file,$url,$minutes = 10) {
	//vars
  $file = 'cache/'.$file;
	$current_time = time();
  $expire_time = $minutes*60;
  $file_time = filemtime($file);
	//decisions, decisions
	if(file_exists($file) && ($current_time - $expire_time < $file_time)) {
		// echo 'returning from cached file';
		return file_get_contents($file);
	}
	else {
		$content = getUrl($url);
		//$content.= '<!-- cached:  '.time().'-->';
		file_put_contents($file,$content);
	//	echo 'retrieved fresh from '.$url.':: '.$content;
		return $content;
	}
}


/* gets json from a controller api : $url via curl */
function getUrl($url) {
  $curl = curl_init();
  curl_setopt ($curl, CURLOPT_URL,$url);
  curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt ($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
  curl_setopt ($curl, CURLOPT_USERPWD, "alex@customer1:passwd");

  $result = curl_exec ($curl);
  curl_close ($curl);
  return $result;
}


$app->run();
