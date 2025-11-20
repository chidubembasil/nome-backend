<?php
    include "configs.php";
    include "auth.php";
    use Ably\AblyRest;

    // Initialize Ably
    $ably = new AblyRest('RSTb1g.vchEGQ:QDy0r8L70mwgsHpNtXNlWZ4DIN661iLkMCnI_7ELMDA');
    $channel = $ably->channels->get('mylistings');

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $data = $_POST;
        $listingId = uniqid();

        $propTitle = trim($data["propertyTitle"] ?? '');
        $propDesc = trim($data["propertyDescription"] ?? '');
        $address = trim($data["address"] ?? '');
        $city = trim($data["city"] ?? '');
        $state = trim($data["state"] ?? '');
        $country = trim($data["country"] ?? '');
        $zipCode = trim($data["zipCode"] ?? '');
        $price = trim($data["price"] ?? '');
        $bedroom = trim($data["bedroom"] ?? '');
        $bathroom = trim($data["bathroom"] ?? '');
        $squareFeet = trim($data["squareFeet"] ?? '');
        $yearBuilt = trim($data["yearBuilt"] ?? '');
        $parkingSpot = trim($data["parkingSpot"] ?? '');

        // Validate required fields
        if (!$propTitle || !$propDesc || !$address || !$city || !$state || !$country || !$price || !$bedroom || !$bathroom || !$squareFeet || !$parkingSpot) {
            echo json_encode([
                "status" => "error",
                "message" => "All fields are required"
            ]);
            exit;
        }

        // Handle multiple images
        if (!isset($_FILES['propertyImg'])) {
            echo json_encode([
                "status" => "error",
                "message" => "At least one property image is required"
            ]);
            exit;
        }

        $images = [];
        $imageFiles = $_FILES['propertyImg'];
        for ($i = 0; $i < count($imageFiles['name']); $i++) {
            $tmpName = $imageFiles['tmp_name'][$i];
            $name = uniqid() . "_" . basename($imageFiles['name'][$i]); // unique file name
            $type = $imageFiles['type'][$i];
            $size = $imageFiles['size'][$i];
            $error = $imageFiles['error'][$i];

            if ($error === 0) {
                $uploadPath = __DIR__ . "/uploads/" . $name;
                move_uploaded_file($tmpName, $uploadPath);
                $images[] = [
                    "imageName" => $name,
                    "imagePath" => "uploads/" . $name,
                    "imageType" => $type,
                    "imageSize" => $size
                ];
            }
        }

        // Insert into MongoDB
        $upload = $trippProperty->insertOne([
            'Listing_id' => $listingId,
            'title' => $propTitle,
            'description' => $propDesc,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'country' => $country,
            'zipCode' => $zipCode,
            'price' => $price,
            'bedroom' => $bedroom,
            'bathroom' => $bathroom,
            'squareFeet' => $squareFeet,
            'yearBuilt' => $yearBuilt,
            'parkingSpot' => $parkingSpot,
            'images' => $images,
            'createdAt' => time()
        ]);

        if ($upload) {
            // Publish to Ably real-time channel
            $channel->publish("mylistings", [
                "action" => "new_listing",
                "Listing_id" => $listingId,
                "title" => $propTitle,
                "images" => $images
            ]);

            echo json_encode([
            "status" => "success",
            "message" => "Property uploaded successfully",
            "data" => [
                "Listing_id" => $listingId,
                "title" => $propTitle,
                "images" => $images
            ]
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Property upload failed"
            ]);
        }
    }
