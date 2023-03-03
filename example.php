<?php
require_once("./regular_framework.php");

// you'll want to have one file handle all requests (you can use FallbackResource or something similar)

// for a large project, you'll want to split your endpoints into multiple files

register("GET", "example", function(){
    //this is an example endpoint
    //it will be called when the request path is /example
    //it will only be called if the request method is GET
    //it will return a json object with the message "Hello World!"
    return [
        "message"=>"Hello World!"
    ];
});

register("GET", "example/{id}", function(){
    //this is an example endpoint
    //it will be called when the request path is /example/{id}
    //it will only be called if the request method is GET
    //it will return a json object with the message "Hello World!" and the id
    $id = param("id", "int", true);
    return [
        "message"=>"Hello World!",
        "id"=>$id
    ];
});

register("POST", "example", function(){
    //this is an example endpoint
    //it will be called when the request path is /example
    //it will only be called if the request method is POST
    //it will return a json object with the message "Hello World!" and the data sent by the client
    $data = param("data", "string", true);
    return [
        "message"=>"Hello World!",
        "data"=>$data
    ];
});

//this is needed for options requests and giving 404 errors
finish();



