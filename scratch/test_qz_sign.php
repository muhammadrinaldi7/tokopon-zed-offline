<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/qz/sign', 'GET', ['request' => 'test_challenge_payload']);
$controller = new App\Http\Controllers\QzTrayController();
$response = $controller->sign($request);

echo "Status Code: " . $response->getStatusCode() . "\n";
echo "Content-Type: " . $response->headers->get('Content-Type') . "\n";
$sig = $response->getContent();
echo "Signature Length: " . strlen($sig) . "\n";
echo "Signature Base64: " . substr($sig, 0, 40) . "...\n";

// Verify with public certificate
$certContent = file_get_contents(__DIR__ . '/../public/qz/digital-certificate.txt');
$pubKey = openssl_pkey_get_public($certContent);
$verify = openssl_verify('test_challenge_payload', base64_decode($sig), $pubKey, OPENSSL_ALGO_SHA512);

echo "Verification Result with public cert: " . ($verify === 1 ? "SUCCESS (100% VALID)" : "FAILED") . "\n";
