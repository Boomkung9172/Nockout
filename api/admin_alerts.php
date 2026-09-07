<?php
/**
 * Admin Live Alerts API
 * Checks for recent bookings and unread customer messages
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = getDb();

// Unread messages count
$msgStmt = $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'");
$unreadMessages = (int)$msgStmt->fetchColumn();

// Pending payment / recent bookings today
$today = date('Y-m-d');
$bookStmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND status = 'pending_payment'");
$bookStmt->execute([$today]);
$pendingBookings = (int)$bookStmt->fetchColumn();

// Total confirmed today
$confStmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND status = 'confirmed'");
$confStmt->execute([$today]);
$confirmedToday = (int)$confStmt->fetchColumn();

echo json_encode([
    'success' => true,
    'unread_messages' => $unreadMessages,
    'pending_bookings' => $pendingBookings,
    'confirmed_today' => $confirmedToday,
    'timestamp' => time()
]);
