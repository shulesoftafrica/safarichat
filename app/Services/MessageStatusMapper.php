<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * MessageStatusMapper handles detailed status mapping and tracking
 * between different messaging systems and provides status lifecycle management
 */
class MessageStatusMapper
{
    /**
     * Comprehensive status definitions with descriptions and lifecycle stages
     */
    private static array $statusDefinitions = [
        // Local system statuses
        'pending' => [
            'description' => 'Message queued for sending',
            'lifecycle_stage' => 'initial',
            'is_final' => false,
            'next_possible' => ['sent', 'failed'],
            'api_equivalent' => 'queued'
        ],
        'sent' => [
            'description' => 'Message sent to WhatsApp',
            'lifecycle_stage' => 'transmitted',
            'is_final' => false,
            'next_possible' => ['delivered', 'failed', 'read'],
            'api_equivalent' => 'delivered'
        ],
        'delivered' => [
            'description' => 'Message delivered to recipient',
            'lifecycle_stage' => 'confirmed',
            'is_final' => false,
            'next_possible' => ['read', 'failed'],
            'api_equivalent' => 'confirmed'
        ],
        'read' => [
            'description' => 'Message read by recipient',
            'lifecycle_stage' => 'completed',
            'is_final' => true,
            'next_possible' => [],
            'api_equivalent' => 'read_receipt'
        ],
        'failed' => [
            'description' => 'Message delivery failed',
            'lifecycle_stage' => 'error',
            'is_final' => true,
            'next_possible' => ['pending'], // Can retry
            'api_equivalent' => 'error'
        ],

        // API system statuses
        'queued' => [
            'description' => 'Message in API queue',
            'lifecycle_stage' => 'initial',
            'is_final' => false,
            'next_possible' => ['delivered', 'error'],
            'local_equivalent' => 'pending'
        ],
        'confirmed' => [
            'description' => 'Message confirmed delivered',
            'lifecycle_stage' => 'confirmed',
            'is_final' => false,
            'next_possible' => ['read_receipt'],
            'local_equivalent' => 'delivered'
        ],
        'read_receipt' => [
            'description' => 'Read receipt received',
            'lifecycle_stage' => 'completed',
            'is_final' => true,
            'next_possible' => [],
            'local_equivalent' => 'read'
        ],
        'error' => [
            'description' => 'API delivery error',
            'lifecycle_stage' => 'error',
            'is_final' => true,
            'next_possible' => ['queued'], // Can retry
            'local_equivalent' => 'failed'
        ]
    ];

    /**
     * Error code mappings for failed messages
     */
    private static array $errorCodeMappings = [
        // WhatsApp specific errors
        'invalid_number' => [
            'local_code' => 'INVALID_PHONE',
            'api_code' => 'INVALID_RECIPIENT',
            'message' => 'Phone number format invalid'
        ],
        'number_not_whatsapp' => [
            'local_code' => 'NOT_WHATSAPP_USER',
            'api_code' => 'RECIPIENT_UNAVAILABLE',
            'message' => 'Phone number not registered with WhatsApp'
        ],
        'session_expired' => [
            'local_code' => 'SESSION_EXPIRED',
            'api_code' => 'SESSION_INVALID',
            'message' => 'WhatsApp session has expired'
        ],
        'rate_limited' => [
            'local_code' => 'RATE_LIMITED',
            'api_code' => 'TOO_MANY_REQUESTS',
            'message' => 'Message rate limit exceeded'
        ],
        'content_blocked' => [
            'local_code' => 'CONTENT_BLOCKED',
            'api_code' => 'CONTENT_VIOLATION',
            'message' => 'Message content violates policy'
        ]
    ];

    /**
     * Map status from local to API format
     */
    public static function mapToApi(string $localStatus): string
    {
        $statusDef = self::$statusDefinitions[$localStatus] ?? null;
        
        if (!$statusDef || !isset($statusDef['api_equivalent'])) {
            Log::warning("Unknown local status for API mapping: {$localStatus}");
            return $localStatus;
        }

        return $statusDef['api_equivalent'];
    }

    /**
     * Map status from API to local format
     */
    public static function mapToLocal(string $apiStatus): string
    {
        $statusDef = self::$statusDefinitions[$apiStatus] ?? null;
        
        if (!$statusDef || !isset($statusDef['local_equivalent'])) {
            Log::warning("Unknown API status for local mapping: {$apiStatus}");
            return $apiStatus;
        }

        return $statusDef['local_equivalent'];
    }

    /**
     * Check if status transition is valid
     */
    public static function isValidTransition(string $fromStatus, string $toStatus): bool
    {
        $statusDef = self::$statusDefinitions[$fromStatus] ?? null;
        
        if (!$statusDef) {
            return false;
        }

        return in_array($toStatus, $statusDef['next_possible']);
    }

