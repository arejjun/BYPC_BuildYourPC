<?php
session_start();
include "configuration/db.php";

if (!isset($_SESSION['user_id'])) { exit("Unauthorized"); }

$order_id = intval($_POST['order_id']);
$user_id = $_SESSION['user_id'];
$reason = trim($_POST['reason']);

$stmt = $conn->prepare("INSERT INTO Order_Requests (order_id, customer_id, request_type, message) VALUES (?, ?, 'return', ?)");
$stmt->bind_param("iis", $order_id, $user_id, $reason);

if ($stmt->execute()) {
    header("Location: ordertracker.php?order_id=$order_id&msg=Return Requested");
} else {
    echo "Error requesting return.";
}
?>
