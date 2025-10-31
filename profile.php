<?php
session_start();
include "configuration/db.php";

// ✅ Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: SignupandLogin/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

/* --------------------------
   FETCH USER DETAILS
-------------------------- */
$stmt = $conn->prepare("SELECT name, email, phone_number FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

/* --------------------------
   UPDATE USER PROFILE
-------------------------- */
if (isset($_POST['update_profile'])) {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone_number = ? WHERE user_id = ?");
    $stmt->bind_param("sssi", $name, $email, $phone, $user_id);

    if ($stmt->execute()) {
        $_SESSION['username'] = $name;
        // ✅ Redirect to Mainpage after success
        header("Location: Mainpage.php?msg=" . urlencode("✅ Profile updated successfully!"));
        exit();
    } else {
        $message = "❌ Error updating profile: " . $stmt->error;
    }
}

/* --------------------------
   UPDATE PASSWORD
-------------------------- */
if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch existing password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $db_user = $result->fetch_assoc();

    if (!password_verify($current_password, $db_user['password'])) {
        $message = "❌ Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $message = "❌ New passwords do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        if ($stmt->execute()) {
            // ✅ Redirect to Mainpage after success
            header("Location: Mainpage.php?msg=" . urlencode("✅ Password updated successfully!"));
            exit();
        } else {
            $message = "❌ Error updating password: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 20px; }
        .container { max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); }
        h2 { color: #333; text-align: center; }
        form { margin-bottom: 20px; }
        label { display: block; margin: 10px 0 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
        button { margin-top: 15px; padding: 10px 20px; border: none; border-radius: 5px; background: #007bff; color: #fff; cursor: pointer; }
        button:hover { background: #0056b3; }
        .msg { margin: 15px 0; padding: 10px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <?php include 'components/navigation.php'; ?>
<div class="container" style="margin-top: 100px;">
    <h2>User Profile</h2>

    <!-- ✅ Success/Error Message -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="msg success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php elseif ($message): ?>
        <div class="msg error"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- ✅ Profile Edit Form -->
    <form method="POST">
        <label for="name">Full Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>

        <label for="email">Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

        <label for="phone_number">Phone Number</label>
        <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>

        <button type="submit" name="update_profile">Save Changes</button>
    </form>

    <!-- ✅ Password Update Form -->
    <h3>Change Password</h3>
    <form method="POST">
        <label for="current_password">Current Password</label>
        <input type="password" name="current_password" required>

        <label for="new_password">New Password</label>
        <input type="password" name="new_password" required>

        <label for="confirm_password">Confirm New Password</label>
        <input type="password" name="confirm_password" required>

        <button type="submit" name="update_password">Update Password</button>
    </form>
</div>
</body>
</html>
