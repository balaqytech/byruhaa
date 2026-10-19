<?php

namespace App\Modules\Identity\Actions;

use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\IpUtils;

class PublicAvatarAddressResolver
{
    private const BLOCKED_RANGES = [
        '0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8',
        '169.254.0.0/16', '172.16.0.0/12', '192.0.0.0/24',
        '192.0.2.0/24', '192.88.99.0/24', '192.168.0.0/16',
        '198.18.0.0/15', '198.51.100.0/24', '203.0.113.0/24',
        '224.0.0.0/4', '240.0.0.0/4',
        '::/96', '::ffff:0:0/96', '64:ff9b::/96', '64:ff9b:1::/48',
        '100::/64', '2001::/32', '2001:2::/48', '2001:10::/28',
        '2001:20::/28', '2001:db8::/32', '2002::/16',
        'fc00::/7', 'fe80::/10', 'ff00::/8',
    ];

    public function resolve(string $host): string
    {
        if (! str_contains($host, '.')
            || filter_var($host, FILTER_VALIDATE_IP) !== false
            || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw $this->invalidUrl();
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        $addresses = [];

        foreach ($records ?: [] as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;

            if (! is_string($address) || ! $this->isPublicIp($address)) {
                throw $this->invalidUrl();
            }

            $addresses[] = $address;
        }

        if ($addresses === []) {
            throw $this->invalidUrl();
        }

        return $addresses[0];
    }

    public function isPublicIp(string $address): bool
    {
        return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false
            && ! IpUtils::checkIp($address, self::BLOCKED_RANGES);
    }

    private function invalidUrl(): ValidationException
    {
        return ValidationException::withMessages(['avatar_url' => 'يجب أن يشير رابط الصورة إلى خادم عام آمن.']);
    }
}
