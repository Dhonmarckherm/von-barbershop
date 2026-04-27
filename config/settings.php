<?php
/**
 * Settings Helper
 * Reads site-wide settings from the database.
 */
require_once __DIR__ . '/db.php';

function getSetting(string $key, string $default = ''): string {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}
