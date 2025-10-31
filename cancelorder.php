<?php
session_start();
include "configuration/db.php";

if (!isset($_SESSION['user_id'])) { exit("Unauthorized"); }

$order_id = intval($_POST['order_id']);
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE orders SET status='cancelled' WHERE order_id=? AND customer_id=? AND status IN ('pending','confirmed')");
$stmt->bind_param("ii", $order_id, $user_id);

if ($stmt->execute()) {
    header("Location: ordertracker.php?order_id=$order_id&msg=Order Cancelled");
} else {
    echo "Error cancelling order.";
}
?>
