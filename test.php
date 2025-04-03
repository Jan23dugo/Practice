<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $image_path = $_FILES['image']['tmp_name'];

    // Convert Image to Base64
    $image_content = file_get_contents($image_path);
    $base64_image = base64_encode($image_content);

    // Google Vision API URL
    $api_key = "AIzaSyBVNouCSmBJK3ExPPhQxV3cUJI9vlg90Yg";  // Replace with your actual API key
    $url = "https://vision.googleapis.com/v1/images:annotate?key=$api_key";

    // API Request Payload
    $request_data = json_encode([
        "requests" => [
            [
                "image" => ["content" => $base64_image],
                "features" => [["type" => "TEXT_DETECTION"]]
            ]
        ]
    ]);

    // Send API Request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
    $response = curl_exec($ch);
    curl_close($ch);

    // Decode Response
    $result = json_decode($response, true);
    $extracted_text = $result['responses'][0]['textAnnotations'][0]['description'] ?? 'No text found';

    echo "<h2>Extracted Text:</h2><pre>$extracted_text</pre>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>OCR Image Upload</title>
</head>
<body>
    <h2>Upload an Image for OCR</h2>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="file" name="image" accept="image/*" required>
        <button type="submit">Extract Text</button>
    </form>
</body>
</html>
