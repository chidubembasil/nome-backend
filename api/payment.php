<?php
require "../configs.php";

header("Content-Type: application/json");

// Flutterwave Secret Key
// define("FLW_SECRET_KEY", "YOUR_FLUTTERWAVE_SECRET_KEY");

// Your subaccounts
$PERCENT_SUBACCOUNT = "RS_A83B219334DD5EC356BA7DB99E38933F"; // 3% split for BUY
$FLAT_SUBACCOUNT = "RS_08C55A89BC9509676E1A38FC95B4BC93";    // ₦500 flat for rent/stay/invest

// Redirect URL after Flutterwave payment
$REDIRECT_URL = "https://yourwebsite.com/payment-success";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Only POST allowed"]);
    exit;
}

// Get request data
$data = json_decode(file_get_contents("php://input"), true);

$propertyId = $data["propertyId"] ?? "";
$userEmail = $data["email"] ?? "";
$currency = strtoupper($data["currency"] ?? "NGN");

if (!$propertyId || !$userEmail) {
    echo json_encode(["status" => "error", "message" => "Missing email or propertyId"]);
    exit;
}

// Fetch property from MongoDB
$property = $trippListing->findOne(["_id" => $propertyId]);

if (!$property) {
    echo json_encode(["status" => "error", "message" => "Property not found"]);
    exit;
}

$amount = $property["price"];
$type = strtolower($property["type"]); // buy | rent | stay | invest
$ownerId = $property["ownerId"] ?? "unknown";

// Determine split mode
if ($type === "buy") {
    $selectedSubaccount = $PERCENT_SUBACCOUNT;
} else {
    $selectedSubaccount = $FLAT_SUBACCOUNT;
}

// Prepare Flutterwave payload
$payload = [
    "tx_ref" => "TRIPP_" . uniqid(),
    "amount" => $amount,
    "currency" => $currency,
    "redirect_url" => $REDIRECT_URL,
    "customer" => [
        "email" => $userEmail
    ],
    "customizations" => [
        "title" => "Tripp Property Payment",
        "description" => "Payment for property: " . ($property["title"] ?? "Real Estate Transaction")
    ],
    "subaccounts" => [
        [
            "id" => $selectedSubaccount
        ]
    ]
];

$ch = curl_init("https://api.flutterwave.com/v3/payments");

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . FLW_SECRET_KEY
]);

$response = curl_exec($ch);

// Fallback if curl_close is unavailable
if (function_exists("curl_close")) {
    curl_close($ch);
}

// Decode Flutterwave response
$res = json_decode($response, true);

// If payment link not created
if (!$res || $res["status"] !== "success") {
    echo json_encode([
        "status" => "error",
        "message" => "Flutterwave error",
        "response" => $res
    ]);
    exit;
}

// Insert transaction into DB
$trippTransaction->insertOne([
    "tx_ref" => $payload["tx_ref"],
    "propertyId" => $propertyId,
    "ownerId" => $ownerId,
    "email" => $userEmail,
    "amount" => $amount,
    "currency" => $currency,
    "type" => $type,
    "subaccount_used" => $selectedSubaccount,
    "status" => "pending",
    "createdAt" => date("Y-m-d H:i:s")
]);

// Return payment link
echo json_encode([
    "status" => "success",
    "message" => "Payment created",
    "payment_link" => $res["data"]["link"]
]);
exit;

