<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class RandomizerService
{
    public const ALGORITHM_NAME = 'sha256(sorted_uuid_list|timestamp_bucket_utc|server_nonce_hex) % count';

    public function pick(array $uuids): array
    {
        $canonicalUuids = array_map(
            static fn (string $uuid): string => strtolower(trim($uuid)),
            $uuids
        );

        sort($canonicalUuids, SORT_STRING);

        $count = count($canonicalUuids);

        if ($count === 0) {
            throw new InvalidArgumentException('UUID list cannot be empty.');
        }

        $timestamp = CarbonImmutable::now('UTC');
        $timestampUtc = $timestamp->format('Y-m-d\TH:i:s\Z');
        $timestampBucketUtc = $timestamp->startOfMinute()->format('Y-m-d\TH:i\Z');

        $joinedUuids = implode("\n", $canonicalUuids);
        $uuidsSha256 = hash('sha256', $joinedUuids);

        $serverNonce = random_bytes(32);
        $serverNonceHex = bin2hex($serverNonce);
        $serverNonceSha256 = hash('sha256', $serverNonce);

        // Digest input is fully documented and reproducible for post-request audits.
        $digestInput = $joinedUuids.'|'.$timestampBucketUtc.'|'.$serverNonceHex;
        $digestSha256 = hash('sha256', $digestInput);

        $index = $this->hexDigestModulo($digestSha256, $count);
        $selectedUuid = $canonicalUuids[$index];

        return [
            'selected_uuid' => $selectedUuid,
            'meta' => [
                'count' => $count,
                'algorithm' => self::ALGORITHM_NAME,
                'audit' => [
                    'uuids_sha256' => $uuidsSha256,
                    'count' => $count,
                    'digest_sha256' => $digestSha256,
                    'index' => $index,
                    'timestamp_utc' => $timestampUtc,
                    'timestamp_bucket_utc' => $timestampBucketUtc,
                    'server_nonce_sha256' => $serverNonceSha256,
                    'server_nonce_hex' => $serverNonceHex,
                    'algorithm_version' => (string) config('omaraf.algorithm_version', 'v1'),
                ],
            ],
        ];
    }

    private function hexDigestModulo(string $hexDigest, int $modulo): int
    {
        $result = 0;

        foreach (str_split($hexDigest) as $char) {
            $result = (($result * 16) + hexdec($char)) % $modulo;
        }

        return $result;
    }
}
