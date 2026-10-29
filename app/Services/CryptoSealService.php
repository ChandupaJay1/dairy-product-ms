<?php

namespace App\Services;

use App\Core\Database;

/**
 * Cryptographic Seal & Integrity Service
 * 
 * Provides HMAC-SHA256 digital seals for receipts, credit orders,
 * and immutable audit logging.
 */
class CryptoSealService
{
    private const HMAC_SECRET_KEY = 'polonnaruwa_dairy_hmac_sha256_audit_key_2026';

    /**
     * Generates a deterministic SHA-256 HMAC transaction seal.
     */
    public function generateTransactionSeal(array $payload): string
    {
        ksort($payload);
        $serialized = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return hash_hmac('sha256', $serialized, self::HMAC_SECRET_KEY);
    }

    /**
     * Hashes digital signature image/vector data into a compact SHA-256 digest.
     */
    public function hashDigitalSignature(?string $signatureDataUrl): ?string
    {
        if (empty($signatureDataUrl)) {
            return null;
        }
        return hash('sha256', $signatureDataUrl);
    }

    /**
     * Records an immutable audit log entry with SHA-256 seal.
     */
    public function logAuditEvent(string $actionType, string $actorName, string $actorRole, string $targetEntity, array $details): void
    {
        try {
            $detailsJson = json_encode($details);
            $seal = $this->generateTransactionSeal([
                'actionType' => $actionType,
                'actorName' => $actorName,
                'targetEntity' => $targetEntity,
                'details' => $detailsJson,
                'timestamp' => date('c')
            ]);

            Database::execute(
                "INSERT INTO audit_logs (action_type, actor_name, actor_role, target_entity, details_json, security_seal) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$actionType, $actorName, $actorRole, $targetEntity, $detailsJson, $seal]
            );
        } catch (\Exception $e) {
            error_log('Audit logging failed: ' . $e->getMessage());
        }
    }
}
