<?php

declare(strict_types=1);

/**
 * Schreibmodi einer GDP-Policy (Konzept Kapitel 12).
 */
enum GDPWriteMode :string {
    case CENTRAL_OWNER = 'central_owner';
    case NODE_OWNER = 'node_owner';
    case APPEND_ONLY = 'append_only';
    case LOCAL_ONLY = 'local_only';

    public function label() :string {
        return match ($this) {
            self::CENTRAL_OWNER => 'Zentral (nur Hub schreibt)',
            self::NODE_OWNER => 'Knoten-Eigentümer',
            self::APPEND_ONLY => 'Nur anhängen',
            self::LOCAL_ONLY => 'Nur lokal',
        };
    }

    public function replicated() :bool {
        return $this !== self::LOCAL_ONLY;
    }
}

/**
 * Rolle einer GBDB-Installation im GDP/1-Verbund.
 */
enum GDPNodeRole :string {
    case HUB = 'hub';
    case REPLICA = 'replica';
    case OBSERVER = 'observer';

    public function label() :string {
        return match ($this) {
            self::HUB => 'Hub',
            self::REPLICA => 'Replica',
            self::OBSERVER => 'Observer',
        };
    }
}

enum GDPConnectionState :string {
    case ONLINE = 'online';
    case OFFLINE = 'offline';
}

enum GDPSyncState :string {
    case INITIALIZING = 'initializing';
    case IDLE = 'idle';
    case PENDING = 'pending';
    case RESYNC = 'resync';
    case BLOCKED = 'blocked';
}

enum GDPStorageState :string {
    case GBDB = 'gbdb';
    case SQL = 'sql';
    case UNAVAILABLE = 'unavailable';
}

/**
 * Betriebspräferenz (Kapitel 26). Sie erteilt niemals zusätzliche Schreibrechte.
 */
enum GDPOperationPreference :string {
    case AUTO = 'auto';
    case FORCE_LOCAL = 'force_local';
    case PREFER_CENTRAL = 'prefer_central';
}

/**
 * Wie eine Anwendung oder ein Administrator auf einen Fehler reagieren soll.
 */
enum GDPRetryClass :string {
    case RETRY = 'retry';
    case CHECK = 'check';
    case MANUAL = 'manual';
}

/**
 * Maschinenlesbare Fehlercodes (Kapitel 30).
 */
enum GDPErrorCode :string {
    case CONNECTION_UNAVAILABLE = 'CONNECTION_UNAVAILABLE';
    case AUTHENTICATION_FAILED = 'AUTHENTICATION_FAILED';
    case AUTHORIZATION_DENIED = 'AUTHORIZATION_DENIED';
    case PAIRING_EXPIRED = 'PAIRING_EXPIRED';
    case POLICY_VIOLATION = 'POLICY_VIOLATION';
    case LEASE_EXPIRED = 'LEASE_EXPIRED';
    case SCHEMA_MISMATCH = 'SCHEMA_MISMATCH';
    case SEQUENCE_GAP = 'SEQUENCE_GAP';
    case VERSION_CONFLICT = 'VERSION_CONFLICT';
    case DUPLICATE_PAYLOAD_MISMATCH = 'DUPLICATE_PAYLOAD_MISMATCH';
    case CHECKPOINT_MISMATCH = 'CHECKPOINT_MISMATCH';
    case SNAPSHOT_REQUIRED = 'SNAPSHOT_REQUIRED';
    case SNAPSHOT_CHECKSUM_FAILED = 'SNAPSHOT_CHECKSUM_FAILED';
    case STORAGE_TRANSACTION_FAILED = 'STORAGE_TRANSACTION_FAILED';
    case STORAGE_STATE_UNCERTAIN = 'STORAGE_STATE_UNCERTAIN';
    case INTEGRITY_BLOCKED = 'INTEGRITY_BLOCKED';
    case RATE_LIMITED = 'RATE_LIMITED';
    case PROTOCOL_ERROR = 'PROTOCOL_ERROR';
    case PAYLOAD_TOO_LARGE = 'PAYLOAD_TOO_LARGE';
    case NOT_CONFIGURED = 'NOT_CONFIGURED';

    public function retryClass() :GDPRetryClass {
        return match ($this) {
            self::CONNECTION_UNAVAILABLE, self::RATE_LIMITED, self::STORAGE_TRANSACTION_FAILED => GDPRetryClass::RETRY,
            self::AUTHENTICATION_FAILED, self::AUTHORIZATION_DENIED, self::PAIRING_EXPIRED, self::POLICY_VIOLATION,
            self::LEASE_EXPIRED, self::PROTOCOL_ERROR, self::PAYLOAD_TOO_LARGE, self::NOT_CONFIGURED => GDPRetryClass::CHECK,
            default => GDPRetryClass::MANUAL,
        };
    }

    public function httpStatus() :int {
        return match ($this) {
            self::AUTHENTICATION_FAILED => 401,
            self::AUTHORIZATION_DENIED, self::POLICY_VIOLATION, self::LEASE_EXPIRED => 403,
            self::PAIRING_EXPIRED => 410,
            self::SCHEMA_MISMATCH, self::SEQUENCE_GAP, self::VERSION_CONFLICT, self::DUPLICATE_PAYLOAD_MISMATCH,
            self::CHECKPOINT_MISMATCH, self::SNAPSHOT_REQUIRED, self::INTEGRITY_BLOCKED => 409,
            self::PAYLOAD_TOO_LARGE => 413,
            self::PROTOCOL_ERROR => 400,
            self::RATE_LIMITED => 429,
            self::CONNECTION_UNAVAILABLE, self::NOT_CONFIGURED => 503,
            default => 500,
        };
    }
}
