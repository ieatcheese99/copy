<?php
// Booking notification helper functions

function sendBookingNotification($booking_id, $action, $admin_name) {
    global $conn;
    
    // Get booking details
    $stmt = $conn->prepare("
        SELECT b.*, u.username, u.email,
               COALESCE(r.name, f.name) as item_name,
               CASE WHEN b.ruangan_id IS NOT NULL THEN 'Room' ELSE 'Facility' END as item_type
        FROM bookings b 
        LEFT JOIN users u ON b.user_id = u.id 
        LEFT JOIN ruang r ON b.ruangan_id = r.id 
        LEFT JOIN facilities f ON b.facility_id = f.id 
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    
    if (!$booking) return false;
    
    // Create notification message
    $message = '';
    $status_class = '';
    
    switch($action) {
        case 'approved':
            $message = "Your booking for {$booking['item_name']} on " . date('d M Y', strtotime($booking['booking_date'])) . " has been approved by {$admin_name}.";
            $status_class = 'success';
            break;
        case 'rejected':
            $message = "Your booking for {$booking['item_name']} on " . date('d M Y', strtotime($booking['booking_date'])) . " has been rejected by {$admin_name}.";
            $status_class = 'danger';
            break;
        case 'cancelled':
            $message = "Your booking for {$booking['item_name']} on " . date('d M Y', strtotime($booking['booking_date'])) . " has been cancelled by {$admin_name}.";
            $status_class = 'warning';
            break;
    }
    
    // Store notification in database (if you have a notifications table)
    // For now, we'll just return the message for display
    return [
        'message' => $message,
        'status_class' => $status_class,
        'booking' => $booking
    ];
}

function getBookingConflicts($booking_date, $start_time, $end_time, $facility_id = null, $ruangan_id = null, $exclude_booking_id = null) {
    global $conn;
    
    $where_conditions = [];
    $params = [];
    $param_types = '';
    
    // Base conditions
    $where_conditions[] = "booking_date = ?";
    $params[] = $booking_date;
    $param_types .= 's';
    
    $where_conditions[] = "status IN ('pending', 'approved')";
    
    // Time overlap condition
    $where_conditions[] = "((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))";
    $params = array_merge($params, [$start_time, $start_time, $end_time, $end_time, $start_time, $end_time]);
    $param_types .= 'ssssss';
    
    // Resource condition
    if ($facility_id) {
        $where_conditions[] = "facility_id = ?";
        $params[] = $facility_id;
        $param_types .= 'i';
    } elseif ($ruangan_id) {
        $where_conditions[] = "ruangan_id = ?";
        $params[] = $ruangan_id;
        $param_types .= 'i';
    }
    
    // Exclude current booking if editing
    if ($exclude_booking_id) {
        $where_conditions[] = "id != ?";
        $params[] = $exclude_booking_id;
        $param_types .= 'i';
    }
    
    $query = "SELECT COUNT(*) as count FROM bookings WHERE " . implode(' AND ', $where_conditions);
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['count'] > 0;
}

function getBookingStatistics($date_from = null, $date_to = null) {
    global $conn;
    
    $where_conditions = [];
    $params = [];
    $param_types = '';
    
    if ($date_from) {
        $where_conditions[] = "booking_date >= ?";
        $params[] = $date_from;
        $param_types .= 's';
    }
    
    if ($date_to) {
        $where_conditions[] = "booking_date <= ?";
        $params[] = $date_to;
        $param_types .= 's';
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    $query = "
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_bookings,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_bookings,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
            SUM(CASE WHEN ruangan_id IS NOT NULL THEN 1 ELSE 0 END) as room_bookings,
            SUM(CASE WHEN facility_id IS NOT NULL THEN 1 ELSE 0 END) as facility_bookings
        FROM bookings 
        $where_clause
    ";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($param_types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
    } else {
        $result = $conn->query($query)->fetch_assoc();
    }
    
    return $result;
}

function validateBookingTime($start_time, $end_time, $booking_date) {
    $errors = [];
    
    // Check if booking date is not in the past
    if (strtotime($booking_date) < strtotime(date('Y-m-d'))) {
        $errors[] = "Booking date cannot be in the past.";
    }
    
    // Check if start time is before end time
    if (strtotime($start_time) >= strtotime($end_time)) {
        $errors[] = "Start time must be before end time.";
    }
    
    // Check minimum booking duration (e.g., 30 minutes)
    $duration = strtotime($end_time) - strtotime($start_time);
    if ($duration < 1800) { // 30 minutes
        $errors[] = "Minimum booking duration is 30 minutes.";
    }
    
    // Check maximum booking duration (e.g., 8 hours)
    if ($duration > 28800) { // 8 hours
        $errors[] = "Maximum booking duration is 8 hours.";
    }
    
    // Check business hours (e.g., 8 AM to 10 PM)
    $start_hour = date('H', strtotime($start_time));
    $end_hour = date('H', strtotime($end_time));
    
    if ($start_hour < 8 || $end_hour > 22) {
        $errors[] = "Bookings are only allowed between 8:00 AM and 10:00 PM.";
    }
    
    return $errors;
}
?>
