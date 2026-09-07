<?php
/**
 * API Cutoff Check
 * Can be triggered via Cron Job or Frontend AJAX
 * Checks and marks no-show bookings after 20:00 cutoff time
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$affected = runCutoffCheck();

echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'cutoff_time' => '20:00:00',
    'affected_bookings' => $affected,
    'message' => "Cutoff check complete. {$affected} bookings marked as no-show."
], JSON_UNESCAPED_UNICODE);
