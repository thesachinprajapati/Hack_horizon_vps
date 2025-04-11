<?php
// Database credentials
$servername = "localhost";
$username = "root";
$password = "sachin";
$dbname = "vps";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form data
    $aadhaar = $_POST['aadhaar'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $otp = $_POST['otp'] ?? '';
    $category = $_POST['category'] ?? '';
    $description = $_POST['description'] ?? '';
    $location = $_POST['location'] ?? '';
    $evidenceList = $_POST['evidenceList'] ?? ''; // Get base64 encoded evidence list

    // Process evidence data (decode and save files)
    $filePaths = [];
    if ($evidenceList) {
        $evidenceArray = json_decode($evidenceList, true); // Decode as associative array

        if (is_array($evidenceArray)) {
            foreach ($evidenceArray as $index => $base64Data) {
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Data));
                $fileName = "evidence_" . time() . "_" . $index . ".png"; // Generate unique filename
                $filePath = "uploads/" . $fileName; // Path to save file

                // Create the 'uploads' directory if it doesn't exist
                if (!is_dir("uploads")) {
                    mkdir("uploads", 0755, true); // Create directory with appropriate permissions
                }

                if (file_put_contents($filePath, $imageData)) {
                    $filePaths[] = $filePath; // Store file paths
                } else {
                    // Handle file save error
                    error_log("Failed to save evidence file: " . $filePath);
                    echo "Error saving evidence file. Complaint not saved.";
                    $filePaths = []; //clear filepaths so that they are not saved to the database.
                    break; //stop saving files and database entry.
                }
            }
        }
    }
    $evidencePathsString = implode(",", $filePaths);

    // SQL query to insert data
    $sql = "INSERT INTO complaints (user_id, complaint_description, status, assigned_officer, evidence_path, aadhaar_number, phone_number, otp_value, category_crime, incident_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $user_id = 1; // Replace with actual user ID
        $status = "Pending";
        $assigned_officer = NULL;

        $stmt->bind_param("isssssssss", $user_id, $description, $status, $assigned_officer, $evidencePathsString, $aadhaar, $phone, $otp, $category, $location);

        if ($stmt->execute()) {
            echo "Complaint registered successfully!";
        } else {
            echo "Error: " . $stmt->error;
            error_log("Database Error: " . $stmt->error . " SQL: " . $sql); // Log the error
        }

        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
        error_log("Statement Preparation Error: " . $conn->error . " SQL: " . $sql);
    }
} else {
    echo "This script only accepts POST requests.";
}

$conn->close();
?>