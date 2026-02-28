<?php
/**
 * ISS Investigations - Common Utility Functions
 * Shared utility functions used across the application.
 */

/**
 * Sanitize user input
 */
function sanitize_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }
    return trim(strip_tags($input));
}

/**
 * Sanitize content that should allow special characters but prevent XSS
 */
function sanitize_content($content) {
    // Allow basic formatting but strip dangerous tags
    $allowed_tags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><code><pre>';
    return trim(strip_tags($content, $allowed_tags));
}

/**
 * Format currency
 */
function format_currency($amount, $symbol = 'R') {
    return $symbol . number_format($amount, 2);
}

/**
 * Get status badge HTML
 */
function get_status_badge($status, $type = 'case') {
    $colors = [
        'case' => [
            'new' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
            'open' => 'bg-green-500/10 text-green-400 border-green-500/20',
            'in progress' => 'bg-primary/10 text-primary border-primary/20',
            'urgent' => 'bg-red-500/10 text-red-500 border-red-500/20',
            'closed' => 'bg-slate-800 text-slate-400 border-slate-700',
            'resolved' => 'bg-slate-800 text-slate-400 border-slate-700',
            'archived' => 'bg-slate-800 text-slate-400 border-slate-700',
        ],
        'client' => [
            'Active' => 'bg-green-500/20 text-green-300 border border-green-500/30',
            'Pending Activation' => 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/30',
            'Disabled' => 'bg-red-500/20 text-red-300 border border-red-500/30',
        ],
        'invoice' => [
            'draft' => 'bg-slate-700 text-slate-300 border-slate-500',
            'sent' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
            'paid' => 'bg-green-500/20 text-green-300 border-green-500/30',
            'partially paid' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
            'overdue' => 'bg-red-500/20 text-red-300 border-red-500/30',
            'void' => 'bg-gray-700 text-gray-400 border-gray-600 opacity-70',
            'cancelled' => 'bg-gray-700 text-gray-400 border-gray-600 opacity-70',
        ],
        'task' => [
            'Pending' => 'bg-slate-500/20 text-slate-300 border-slate-500/30',
            'In Progress' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
            'Completed' => 'bg-green-500/20 text-green-300 border-green-500/30',
            'Deferred' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
        ]
    ];

    $style = $colors[$type][strtolower($status)] ?? 'bg-slate-800 text-slate-400 border-slate-700';
    $safe_status = htmlspecialchars($status);
    return "<span class='px-2 py-0.5 rounded-full border text-[10px] font-black uppercase tracking-tighter $style'>$safe_status</span>";
}

/**
 * Generate random case number
 */
function generate_case_number() {
    return "ISS-" . date('Y') . "-" . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Validate date format
 */
function is_valid_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Get file extension
 */
function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if file type is allowed
 */
function is_allowed_file_type($filename, $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']) {
    $ext = get_file_extension($filename);
    return in_array($ext, $allowed_types);
}

/**
 * Generate secure filename
 */
function generate_secure_filename($original_name) {
    $ext = get_file_extension($original_name);
    $random_name = bin2hex(random_bytes(16));
    return $random_name . '.' . $ext;
}

/**
 * Get client initials
 */
function get_client_initials($first_name, $last_name) {
    return strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
}

/**
 * Calculate days between dates
 */
function days_between_dates($date1, $date2) {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    return $interval->days;
}

/**
 * Check if date is overdue
 */
function is_overdue($date) {
    return strtotime($date) < strtotime('today');
}

/**
 * Get relative time string
 */
function get_relative_time($timestamp) {
    $now = time();
    $time = strtotime($timestamp);
    $diff = $now - $time;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M j, Y', $time);
    }
}

/**
 * Validate email format
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number format
 */
function is_valid_phone($phone) {
    return preg_match('/^[0-9\s\+\-\(\)]+$/', $phone);
}

/**
 * Generate random string
 */
function generate_random_string($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Clean filename for storage
 */
function clean_filename($filename) {
    return preg_replace('/[^a-zA-Z0-9\._-]/', '', $filename);
}

/**
 * Get pagination links
 */
function get_pagination_links($current_page, $total_pages, $base_url, $params = []) {
    $links = [];

    // Previous link
    if ($current_page > 1) {
        $params['page'] = $current_page - 1;
        $links[] = [
            'url' => $base_url . '?' . http_build_query($params),
            'text' => 'Previous',
            'class' => 'previous'
        ];
    }

    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);

    // First page
    if ($start > 1) {
        $params['page'] = 1;
        $links[] = [
            'url' => $base_url . '?' . http_build_query($params),
            'text' => '1',
            'class' => 'page'
        ];
        if ($start > 2) {
            $links[] = [
                'url' => null,
                'text' => '...',
                'class' => 'ellipsis'
            ];
        }
    }

    // Page range
    for ($i = $start; $i <= $end; $i++) {
        $params['page'] = $i;
        $links[] = [
            'url' => $base_url . '?' . http_build_query($params),
            'text' => $i,
            'class' => $i === $current_page ? 'current' : 'page'
        ];
    }

    // Last page
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $links[] = [
                'url' => null,
                'text' => '...',
                'class' => 'ellipsis'
            ];
        }
        $params['page'] = $total_pages;
        $links[] = [
            'url' => $base_url . '?' . http_build_query($params),
            'text' => $total_pages,
            'class' => 'page'
        ];
    }

    // Next link
    if ($current_page < $total_pages) {
        $params['page'] = $current_page + 1;
        $links[] = [
            'url' => $base_url . '?' . http_build_query($params),
            'text' => 'Next',
            'class' => 'next'
        ];
    }

    return $links;
}

/**
 * Send JSON response
 */
function json_response($data, $status_code = 200) {
    header('Content-Type: application/json');
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}

/**
 * Handle API errors
 */
function api_error($message, $status_code = 400) {
    json_response(['success' => false, 'message' => $message], $status_code);
}

/**
 * Handle API success
 */
function api_success($data = null, $message = 'Success') {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    json_response($response);
}
