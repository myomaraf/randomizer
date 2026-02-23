<?php

namespace App\Services;

use App\Models\Raffle;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;

class RandomizerService
{
    public function pick(string $raffleId, array $ticketUuids): array
    {
        $canonicalTicketUuids = array_map(
            static fn (string $uuid): string => strtolower(trim($uuid)),
            $ticketUuids
        );

        sort($canonicalTicketUuids, SORT_STRING);

        $count = count($canonicalTicketUuids);

        if ($count === 0) {
            throw new InvalidArgumentException('UUID list cannot be empty.');
        }

        $timestamp = CarbonImmutable::now('UTC');
        $timestampUtc = $timestamp->format('Y-m-d\TH:i:s\Z');
        $algorithmVersion = (string) config('omaraf.algorithm_version', '1');
        $algorithmName = 'Omaraf Randomizer v'.$algorithmVersion;

        $joinedUuids = implode("\n", $canonicalTicketUuids);
        $uuidsSha256 = hash('sha256', $joinedUuids);

        $nonceHex = bin2hex(random_bytes(32));
        $digestInput = $joinedUuids.':'.$raffleId.':'.$nonceHex;
        $digestSha256 = hash('sha256', $digestInput);
        $indexSelected = $this->indexFromDigest($digestSha256, $count);
        $selectedUuid = $canonicalTicketUuids[$indexSelected];

        $raffle = DB::transaction(function () use (
            $raffleId,
            $uuidsSha256,
            $count,
            $selectedUuid,
            $algorithmVersion,
            $digestSha256,
            $indexSelected,
            $nonceHex,
            $timestamp,
            $canonicalTicketUuids
        ) {
            $raffle = Raffle::query()->create([
                'raffle_id' => $raffleId,
                'uuids_sha256' => $uuidsSha256,
                'count' => $count,
                'selected_uuid' => $selectedUuid,
                'algorithm_version' => $algorithmVersion,
                'digest_sha256' => $digestSha256,
                'index_selected' => $indexSelected,
                'nonce_hex' => $nonceHex,
                'timestamp_utc' => $timestamp,
            ]);

            $insertedAt = now();
            $tickets = [];

            foreach ($canonicalTicketUuids as $position => $uuid) {
                $tickets[] = [
                    'raffle_id' => $raffle->id,
                    'uuid' => $uuid,
                    'position' => $position,
                    'created_at' => $insertedAt,
                    'updated_at' => $insertedAt,
                ];
            }

            $raffle->tickets()->insert($tickets);

            return $raffle;
        });

        return [
            'raffle_id' => $raffle->raffle_id,
            'selected_uuid' => $selectedUuid,
            'meta' => [
                'count' => $count,
                'algorithm' => $algorithmName,
                'audit' => [
                    'raffle_id' => $raffleId,
                    'uuids_sha256' => $uuidsSha256,
                    'nonce_hex' => $nonceHex,
                    'digest_sha256' => $digestSha256,
                    'index_selected' => $indexSelected,
                    'count' => $count,
                    'timestamp_utc' => $timestampUtc,
                    'algorithm_version' => $algorithmVersion,
                ],
            ],
        ];
    }

    private function indexFromDigest(string $digestSha256, int $count): int
    {
        $seedHex = substr($digestSha256, 0, 16);
        $high32 = hexdec(substr($seedHex, 0, 8));
        $low32 = hexdec(substr($seedHex, 8, 8));

        $base = 4294967296 % $count;
        $highModulo = $high32 % $count;
        $lowModulo = $low32 % $count;

        return (int) (($highModulo * $base + $lowModulo) % $count);
    }
}
