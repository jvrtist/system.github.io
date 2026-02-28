<?php
// api/notifications.php - Notification API endpoint
require_once '../config.php';
require_login();

// Set JSON response header
header('Content-Type: application/json');

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    $conn = get_db_connection();

    switch ($method) {
        case 'GET':
            // Get notifications for current user
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $unread_only = isset($_GET['unread_only']) ? (bool)$_GET['unread_only'] : false;

            $query = "SELECT notification_id, title, message, type, priority, related_entity_type,
                             related_entity_id, action_url, is_read, created_at
                      FROM notifications
                      WHERE user_id = ? AND (expires_at IS NULL OR expires_at > NOW())";

            $params = [$_SESSION['user_id']];
            $types = "i";

            if ($unread_only) {
                $query .= " AND is_read = FALSE";
            }

            $query .= " ORDER BY priority DESC, created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";

            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $notifications = [];
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }

            // Get unread count
            $stmt_count = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = FALSE AND (expires_at IS NULL OR expires_at > NOW())");
            $stmt_count->bind_param("i", $_SESSION['user_id']);
            $stmt_count->execute();
            $unread_count = $stmt_count->get_result()->fetch_assoc()['unread_count'];

            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $unread_count,
                'total' => count($notifications)
            ]);
            break;

        case 'POST':
            // Create a new notification (admin only)
            if (!user_has_role('admin')) {
                throw new Exception('Unauthorized');
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['user_id']) || !isset($data['title']) || !isset($data['message'])) {
                throw new Exception('Missing required fields');
            }

            $stmt = $conn->prepare("INSERT INTO notifications
                (user_id, title, message, type, priority, related_entity_type, related_entity_id, action_url, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $expires_at = isset($data['expires_at']) ? $data['expires_at'] : null;

            $stmt->bind_param("isssssiss",
                $data['user_id'],
                $data['title'],
                $data['message'],
                $data['type'] ?? 'info',
                $data['priority'] ?? 'medium',
                $data['related_entity_type'] ?? null,
                $data['related_entity_id'] ?? null,
                $data['action_url'] ?? null,
                $expires_at
            );

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'notification_id' => $conn->insert_id,
                    'message' => 'Notification created successfully'
                ]);
            } else {
                throw new Exception('Failed to create notification');
            }
            break;

        case 'PUT':
            // Mark notifications as read
            $data = json_decode(file_get_contents('php://input'), true);

            if (isset($data['notification_ids']) && is_array($data['notification_ids'])) {
                // Mark specific notifications as read
                $placeholders = str_repeat('?,', count($data['notification_ids']) - 1) . '?';
                $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE notification_id IN ($placeholders) AND user_id = ?");
                $params = array_merge($data['notification_ids'], [$_SESSION['user_id']]);
                $types = str_repeat('i', count($params));
                $stmt->bind_param($types, ...$params);
            } elseif (isset($data['mark_all_read']) && $data['mark_all_read']) {
                // Mark all notifications as read for current user
                $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
                $stmt->bind_param("i", $_SESSION['user_id']);
            } else {
                throw new Exception('Invalid request parameters');
            }

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notifications marked as read'
                ]);
            } else {
                throw new Exception('Failed to update notifications');
            }
            break;

        case 'DELETE':
            // Delete a notification (admin only or own notifications)
            $notification_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

            if (!$notification_id) {
                throw new Exception('Notification ID required');
            }

            // Check if user owns the notification or is admin
            $stmt = $conn->prepare("SELECT user_id FROM notifications WHERE notification_id = ?");
            $stmt->bind_param("i", $notification_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception('Notification not found');
            }

            $notification = $result->fetch_assoc();

            if ($notification['user_id'] !== $_SESSION['user_id'] && !user_has_role('admin')) {
                throw new Exception('Unauthorized');
            }

            $stmt_delete = $conn->prepare("DELETE FROM notifications WHERE notification_id = ?");
            $stmt_delete->bind_param("i", $notification_id);

            if ($stmt_delete->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete notification');
            }
            break;

        default:
            throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
