<?php
require_once("./helper_functions.php");

#allowed request origin
define('ALLOWED_ORIGIN', $_SERVER['FRONTEND_ORIGIN']);

#path prefix (if there is a version number or anything before the actual request path)
#for example, if an API request url was https://example.com/api/endpoint, the path prefix would be "api"
define('PATH_PREFIX', 'api');

#path to error logs
define('ERROR_LOG_PATH', $_SERVER['DOCUMENT_ROOT'] . "/../logs");


header("Access-Control-Allow-Origin: " . ALLOWED_ORIGIN);
header("Access-Control-Allow-Credentials: true");

header("Cache-Control: max-age=0, no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");
header("X-Powered-By: a hamster running *really fast*");

#define the request path and length as constants
define('PATH_PARTS', split_path($_SERVER['REQUEST_URI']));
define('PATH_PARTS_LENGTH', count(PATH_PARTS));

#for options requests, we need to record all the options
$OPTIONS = [];

/* register an API endpoint */
// this function checks if the request matches the method and path of the endpoint, and if it does, it calls the function
function register(String $method, String $path, callable $function, bool $require_origin = true){
    global $OPTIONS;
    //Check access method
    if($_SERVER['REQUEST_METHOD'] !== strtoupper($method) && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') return;

    //check origin
    if($require_origin && isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] !== ALLOWED_ORIGIN){
        err(403, "You can't access this resource unless you're on the main site (".$_SERVER['ALLOWED_ORIGIN'].")");
    }

    //Check path
    //Variables are denoted with curly brackets {} and must be a full part of the path
    // e.g. users/{user_id}/path instead of users/{user_id}path or something crazy like that

    #split the endpoint's path into parts, just like the request path is split
    $path_parts = split_path($path);
    #check if the number of parts match (if not, this endpoint is not right)
    if(count($path_parts) !== PATH_PARTS_LENGTH) return;

    #check each path part to see if it matches
    $path_data = [];
    foreach($path_parts as $index=>$part){
        //this handles variables in the path (e.g. /users/{user_id})
        $variable_parts = explode("{", $part);
        if(count($variable_parts) === 2){
            $variable_prefix = $variable_parts[0];
            $variable_name = $variable_parts[1];
            $variable_name = substr($variable_name, 0, strlen($variable_name) - 1);

            if(!str_starts_with(PATH_PARTS[$index], $variable_prefix)) return;
            $path_data[$variable_name] = substr(PATH_PARTS[$index], strlen($variable_prefix));
            continue;
        }

        //if it's not a variable, just check if it matches
        if(strtolower($part) !== strtolower(PATH_PARTS[$index])){
            //does not match, move on
            return;
        }

        //matches (so far...)
    }

    //if it's an options request, add this method to the list
    // (options requests just tell the browser what methods are allowed)
    // add the option to the array and then return (we'll send them later)
    if($_SERVER['REQUEST_METHOD'] === 'OPTIONS'){
        $OPTIONS[] = $method;
        return;
    }

    //The path is good, now check other stuff
    //At this point, any errors will be fatal, and no other endpoints will be checked

    // you can add logic here to check authorization, etc.






    //get provided data into the RAW_DATA constant

    $json_data = [];
    $raw_data = file_get_contents('php://input');

    //get data from client if method supports it
    if($_SERVER['REQUEST_METHOD'] === 'GET'){
        //use query string parameters in PHP's $_GET array
        $json_data = $_GET;
    } else if($raw_data){
        //the user sent data, so verify it is properly formatted
        $json_data = json_decode($raw_data, true);
        if($json_data === null){
            err(400, "Invalid JSON syntax.");
        }
    }

    //add url data to json (replacing if needed)
    // e.g. /users/{user_id} would have the user_id in the path data
    foreach($path_data as $key=>$value){
        $json_data[$key] = $value;
    }

    define('RAW_DATA', $json_data);

    //get data from endpoint
    $return_data = $function( /* this function can take any arguments if you want */ );

    if(!$return_data){
        //send no content status code and exit
        http_response_code(204);
        exit();
    }

    //verify data
    if(!is_array($return_data)){
        err(500, "Return value is not an object. That's on us.");
    }

    //send data to client
    header("Content-Type: application/json");
    echo json_encode($return_data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_LINE_TERMINATORS);
    http_response_code(200);
    exit();
}

/** Function to end if no endpoints are reached */
function finish(){
    global $OPTIONS;
    //if it's an options request, return the options
    if($_SERVER['REQUEST_METHOD'] === 'OPTIONS'){
        header("Allow: " . join(", ", $OPTIONS));
        header("Access-Control-Allow-Methods: " . join(", ", $OPTIONS));
        http_response_code(204);
        exit();
    }
    //no endpoint matched
    err(404, "Endpoint not found. Make sure you're using the correct endpoint and method.");
}