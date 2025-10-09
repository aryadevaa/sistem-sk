<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $sk_id = clean($_POST['sk_id']);
    $comment_text = clean($_POST['comment_text']);
    $admin_id = $_SESSION['user_id'];
    $admin_username = $_SESSION['username'];
    
    // Insert comment
    $query = "INSERT INTO comments (sk_id, admin_id, admin_username, comment, created_at) 
              VALUES ('$sk_id', '$admin_id', '$admin_username', '$comment_text', NOW())";
    
    if (mysqli_query($conn, $query)) {
        header("Location: ../pages/view_sk.php?id=$sk_id&success=comment");
    } else {
        header("Location: ../pages/view_sk.php?id=$sk_id&error=comment");
    }
} else {
    header("Location: ../pages/data_sk.php");
}
exit;
?>