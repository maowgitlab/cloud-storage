<?php
function log_activity($conn, $user_id, $action, $description) {
    $action = $conn->real_escape_string($action);
    $description = $conn->real_escape_string($description);
    $sql = "INSERT INTO activity_logs (user_id, action, description) VALUES ($user_id, '$action', '$description')";
    $conn->query($sql);
}
?>