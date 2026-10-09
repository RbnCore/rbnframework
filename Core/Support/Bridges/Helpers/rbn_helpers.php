<?php

/**
 * RBN Framework - Global Helper Hub 🎻🛰️⚓⚖️✨
 * 
 * RBN Framework: This file orchestrates specialized helper modules from the Global cluster.
 * It ensures architectural scalpability and a non-bloated helper registry.
 */

$helperCluster = [
    'system_helpers.php',
    'http_helpers.php',
    'support_helpers.php',
    'project_helpers.php'
];

foreach ($helperCluster as $file) {
    require_once __DIR__ . '/Global/' . $file;
}
