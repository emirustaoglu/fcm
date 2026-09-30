<?php

declare(strict_types=1);

namespace emirustaoglu\fcm\Tests;

use emirustaoglu\fcm\Auth\ServiceAccount;
use PHPUnit\Framework\TestCase;

final class ServiceAccountTest extends TestCase
{
    public function testServiceAccountCanBeCreatedFromJson(): void
    {
        $account = ServiceAccount::fromJson(json_encode([
            'type' => 'service_account',
            'project_id' => 'demo-project',
            'private_key_id' => 'demo',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nINVALID\n-----END PRIVATE KEY-----\n",
            'client_email' => 'demo@example.iam.gserviceaccount.com',
        ], JSON_THROW_ON_ERROR));

        self::assertSame('demo-project', $account->getProjectId());
    }
}