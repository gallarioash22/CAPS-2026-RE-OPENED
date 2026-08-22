<?php
date_default_timezone_set('Asia/Manila');

function get_pending_limit_minutes($conn) {
    $minutes = 180; // default 3 hours

    $sql = "SELECT setting_value
            FROM tbl_system_settings
            WHERE setting_key = 'pending_limit_minutes'
            LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $minutes = (int)$row['setting_value'];
    }

    if ($minutes < 1) {
        $minutes = 180;
    }

    return $minutes;
}

function auto_expire_pending_requests($conn) {
    $now = date('Y-m-d H:i:s');

    $sql = "UPDATE tbl_borrowed_records
            SET status = 'expired',
                expired_at = '$now'
            WHERE status = 'pending'
              AND request_expires_at IS NOT NULL
              AND request_expires_at <= '$now'";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        die("Auto-expire query failed: " . mysqli_error($conn));
    }
}

function get_remaining_seconds($expires_at) {
    if (empty($expires_at)) {
        return 0;
    }

    return strtotime($expires_at) - time();
}

function format_remaining_time($expires_at) {
    $seconds = get_remaining_seconds($expires_at);

    if ($seconds <= 0) {
        return "Expired";
    }

    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;

    if ($hours > 0) {
        return $hours . "h " . $minutes . "m";
    }

    if ($minutes > 0) {
        return $minutes . "m " . $secs . "s";
    }

    return $secs . "s";
}
?>