<?php

declare(strict_types=1);

/**
 * Basis aller GDP-Fehler: Anwendungen werten den Code aus, nicht den Text.
 */
class GDPException extends RuntimeException {
    public function __construct(
        private readonly GDPErrorCode $errorCode,
        string $message,
        private readonly array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function errorCode() :GDPErrorCode {
        return $this->errorCode;
    }

    public function retryClass() :GDPRetryClass {
        return $this->errorCode->retryClass();
    }

    /**
     * Diagnosekontext ohne Geheimnisse (Knoten, Instanz, Sequenz …).
     *
     * @return array<string, mixed>
     */
    public function context() :array {
        return $this->context;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray() :array {
        return [
            'code' => $this->errorCode->value,
            'message' => $this->getMessage(),
            'retry' => $this->retryClass()->value,
            'context' => $this->context,
            'at' => gmdate('c'),
        ];
    }
}

class GDPConnectionException extends GDPException {
}

class GDPAuthorizationException extends GDPException {
}

class GDPSchemaMismatchException extends GDPException {
}

class GDPIntegrityException extends GDPException {
}

class GDPPolicyViolationException extends GDPException {
}

class GDPStorageUnavailableException extends GDPException {
}

class GDPSnapshotException extends GDPException {
}

class GDPCheckpointMismatchException extends GDPException {
}

class GDPProtocolException extends GDPException {
}
