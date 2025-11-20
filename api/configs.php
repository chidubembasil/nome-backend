<?php
    require "../vendor/autoload.php";

    // Get environment variables
    $username = getenv('MONGO_USERNAME');
    $password = getenv('MONGO_PASSWORD');

    // Debug: check if PHP can read them
    var_dump($username);
    var_dump($password);

    // URL-encode password in case it contains special characters
    $encodedPassword = urlencode($password);

    // Connect to MongoDB
    $client = new MongoDB\Client(
        "mongodb+srv://$username:$encodedPassword@trippdb.ne0tccv.mongodb.net/?appName=trippDB"
    );

    // Select database and collections
    $tripp = $client->tripp;
    $trippUser = $tripp->users;
    $trippProperty = $tripp->properties;
    // $trippSearch = $tripp->search;
    $trippFilter = $tripp->filter;
    $trippTransaction = $tripp->transactions;
    $trippPay = $tripp->pay;
    $trippListing = $tripp->listing;
    $trippFav = $tripp->favourite;

    try {
        $client->listDatabases();
        echo "Connected to MongoDB successfully!";
    } catch (Exception $e) {
        echo "Connection failed: " . $e->getMessage();
    }
