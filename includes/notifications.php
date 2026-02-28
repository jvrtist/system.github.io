<?php
// includes/notifications.php - Notification helper functions

/**
 * Create a notification for a user
 */
function create_notification($user_id, $title, $message, $type = 'info', $priority = 'medium', $related_entity_type = null, $related_entity_id = null, $action_url = null, $expires_days = null) {
    global $conn;

    if (!$conn) return false;

    $expires_at = null;
    if ($expires_days) {
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expires_days} days"));
    }

    $stmt = $conn->prepare("INSERT INTO notifications
        (user_id, title, message, type, priority, related_entity_type, related_entity_id, action_url, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("isssssiss", $user_id, $title, $message, $type, $priority, $related_entity_type, $related_entity_id, $action_url, $expires_at);

    return $stmt->execute();
}

/**
 * Create notification for overdue tasks
 */
function notify_overdue_tasks() {
    global $conn;

    if (!$conn) return;

    // Find all users with overdue tasks
    $stmt = $conn->prepare("
        SELECT DISTINCT u.user_id, u.full_name, COUNT(t.task_id) as overdue_count
        FROM users u
        JOIN tasks t ON u.user_id = t.assigned_to_user_id
        WHERE t.status != 'Completed' AND t.due_date < CURDATE()
        GROUP BY u.user_id, u.full_name
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    while ($user = $result->fetch_assoc()) {
        $title = "Overdue Tasks Alert";
        $message = "You have {$user['overdue_count']} overdue task(s) that need immediate attention.";

        create_notification(
            $user['user_id'],
            $title,
            $message,
            'warning',
            'high',
            'task',
            null,
            BASE_URL . 'tasks/',
            7 // Expires in 7 days
        );
    }
}

/**
 * Create notification for new client messages
 */
function notify_new_messages() {
    global $conn;

    if (!$conn) return;

    // Find unread messages for each user
    $stmt = $conn->prepare("
        SELECT DISTINCT u.user_id, COUNT(cm.message_id) as unread_count
        FROM users u
        JOIN cases c ON u.user_id = c.assigned_to_user_id
        JOIN client_messages cm ON c.case_id = cm.case_id
        WHERE cm.sent_by_client = TRUE AND cm.is_read_by_user = FALSE
        GROUP BY u.user_id
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    while ($user = $result->fetch_assoc()) {
        $title = "New Client Messages";
        $message = "You have {$user['unread_count']} unread message(s) from clients.";

        create_notification(
            $user['user_id'],
            $title,
            $message,
            'info',
            'medium',
            'message',
            null,
            BASE_URL . 'cases/',
            3 // Expires in 3 days
        );
    }
}

/**
 * Create notification for case status changes
 */
function notify_case_updates($case_id, $old_status, $new_status) {
    global $conn;

    if (!$conn || $old_status === $new_status) return;

    // Notify the assigned investigator
    $stmt = $conn->prepare("
        SELECT c.case_number, c.title, u.user_id, u.full_name
        FROM cases c
        JOIN users u ON c.assigned_to_user_id = u.user_id
        WHERE c.case_id = ?
    ");

    $stmt->bind_param("i", $case_id);
    $stmt->execute();
    $case = $stmt->get_result()->fetch_assoc();

    if ($case) {
        $title = "Case Status Updated";
        $message = "Case #{$case['case_number']} - {$case['title']} status changed from {$old_status} to {$new_status}.";

        create_notification(
            $case['user_id'],
            $title,
            $message,
            'info',
            'medium',
            'case',
            $case_id,
            BASE_URL . "cases/view_case.php?id={$case_id}",
            7 // Expires in 7 days
        );
    }
}

/**
 * Create notification for system alerts (admin only)
 */
function notify_system_alert($title, $message, $priority = 'medium') {
    global $conn;

    if (!$conn) return;

    // Notify all admin users
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE role = 'admin'");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($user = $result->fetch_assoc()) {
        create_notification(
            $user['user_id'],
            $title,
            $message,
            'warning',
            $priority,
            'system',
            null,
            BASE_URL . 'dashboard.php',
            30 // Expires in 30 days
        );
    }
}

/**
 * Clean up expired notifications
 */
function cleanup_expired_notifications() {
    global $conn;

    if (!$conn) return;

    $conn->query("DELETE FROM notifications WHERE expires_at IS NOT NULL AND expires_at < NOW()");
}

/**
 * Get notification preferences for a user
 */
function get_notification_preferences($user_id) {
    global $conn;

    if (!$conn) return ['overdue_tasks' => true, 'new_messages' => true, 'case_updates' => true, 'system_alerts' => true];

    $stmt = $conn->prepare("SELECT notification_preferences FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return json_decode($row['notification_preferences'], true);
    }

    return ['overdue_tasks' => true, 'new_messages' => true, 'case_updates' => true, 'system_alerts' => true];
}

/**
 * Update notification preferences for a user
 */
function update_notification_preferences($user_id, $preferences) {
    global $conn;

    if (!$conn) return false;

    $prefs_json = json_encode($preferences);

    $stmt = $conn->prepare("UPDATE users SET notification_preferences = ? WHERE user_id = ?");
    $stmt->bind_param("si", $prefs_json, $user_id);

    return $stmt->execute();
}

/**
 * Process automated notifications (call this periodically)
 */
function process_automated_notifications() {
    // Clean up expired notifications
    cleanup_expired_notifications();

    // Check for overdue tasks
    notify_overdue_tasks();

    // Check for new messages
    notify_new_messages();
}
?>
