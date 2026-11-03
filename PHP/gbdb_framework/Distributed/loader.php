<?php

declare(strict_types=1);

/**
 * GDP-Modul (GBDB Distributed Protocol). Reine Deklarationen: Beim Laden
 * werden weder Dateien noch Netzwerk oder Datenbank angesprochen.
 */
require_once __DIR__ . '/GDPTypes.php';
require_once __DIR__ . '/GDPExceptions.php';
require_once __DIR__ . '/GDPConfig.php';
require_once __DIR__ . '/GDPCanonical.php';
require_once __DIR__ . '/GDPStore.php';
require_once __DIR__ . '/GDPJournal.php';
require_once __DIR__ . '/GDPRegistry.php';
require_once __DIR__ . '/GDPPolicyEngine.php';
require_once __DIR__ . '/GDPWriteInterceptor.php';
require_once __DIR__ . '/GDPSecurity.php';
require_once __DIR__ . '/GDPTransport.php';
require_once __DIR__ . '/GDPSnapshot.php';
require_once __DIR__ . '/GDPHub.php';
require_once __DIR__ . '/GDPSyncEngine.php';
require_once __DIR__ . '/GDP.php';
