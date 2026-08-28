<?php
// api/gmail/get-refresh-token.php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/security.php';
require_once USER_HOME . '/vendor/autoload.php';

$client = new Google\Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri('https://app.tlapayferrediego.com/api/gmail/save-token.php');
$client->addScope("https://mail.google.com/"); 

// Forzar la entrega del Refresh Token
$client->setAccessType('offline');
$client->setPrompt('consent'); 

$authUrl = $client->createAuthUrl();

header("Location: " . $authUrl);
exit;