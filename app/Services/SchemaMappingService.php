<?php

namespace App\Services;

/**
 * SchemaMappingService handles mapping between local database schema 
 * and unified notification API schema fields
 */
class SchemaMappingService
{
    /**
     * Mapping configuration for different schemas
     */
    private static array $schemaMappings = [
        'outgoing_messages' => [
            'local_to_api' => [
                'id' => 'message_id',
                'message' => 'content',
                'phone' => 'recipient_phone',
                'sent_at' => 'sent_timestamp',
                'status' => 'delivery_status',
                'api_response' => 'external_response',
                'created_at' => 'created_timestamp',
                'updated_at' => 'last_updated'
            ],
            'api_to_local' => [
                'message_id' => 'id',
                'content' => 'message',
                'recipient_phone' => 'phone',
                'sent_timestamp' => 'sent_at',
                'delivery_status' => 'status',
                'external_response' => 'api_response',
                'created_timestamp' => 'created_at',
                'last_updated' => 'updated_at'
            ]
        ],
        'whatsapp_instances' => [
            'local_to_api' => [
                'id' => 'session_id',
                'sessionName' => 'session_name',
                'sessionActive' => 'is_active',
                'qrCode' => 'qr_code_data',
                'phone' => 'phone_number',
                'connected_at' => 'connection_timestamp',
                'created_at' => 'session_created',
                'updated_at' => 'last_activity'
            ],
            'api_to_local' => [
                'session_id' => 'id',
                'session_name' => 'sessionName',
                'is_active' => 'sessionActive',
                'qr_code_data' => 'qrCode',
                'phone_number' => 'phone',
                'connection_timestamp' => 'connected_at',
                'session_created' => 'created_at',
                'last_activity' => 'updated_at'
            ]
        ],
        'events_guests' => [
            'local_to_api' => [
                'id' => 'contact_id',
                'name' => 'contact_name',
                'phone' => 'phone_number',
                'email' => 'email_address',
                'type' => 'contact_type',
                'created_at' => 'registered_at',
                'updated_at' => 'last_modified'
            ],
            'api_to_local' => [
                'contact_id' => 'id',
                'contact_name' => 'name',
                'phone_number' => 'phone',
                'email_address' => 'email',
                'contact_type' => 'type',
                'registered_at' => 'created_at',
                'last_modified' => 'updated_at'
            ]
        ]
    ];

    /**
     * Status mappings between systems
     */
    private static array $statusMappings = [
        'message_status' => [
            'local_to_api' => [
                'pending' => 'queued',
                'sent' => 'delivered',
                'delivered' => 'confirmed',
                'failed' => 'error',
                'read' => 'read_receipt'
            ],
            'api_to_local' => [
                'queued' => 'pending',
                'delivered' => 'sent',
                'confirmed' => 'delivered',
                'error' => 'failed',
                'read_receipt' => 'read'
            ]
        ],
        'session_status' => [
            'local_to_api' => [
                'active' => 'connected',
                'inactive' => 'disconnected',
                'connecting' => 'initializing',
                'error' => 'failed'
            ],
            'api_to_local' => [
                'connected' => 'active',
                'disconnected' => 'inactive',
                'initializing' => 'connecting',
                'failed' => 'error'
            ]
        ]
    ];

    /**
     * Map local data to API schema
     */
    public static function mapToApiSchema(string $table, array $data): array
    {
        if (!isset(self::$schemaMappings[$table]['local_to_api'])) {
            return $data;
        }

        $mapping = self::$schemaMappings[$table]['local_to_api'];
        $mappedData = [];

        foreach ($data as $key => $value) {
            $mappedKey = $mapping[$key] ?? $key;
            $mappedData[$mappedKey] = $value;
        }

        return $mappedData;
    }

    /**
     * Map API response to local schema
     */
    public static function mapToLocalSchema(string $table, array $data): array
    {
        if (!isset(self::$schemaMappings[$table]['api_to_local'])) {
            return $data;
        }

        $mapping = self::$schemaMappings[$table]['api_to_local'];
        $mappedData = [];

        foreach ($data as $key => $value) {
            $mappedKey = $mapping[$key] ?? $key;
            $mappedData[$mappedKey] = $value;
        }

        return $mappedData;
    }

    /**
     * Map status from local to API
     */
    public static function mapStatusToApi(string $statusType, string $localStatus): string
    {
        return self::$statusMappings[$statusType]['local_to_api'][$localStatus] ?? $localStatus;
    }

    /**
     * Map status from API to local
     */
    public static function mapStatusToLocal(string $statusType, string $apiStatus): string
    {
        return self::$statusMappings[$statusType]['api_to_local'][$apiStatus] ?? $apiStatus;
    }

    /**
     * Get all available schema mappings
     */
    public static function getAvailableMappings(): array
    {
        return array_keys(self::$schemaMappings);
    }

    /**
     * Check if table has schema mapping
     */
    public static function hasMapping(string $table): bool
    {
        return isset(self::$schemaMappings[$table]);
    }

    /**
     * Get field mapping for specific direction
     */
    public static function getFieldMapping(string $table, string $direction = 'local_to_api'): array
    {
        if (!isset(self::$schemaMappings[$table][$direction])) {
            return [];
        }

        return self::$schemaMappings[$table][$direction];
    }

    /**
     * Map outgoing message for API transmission
     */
    public static function mapOutgoingMessageForApi(array $messageData): array
    {
        $mapped = self::mapToApiSchema('outgoing_messages', $messageData);
        
        // Apply status mapping if status exists
        if (isset($mapped['delivery_status'])) {
            $mapped['delivery_status'] = self::mapStatusToApi('message_status', $mapped['delivery_status']);
        }

        return $mapped;
    }

    /**
     * Map API response to outgoing message format
     */
    public static function mapApiResponseToMessage(array $apiData): array
    {
        $mapped = self::mapToLocalSchema('outgoing_messages', $apiData);
        
        // Apply status mapping if status exists
        if (isset($mapped['status'])) {
            $mapped['status'] = self::mapStatusToLocal('message_status', $mapped['status']);
        }

        return $mapped;
    }

    /**
     * Map WhatsApp instance for API transmission
     */
    public static function mapInstanceForApi(array $instanceData): array
    {
        $mapped = self::mapToApiSchema('whatsapp_instances', $instanceData);
        
        // Apply session status mapping if exists
        if (isset($mapped['is_active'])) {
            $sessionStatus = $mapped['is_active'] ? 'active' : 'inactive';
            $mapped['session_status'] = self::mapStatusToApi('session_status', $sessionStatus);
        }

        return $mapped;
    }

    /**
     * Map events guest (contact) for API transmission
     */
    public static function mapContactForApi(array $contactData): array
    {
        return self::mapToApiSchema('events_guests', $contactData);
    }

    /**
     * Get reverse mapping for a field
     */
    public static function getReverseMappingForField(string $table, string $field): ?string
    {
        $mapping = self::getFieldMapping($table, 'local_to_api');
        return $mapping[$field] ?? null;
    }

    /**
     * Validate mapped data has required fields
     */
    public static function validateMappedData(array $mappedData, array $requiredFields): array
    {
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($mappedData[$field])) {
                $missingFields[] = $field;
            }
        }

        return $missingFields;
    }
}