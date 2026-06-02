<?php

namespace App\Support;

final class ChatPolicy
{
    public const POLICY_VERSION = '2026-06-02-v2';

    public const SOURCE_WEB_CHAT = 'web-chat';

    public static function version(): string
    {
        return self::POLICY_VERSION;
    }
}
