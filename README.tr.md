# emirustaoglu/fcm

[Türkçe](README.tr.md) · [English](README.md)

[![Latest Version](https://img.shields.io/packagist/v/emirustaoglu/fcm.svg)](https://packagist.org/packages/emirustaoglu/fcm)
[![Monthly Downloads](https://img.shields.io/packagist/dm/emirustaoglu/fcm.svg)](https://packagist.org/packages/emirustaoglu/fcm/stats)
[![Total Downloads](https://img.shields.io/packagist/dt/emirustaoglu/fcm.svg)](https://packagist.org/packages/emirustaoglu/fcm/stats)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

PHP için **Firebase Cloud Messaging (FCM) HTTP v1 API** odaklı, küçük ve bağımsız bir istemci.

Bu paket Firebase Admin SDK'nın tamamını kullanmaz. Yalnızca FCM mesaj gönderme ihtiyacına odaklanır.

## Özellikler

- FCM HTTP v1 API
- Service Account JSON ile OAuth 2.0 JWT authentication
- Access token cache
- Token'a mesaj gönderme
- Topic'e mesaj gönderme
- Condition'a mesaj gönderme
- Notification payload
- Data payload
- Android config
- APNs config
- `validate_only` desteği
- HTTP hata yönetimi
- Sadece PHP + cURL + JSON + OpenSSL; Firebase/Google SDK bağımlılığı yok

## Gereksinimler

- PHP `8.2+`
- ext-curl
- ext-json
- ext-openssl

## Kurulum

```bash
composer require emirustaoglu/fcm
```

## Firebase hazırlığı

Firebase projesinde FCM HTTP v1 API kullanılmalıdır.

Service Account JSON dosyasını Firebase Console'daki:

**Project Settings → Service Accounts**

bölümünden oluşturabilirsiniz.

Service account dosyasını public repository'ye kesinlikle göndermeyin.

Önerilen yöntem:

```bash
export GOOGLE_APPLICATION_CREDENTIALS="/path/to/service-account.json"
```

Bu sürümde dosya yolunu doğrudan vermek de desteklenmektedir.

## Basit kullanım

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use emirustaoglu\fcm\Fcm;

$fcm = Fcm::fromServiceAccount(
    __DIR__ . '/service-account.json'
);

$response = $fcm->sendToToken(
    token: 'FCM_DEVICE_TOKEN',
    title: 'Merhaba',
    body: 'Yeni bildiriminiz var.'
);

print_r($response);
```

Başarılı bir HTTP v1 isteğinde Firebase mesaj adını içeren bir response döner:

```php
[
    'name' => 'projects/demo-project/messages/0:123456789'
]
```

## Data gönderme

```php
$response = $fcm->sendToToken(
    token: $deviceToken,
    title: 'Sipariş',
    body: 'Yeni siparişiniz var.',
    data: [
        'type' => 'order',
        'order_id' => 12345,
    ]
);
```

FCM data payload değerleri string olarak gönderilir.

## webPush gönderme

```php
$response = $fcm->sendToToken(
    token: $deviceToken,
    title: 'Sipariş',
    body: 'Yeni siparişiniz var.',
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
    topic: 'haberler',
    title: 'Yeni Haber',
    body: 'Yeni haber yayınlandı.'
);
```

## Condition

```php
$response = $fcm->sendToCondition(
    condition: "'news' in topics && 'tr' in topics",
    title: 'Duyuru',
    body: 'Yeni duyuru yayınlandı.'
);
```

## Message Builder

Daha gelişmiş payload için `Message` sınıfını kullanabilirsiniz:

```php
use emirustaoglu\fcm\Message;

$message = Message::create()
    ->token($deviceToken)
    ->notification(
        title: 'Sipariş',
        body: 'Siparişiniz hazırlanıyor.'
    )
    ->data([
        'type' => 'order',
        'order_id' => 12345,
    ]);

$response = $fcm->send($message);
```

## Notification image

```php
$message = Message::create()
    ->token($deviceToken)
    ->notification(
        title: 'Yeni Ürün',
        body: 'Yeni ürünümüzü keşfedin.',
        image: 'https://example.com/product.jpg'
    );

$response = $fcm->send($message);
```

## Android ayarları

```php
use emirustaoglu\fcm\AndroidConfig;
use emirustaoglu\fcm\Message;

$message = Message::create()
    ->token($deviceToken)
    ->notification('Başlık', 'Mesaj')
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
    ->notification('Başlık', 'Mesaj')
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

## Sadece doğrulama

FCM isteğini gerçek teslimat yapmadan doğrulamak için:

```php
$response = $fcm->send(
    $message,
    validateOnly: true
);
```

Bu, HTTP v1 API'nin `validate_only` özelliğini kullanır.

## Hatalar

Genel paket hatası:

```php
use emirustaoglu\fcm\Exceptions\FcmException;
```

Authentication hatası:

```php
use emirustaoglu\fcm\Exceptions\AuthenticationException;
```

FCM HTTP hatası:

```php
use emirustaoglu\fcm\Exceptions\FcmRequestException;

try {
    $response = $fcm->sendToToken(
        $deviceToken,
        'Test',
        'Merhaba'
    );
} catch (FcmRequestException $e) {
    echo $e->getStatusCode();
    print_r($e->getResponse());
}
```

## Test

```bash
composer install
composer test
```

Syntax kontrolü:

```bash
composer test:syntax
```

## Tasarım

Bu paket bilinçli olarak Firebase'in diğer servislerini kapsamaz.

Kapsam:

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

Kapsam dışı:

- Firebase Authentication
- Firestore
- Realtime Database
- Firebase Storage
- Firebase Analytics
- Firebase Remote Config
- Firebase App Check
- Firebase Admin SDK

## Güvenlik

Service Account private key dosyasını Git repository'sine koymayın.

Örneğin:

```gitignore
service-account.json
```

Service Account anahtarı ele geçirilirse saldırgan FCM API'ye yetkili erişim elde edebilir. Anahtarları sunucu üzerinde güvenli bir konumda tutun.

## Lisans

MIT
