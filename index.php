<?php
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    $method = $_SERVER["REQUEST_METHOD"];
    $uri = explode("/", trim($_SERVER["REQUEST_URI"], "/"));

    // Example: /api/users → ["api", "users"]
    $endpoint = isset($uri[1]) ? $uri[1] : null;

    switch ($endpoint) {
        case "auth":
            require "api/auth.php";
            break;

        case "listing":
            require "api/listing.php";
            break;

        case "upload":
            require "api/upload.php";
            break;

        case "mylistings":
            require "api/mylistings.php";
            break;

        case "stay":
            require "api/stay.php";
            break;   
            
        case "rent":
            require "api/rent.php";
            break;    

        default:
            echo json_encode(["error" => "Invalid endpoint"]);
    }
