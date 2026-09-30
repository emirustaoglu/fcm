<?php

declare(strict_types=1);

include(__DIR__ . '/../vendor/autoload.php');

use emirustaoglu\fcm\Fcm;

$fcm = Fcm::fromServiceAccount(
    __DIR__ . '/../service-account.json'
);

$response = $fcm->sendToToken(
    token: 'YOUR_DEVICE_TOKEN',
    title: 'Merhaba',
    body: 'Yeni Bir Bildirimizni Var.'
);

print_r($response);
