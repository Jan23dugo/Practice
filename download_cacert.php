<?php
// Script to download CA certificate bundle
$cacertUrl = 'https://curl.se/ca/cacert.pem';
$savePath = __DIR__ . '/cacert.pem';

echo "Downloading CA certificates from $cacertUrl...\n";

$ch = curl_init($cacertUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$cacert = curl_exec($ch);

if (curl_errno($ch)) {
    die('Failed to download CA certificates: ' . curl_error($ch));
}

curl_close($ch);

if (file_put_contents($savePath, $cacert) === false) {
    die('Failed to save CA certificates to ' . $savePath);
}

echo "CA certificates successfully downloaded and saved to $savePath\n";
echo "File size: " . filesize($savePath) . " bytes\n";
?> 