    /**
     * Get all possible next statuses for current status
     */
    public static function getNextPossibleStatuses(string $currentStatus): array
    {
        $statusDef = self::$statusDefinitions[$currentStatus] ?? null;
        return $statusDef['next_possible'] ?? [];
    }

    /**
     * Check if status is final (no further transitions possible)
     */
    public static function isFinalStatus(string $status): bool
    {
        $statusDef = self::$statusDefinitions[$status] ?? null;
        return $statusDef['is_final'] ?? false;
    }

    /**
     * Get lifecycle stage for status
     */
    public static function getLifecycleStage(string $status): string
    {
        $statusDef = self::$statusDefinitions[$status] ?? null;
        return $statusDef['lifecycle_stage'] ?? 'unknown';
    }

    /**
     * Get human-readable description for status
     */
    public static function getStatusDescription(string $status): string
    {
        $statusDef = self::$statusDefinitions[$status] ?? null;
        return $statusDef['description'] ?? 'Unknown status';
    }

    /**
     * Map error code between systems
     */
    public static function mapErrorCode(string $errorCode, string $direction = 'local_to_api'): array
    {
        foreach (self::$errorCodeMappings as $mapping) {
            $sourceKey = $direction === 'local_to_api' ? 'local_code' : 'api_code';
            $targetKey = $direction === 'local_to_api' ? 'api_code' : 'local_code';
            
            if ($mapping[$sourceKey] === $errorCode) {
                return [
                    'code' => $mapping[$targetKey],
                    'message' => $mapping['message']
                ];
            }
        }

        return [
            'code' => $errorCode,
            'message' => 'Unknown error'
        ];
    }

    /**
     * Get status progression analytics
     */
    public static function getStatusProgression(array $statusHistory): array
    {
        $progression = [];
        $totalTime = 0;
        
        for ($i = 0; $i < count($statusHistory) - 1; $i++) {
            $from = $statusHistory[$i];
            $to = $statusHistory[$i + 1];
            
            $timeDiff = strtotime($to['timestamp']) - strtotime($from['timestamp']);
            $totalTime += $timeDiff;
            
            $progression[] = [
                'from' => $from['status'],
                'to' => $to['status'],
                'duration_seconds' => $timeDiff,
                'is_valid_transition' => self::isValidTransition($from['status'], $to['status'])
            ];
        }

        return [
            'progression' => $progression,
            'total_duration_seconds' => $totalTime,
            'final_status' => end($statusHistory)['status'] ?? 'unknown',
            'is_completed' => self::isFinalStatus(end($statusHistory)['status'] ?? '')
        ];
    }

    /**
     * Create status update with validation
     */
    public static function createStatusUpdate(string $currentStatus, string $newStatus, string $reason = ''): array
    {
        $isValid = self::isValidTransition($currentStatus, $newStatus);
        
        if (!$isValid) {
            Log::warning("Invalid status transition attempted: {$currentStatus} -> {$newStatus}");
        }

        return [
            'from_status' => $currentStatus,
            'to_status' => $newStatus,
            'is_valid' => $isValid,
            'reason' => $reason,
            'timestamp' => now()->toISOString(),
            'lifecycle_stage' => self::getLifecycleStage($newStatus),
            'is_final' => self::isFinalStatus($newStatus),
            'description' => self::getStatusDescription($newStatus)
        ];
    }

    /**
     * Get all statuses in a specific lifecycle stage
     */
    public static function getStatusesByStage(string $lifecycleStage): array
    {
        $statuses = [];
        
        foreach (self::$statusDefinitions as $status => $definition) {
            if ($definition['lifecycle_stage'] === $lifecycleStage) {
                $statuses[] = $status;
            }
        }

        return $statuses;
    }

    /**
     * Get comprehensive status information
     */
    public static function getStatusInfo(string $status): array
    {
        return self::$statusDefinitions[$status] ?? [
            'description' => 'Unknown status',
            'lifecycle_stage' => 'unknown',
            'is_final' => false,
            'next_possible' => []
        ];
    }

    /**
     * Validate status exists in either system
     */
    public static function isValidStatus(string $status): bool
    {
        return isset(self::$statusDefinitions[$status]);
    }

    /**
     * Get all local statuses
     */
    public static function getLocalStatuses(): array
    {
        $localStatuses = [];
        
        foreach (self::$statusDefinitions as $status => $definition) {
            if (isset($definition['api_equivalent'])) {
                $localStatuses[] = $status;
            }
        }

        return $localStatuses;
    }

    /**
     * Get all API statuses
     */
    public static function getApiStatuses(): array
    {
        $apiStatuses = [];
        
        foreach (self::$statusDefinitions as $status => $definition) {
            if (isset($definition['local_equivalent'])) {
                $apiStatuses[] = $status;
            }
        }

        return $apiStatuses;
    }
}