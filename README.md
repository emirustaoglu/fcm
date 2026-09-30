# emirustaoglu/fcm

[Türkçe](README.tr.md) · [English](README.md)

[![Latest Version](https://img.shields.io/packagist/v/emirustaoglu/fcm.svg)](https://packagist.org/packages/emirustaoglu/fcm)
[![Monthly Downloads](https://img.shields.io/packagist/dm/emirustaoglu/fcm.svg)](https://packagist.org/packages/emirustaoglu/fcm/stats)
[![Total Downloads](https://img.shields.io/packagist/dt/emirustaoglu/fcm.svg)](https://packagist.org/packages/emirustaoglu/fcm/stats)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A small and independent PHP client focused on the **Firebase Cloud Messaging (FCM) HTTP v1 API**.

This package does not use the full Firebase Admin SDK. It focuses only on the need to send FCM messages.

## Features

* FCM HTTP v1 API
* OAuth 2.0 JWT authentication with Service Account JSON
* Access token caching
* Send messages to tokens
* Send messages to topics
* Send messages using conditions
* Notification payload
* Data payload
* Android config
* APNs config
* `validate_only` support
* HTTP error handling
* No Firebase/Google SDK dependencies; only PHP + cURL + JSON + OpenSSL

## Requirements

* PHP `8.2+`
* ext-curl
* ext-json
* ext-openssl

## Installation

```bash
composer require emirustaoglu/fcm
```

## Firebase Setup

The FCM HTTP v1 API must be enabled and available in your Firebase project.

You can create a Service Account JSON file from:

**Project Settings → Service Accounts**

Never commit your Service Account JSON file to a public repository.

Recommended approach:

```bash
export GOOGLE_APPLICATION_CREDENTIALS="/path/to/service-account.json"
```

This version also supports providing the file path directly.

## Basic Usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use emirustaoglu\fcm\Fcm;

$fcm = Fcm::fromServiceAccount(
    __DIR__ . '/service-account.json'
);

$response = $fcm->sendToToken(
    token: 'FCM_DEVICE_TOKEN',
    title: 'Hello',
    body: 'You have a new notification.'
);

print_r($response);
```

For a successful HTTP v1 request, Firebase returns a response containing the message name:

```php
[
    'name' => 'projects/demo-project/messages/0:123456789'
]
```

## Sending Data

```php
$response = $fcm->sendToToken(
    token: $deviceToken,
    title: 'Order',
    body: 'You have a new order.',
    data: [
        'type' => 'order',
        'order_id' => 12345,
    ]
);
```

FCM data payload values are sent as strings.

## Sending webPush

```php
$response = $fcm->sendToToken(
    token: $deviceToken,
    title: 'Order',
    body: 'You have a new order.',
    webPush: [
        'notification' => [
            'requireInteraction' => true,
            'icon' => 'https://img.shields.io/badge/php-%3E%3D8.1-777BB4.svg'
        ],
        'fcm_options' => [
            'link' => 'https://github.com/emirustaoglu'
        ]
    ]
);
```

## Topic

```php
$response = $fcm->sendToTopic(
    topic: 'news',
    title: 'New News',
    body: 'A new article has been published.'
);
```

## Condition

```php
$response = $fcm->sendToCondition(
    condition: "'news' in topics && 'tr' in topics",
    title: 'Announcement',
    body: 'A new announcement has been published.'
);
```

## Message Builder

For more advanced payloads, you can use the `Message` class:

```php
use emirustaoglu\fcm\Message;

$message = Message::create()
    ->token($deviceToken)
    ->notification(
        title: 'Order',
        body: 'Your order is being prepared.'
    )
    ->data([
        'type' => 'order',
        'order_id' => 12345,
    ]);

$response = $fcm->send($message);
```

## Notification Image

```php
$message = Message::create()
    ->token($deviceToken)
    ->notification(
        title: 'New Product',
        body: 'Discover our new product.',
        image: 'https://example.com/product.jpg'
    );

$response = $fcm->send($message);
```

## Android Configuration

```php
use emirustaoglu\fcm\AndroidConfig;
use emirustaoglu\fcm\Message;

$message = Message::create()
    ->token($deviceToken)
    ->notification('Title', 'Message')
    ->android(
        new AndroidConfig(
            priority: 'HIGH',
            ttl: '3600s',
            collapseKey: 'orders',
            channelId: 'orders'
        )
    );

$response = $fcm->send($message);
```

## APNs

```php
use emirustaoglu\fcm\ApnsConfig;
use emirustaoglu\fcm\Message;

$message = Message::create()
    ->token($deviceToken)
    ->notification('Title', 'Message')
    ->apns(
        new ApnsConfig(
            headers: [
                'apns-priority' => '10',
            ],
            payload: [
                'aps' => [
                    'sound' => 'default',
                ],
            ]
        )
    );

$response = $fcm->send($message);
```

## Validation Only

To validate an FCM request without actually delivering the message:

```php
$response = $fcm->send(
    $message,
    validateOnly: true
);
```

This uses the `validate_only` feature of the HTTP v1 API.

## Errors

General package exception:

```php
use emirustaoglu\fcm\Exceptions\FcmException;
```

Authentication exception:

```php
use emirustaoglu\fcm\Exceptions\AuthenticationException;
```

FCM HTTP request exception:

```php
use emirustaoglu\fcm\Exceptions\FcmRequestException;

try {
    $response = $fcm->sendToToken(
        $deviceToken,
        'Test',
        'Hello'
    );
} catch (FcmRequestException $e) {
    echo $e->getStatusCode();

    print_r($e->getResponse());
}
```

## Testing

```bash
composer install
composer test
```

Syntax check:

```bash
composer test:syntax
```

## Design

This package intentionally does not cover other Firebase services.

Scope:

```text
FCM HTTP v1
   │
   ├── Authentication
   │   └── Service Account → OAuth 2.0 access token
   │
   └── Messaging
       ├── Token
       ├── Topic
       ├── Condition
       ├── Notification
       ├── Data
       ├── Android
       └── APNs
```

Out of scope:

* Firebase Authentication
* Firestore
* Realtime Database
* Firebase Storage
* Firebase Analytics
* Firebase Remote Config
* Firebase App Check
* Firebase Admin SDK

## Security

Do not commit your Service Account private key file to a Git repository.

For example:

```gitignore
service-account.json
```

If a Service Account private key is compromised, an attacker may gain authorized access to the FCM API.

Store Service Account credentials in a secure location on your server.

## License

MIT
