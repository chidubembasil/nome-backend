<?php
    include 'configs.php';

    header('Content-Type: application/json');

    // Initialize success/error
    $success = null;
    $error = null;

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $data = json_decode(file_get_contents("php://input"), true);

        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $sanitizedEmail = filter_var($email, FILTER_SANITIZE_EMAIL);

        // Generate name from email
        $username = explode("@", $sanitizedEmail)[0];
        $name = preg_replace("/[\._]/", " ", $username);
        $name = preg_replace("/\d+$/", "", $name);
        $name = ucwords($name);

        $userId = uniqid();

        if (empty($sanitizedEmail) || empty($password) || empty($name)) {
            echo json_encode([
                "status" => "error",
                "message" => "All fields are required"
            ]);
            exit;
        }

        $user = $trippUser->findOne(["email" => $sanitizedEmail]);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Login successful"
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Invalid password"
                ]);
            }
            exit;
        }

        // Register new user
        $insertUser = $trippUser->insertOne([
            "_id" => $userId,
            "name" => $name,
            "email" => $sanitizedEmail,
            "password" => password_hash($password, PASSWORD_DEFAULT)
        ]);

        if ($insertUser->getInsertedCount() > 0) {
            echo json_encode([
                "status" => "success",
                "message" => "User registered successfully"
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "User registration failed"
            ]);
        }
        exit;
    }

    // PUT request (update profile)
    if ($_SERVER["REQUEST_METHOD"] == "PUT") {
        $data = json_decode(file_get_contents("php://input"), true);

        $email = $data['email'] ?? '';
        $sanitizedEmail = filter_var($email, FILTER_SANITIZE_EMAIL);

        // Make sure required fields exist
        $required = ['password','phoneNo','bio','address','city','state','country','zipCode'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                echo json_encode(["status"=>"error","message"=>"All fields are required"]);
                exit;
            }
        }

        // Build update array
        $updateData = [
            'phoneNo' => filter_var($data['phoneNo'], FILTER_SANITIZE_NUMBER_INT),
            'bio' => $data['bio'],
            'address' => $data['address'],
            'city' => $data['city'],
            'state' => $data['state'],
            'country' => $data['country'],
            'zipCode' => $data['zipCode']
        ];

        // Note: $_FILES does NOT work with PUT. Use POST for file uploads

        $updateResult = $trippUser->updateMany(
            ['email' => $sanitizedEmail],
            ['$set' => $updateData],
            ['upsert' => true]
        );

        if ($updateResult->getModifiedCount() > 0 || $updateResult->getUpsertedCount() > 0) {
            echo json_encode(["status"=>"success","message"=>"User updated successfully"]);
        } else {
            echo json_encode(["status"=>"error","message"=>"User update failed"]);
        }
        exit;
    }

    // No need for final echo with $success/$error
