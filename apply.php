<?php
header('Content-Type: application/json');
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $age = $_POST['age'];
    $birthday = $_POST['birthday'];
    $address = $_POST['address'];
    $signature = $_POST['signature'];
    $rsa_signature = $_POST['rsa_signature'];
    $public_key = $_POST['public_key'];
    $private_key = $_POST['private_key'];

    $id_picture = $_FILES['id_picture']['name'];
    $resume = $_FILES['resume']['name'];

    // Ensure uploads directory exists
    $uploadDir = "../uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Move uploaded files
    move_uploaded_file($_FILES['id_picture']['tmp_name'], $uploadDir . $id_picture);
    move_uploaded_file($_FILES['resume']['tmp_name'], $uploadDir . $resume);

    // Insert user data into the users table
    $stmt = $conn->prepare("INSERT INTO users (name, age, birthday, address, id_picture, resume, public_key, private_key) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissssss", $name, $age, $birthday, $address, $id_picture, $resume, $public_key, $private_key);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;

        // Insert signature data into the signed_documents table
        $stmt = $conn->prepare("INSERT INTO signed_documents (user_id, signature, rsa_signature) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $signature, $rsa_signature);

        if ($stmt->execute()) {
            // Insert audit log
            $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, 'Applied for job')");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            echo json_encode(['message' => 'Application submitted successfully.']);
        } else {
            echo json_encode(['message' => 'Error: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['message' => 'Error: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['message' => 'Invalid request method']);
}

$conn->close();
?>
