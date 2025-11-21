<?php
    header("Content-Type: application/json");

    $apiKey = "AIzaSyAxDALFH6DeB1jt4WauABmOfURUOC4TDIQ";

    $data = json_decode(file_get_contents("php://input"), true);
    $userPrompt = $data["prompt"];

    // SYSTEM INSTRUCTIONS (The Brain of Nome AI)
    $systemInstruction = "
    You are NESSA or nessa — an intelligent assistant for a global real estate platform called Nome.
    Your job:
    - Answer ONLY real estate questions or Nome-related questions.
    - Provide clear, brief, helpful guidance.
    - Understand property listings, rentals, buying, selling, mortgage, escrow, pricing, countries, states, cities, shortlets, land, houses, and apartments.
    - Give advice on safety, verification, KYC, and payments.
    - Never hallucinate things about Nome. If unsure, ask for more information.
    - Use simple English and be friendly.

    Nome Description:
    Nome is a real estate marketplace that allows users to buy, rent, stay (shortlet), invest, and favorite properties.
    It supports:
    - global currencies
    - split payments
    - escrow logic
    - user KYC
    - verified listings
    - Ably live updates
    ";

    // Combine system + user message
    $finalPrompt = $systemInstruction . "\n\nUser: " . $userPrompt;

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$apiKey";

    $body = json_encode([
        "contents" => [[
            "parts" => [[ "text" => $finalPrompt ]]
        ]]
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    echo $response;
