<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check login
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Get current user data
    $user_data = getUserData($user_id, $conn);
    
    // Handle photo upload
    if (isset($_POST['update_photo']) && isset($_FILES['profile_photo'])) {
        $file = $_FILES['profile_photo'];
        
        // Validate file
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($file['type'], $allowed_types)) {
                header("Location: ../pages/profile.php?error=photo_type");
                exit;
            }
            
            if ($file['size'] > $max_size) {
                header("Location: ../pages/profile.php?error=photo_size");
                exit;
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
            $upload_path = UPLOAD_PATH . 'profiles/';
            
            // Create directory if not exists
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            
            // Upload file
            if (move_uploaded_file($file['tmp_name'], $upload_path . $filename)) {
                // Delete old photo if exists
                if (!empty($user_data['profile_photo']) && file_exists($upload_path . $user_data['profile_photo'])) {
                    unlink($upload_path . $user_data['profile_photo']);
                }
                
                // Update database
                $filename = mysqli_real_escape_string($conn, $filename);
                $query = "UPDATE users SET profile_photo='$filename' WHERE id='$user_id'";
                
                if (mysqli_query($conn, $query)) {
                    header("Location: ../pages/profile.php?success=photo");
                } else {
                    header("Location: ../pages/profile.php?error=update_failed");
                }
            } else {
                header("Location: ../pages/profile.php?error=upload_failed");
            }
            exit;
        }
    }

    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $username = clean($_POST['username']);
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        // Validate username
        if (strlen($username) < 4) {
            header("Location: ../pages/profile.php?error=username_length");
            exit;
        }
        
        // Check if username exists (exclude current user)
        $query = "SELECT id FROM users WHERE username='$username' AND id != '$user_id'";
        if (mysqli_num_rows(mysqli_query($conn, $query)) > 0) {
            header("Location: ../pages/profile.php?error=username_exists");
            exit;
        }
        
        // Build query
        $query = "UPDATE users SET username='$username'";
        
        // Update password if provided
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                header("Location: ../pages/profile.php?error=password_mismatch");
                exit;
            }
            if (strlen($new_password) < 6) {
                header("Location: ../pages/profile.php?error=password_length");
                exit;
            }
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query .= ", password='$hashed_password'";
        }

        // Handle signature upload for admin users
        if ($_SESSION['role'] === 'admin' && isset($_FILES['signature']) && !empty($_FILES['signature']['name'])) {
            $signature = $_FILES['signature'];
            
            // Validate signature file
            if ($signature['error'] === UPLOAD_ERR_OK) {
                if ($signature['type'] !== 'image/png') {
                    header("Location: ../pages/profile.php?error=signature&message=" . urlencode("Tanda tangan harus berformat PNG"));
                    exit;
                }
                if ($signature['size'] > 500000) { // 500KB
                    header("Location: ../pages/profile.php?error=signature&message=" . urlencode("Ukuran file tanda tangan maksimal 500KB"));
                    exit;
                }

                // Generate signature filename
                $signature_filename = 'signature_' . $user_id . '_' . time() . '.png';
                
                // Create signatures directory if not exists
                if (!file_exists(SIGNATURE_PATH)) {
                    mkdir(SIGNATURE_PATH, 0777, true);
                }

                // Upload signature file
                if (move_uploaded_file($signature['tmp_name'], SIGNATURE_PATH . $signature_filename)) {
                    // Delete old signature if exists
                    if (!empty($user_data['signature_path']) && file_exists(SIGNATURE_PATH . $user_data['signature_path'])) {
                        unlink(SIGNATURE_PATH . $user_data['signature_path']);
                    }
                    $query .= ", signature_path='$signature_filename'";
                } else {
                    header("Location: ../pages/profile.php?error=signature&message=" . urlencode("Gagal upload tanda tangan"));
                    exit;
                }
            }
        }
        
        // Complete and execute the update query
        $query .= " WHERE id='$user_id'";
        if (mysqli_query($conn, $query)) {
            $_SESSION['username'] = $username;
            header("Location: ../pages/profile.php?success=updated");
        } else {
            header("Location: ../pages/profile.php?error=update_failed");
        }
        exit;
    }
}

// If no valid POST action, redirect back to profile
header("Location: ../pages/profile.php");
exit;