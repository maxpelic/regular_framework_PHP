<?php

//function to split the request path into parts
//each part is separated by a forward slash
function split_path($path){
    $path = explode("?", $path)[0];
    $path = trim($path, " /");
    if(str_starts_with($path, PATH_PREFIX)){
        $path = substr($path, strlen(PATH_PREFIX));
    }
    return array_values(array_filter(explode('/', $path)));
}

/* check if object is a numeric array */
//not sure if this is used, but it's here ;)
function is_numeric_array(array $object){
    return is_array($object) && array_keys($object) === range(0, count($object) - 1);
}


/* send an error to the client */
function err(int $code, string $message, $logError = false){
    header("Content-Type: Application/json");
    header("HTTP/1.1 " . $code . " " . $message);
    echo json_encode([
        "errors"=>[[
            "message"=>$message,
            "code"=>$code
        ]]
    ]);

    if($logError !== false){
        //log the error
        $filename = ERROR_LOG_PATH . (new DateTime())->format("Ymd") . ".json";
        $file = fopen($filename, "a");
        //add data
        $data = [];
        $data['message'] = $message;
        if($logError !== true){
            $data['message'] .= "\n\n" . $logError;
        }
        $data['date'] = (new DateTime())->format("r");
        $data['ip'] = IP_STRING;
        //add backtrace
        $data['trace'] = [];
        $trace = debug_backtrace();
        foreach($trace as $info){
            $data["trace"][] = [
                "file"=>str_replace('\\', '/', str_replace(realpath($_SERVER['DOCUMENT_ROOT'] . '/../'), '..', $info['file'])),
                "line"=>$info['line']
            ];
        }
        fwrite($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . ",\n");
        fclose($file);
    }
    exit();
}

/* get a value sent to the api */
function param(String $name, String $type = "string", bool $require_value = false){
    $type = strtolower($type);
    
    if(!isset(RAW_DATA[$name])){
        if($require_value){
            //value is required, throw an error
            err(400, "Parameter '$name' is missing.");
        }
        //value is not required, return null
        return $type === 'bool' ? false : null;
    }

    //get value at position
    $return_value = RAW_DATA[$name];

    //verify the type of data
    switch($type){
        case "number":
            if(!is_numeric($return_value)){
                err(400, "Parameter '$name' must be a number or numeric string.");
            }
            return floatval($return_value);
        case "string":
            return strval($return_value);
        case "bool":
            if(is_bool($return_value)) return $return_value;
            if(filter_var($return_value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null){
                err(400, "Parameter '$name' must be a boolean value or boolean-like string.");
            }
            return boolval($return_value);
        case "date":
            if(!is_string($return_value)){
                err(400, "Parameter '$name' must be a date string.");
            }
            $date = new date($return_value);
            return $date;
        case "email":
            if(!is_string($return_value)){
                err(400, "Parameter '$name' must be an email address.");
            }
            if(!filter_var($return_value, FILTER_VALIDATE_EMAIL)){
                err(400, "Parameter '$name' must be a valid email address.");
            }
            return $return_value;
        case "json":
            if(!is_array($return_value)){
                err(400, "Parameter '$name' must be a JSON object.");
            }
            return $return_value;
        default:
            err(500, "Variable type not supported. That's on us.");
    }

}

//get all params as an associative array
function params(){
    return RAW_DATA;
}