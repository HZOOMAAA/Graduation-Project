<?php
require_once 'includes/connection.php';
require_once 'includes/auth_check.php'; // ensures user is logged in

header('Content-Type: application/json');

// ── Only accept POST ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
$customer_id    = $_SESSION['user_id'];

// ── Validate application belongs to this customer and is in waiting_docs ──────
$stmt = mysqli_prepare($connect,
    "SELECT application_id, status FROM applications WHERE application_id = ? AND customer_id = ?"
);
mysqli_stmt_bind_param($stmt, 'ii', $application_id, $customer_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$app = mysqli_fetch_assoc($res);

if (!$app) {
    echo json_encode(['success' => false, 'message' => 'Application not found.']);
    exit;
}

if ($app['status'] !== 'waiting_docs') {
    echo json_encode(['success' => false, 'message' => 'Documents have already been submitted for this application.']);
    exit;
}

// ── Upload directory ───────────────────────────────────────────────────────────
$uploadDir = __DIR__ . '/uploads/documents/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ── Expected file fields ───────────────────────────────────────────────────────
$fileFields = [
    'national_id'  => 'National ID',
    'car_license'  => 'Car License',
    'car_photos'   => 'Car Photos',
];

$uploadedDocs = [];
$errors       = [];

foreach ($fileFields as $fieldName => $docType) {
    // car_photos may be multi-file
    $isMulti = ($fieldName === 'car_photos');

    if (!isset($_FILES[$fieldName]) || empty($_FILES[$fieldName]['name'][0])) {
        if ($fieldName !== 'car_photos') {        // car_photos is optional
            // national_id and car_license are required
            $errors[] = "$docType is required.";
        }
        continue;
    }

    // Normalise to arrays for uniform handling
    if ($isMulti) {
        $names    = $_FILES[$fieldName]['name'];
        $tmpNames = $_FILES[$fieldName]['tmp_name'];
        $sizes    = $_FILES[$fieldName]['size'];
        $errors2  = $_FILES[$fieldName]['error'];
    } else {
        $names    = [$_FILES[$fieldName]['name']];
        $tmpNames = [$_FILES[$fieldName]['tmp_name']];
        $sizes    = [$_FILES[$fieldName]['size']];
        $errors2  = [$_FILES[$fieldName]['error']];
    }

    foreach ($names as $i => $origName) {
        if ($errors2[$i] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload error for $docType.";
            continue;
        }
        if ($sizes[$i] > 8 * 1024 * 1024) {        // 8 MB cap
            $errors[] = "$docType: file size must not exceed 8 MB.";
            continue;
        }

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'webp'];
        if (!in_array($ext, $allowedExts)) {
            $errors[] = "$docType: unsupported file type ($ext).";
            continue;
        }

        // Build a unique filename
        $newName   = 'app_' . $application_id . '_' . $fieldName . '_' . uniqid() . '.' . $ext;
        $destPath  = $uploadDir . $newName;
        $dbPath    = 'uploads/documents/' . $newName;

        if (!move_uploaded_file($tmpNames[$i], $destPath)) {
            $errors[] = "Failed to save $docType file.";
            continue;
        }

        $uploadedDocs[] = [
            'type' => $docType,
            'path' => $dbPath,
        ];
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

if (empty($uploadedDocs)) {
    echo json_encode(['success' => false, 'message' => 'Please upload at least your National ID and Car License.']);
    exit;
}

// ── Insert documents into the documents table ──────────────────────────────────
$insStmt = mysqli_prepare($connect,
    "INSERT INTO documents (application_id, doc_type, file_path, uploaded_at) VALUES (?, ?, ?, NOW())"
);

foreach ($uploadedDocs as $doc) {
    mysqli_stmt_bind_param($insStmt, 'iss', $application_id, $doc['type'], $doc['path']);
    mysqli_stmt_execute($insStmt);
}
mysqli_stmt_close($insStmt);

// ── Update application status → under_review ──────────────────────────────────
$updStmt = mysqli_prepare($connect,
    "UPDATE applications SET status = 'under_review' WHERE application_id = ? AND customer_id = ?"
);
mysqli_stmt_bind_param($updStmt, 'ii', $application_id, $customer_id);
$updated = mysqli_stmt_execute($updStmt);
mysqli_stmt_close($updStmt);

// ── Also store any notes in application_data ──────────────────────────────────
$notes = trim($_POST['notes'] ?? '');
if ($notes !== '') {
    $noteStmt = mysqli_prepare($connect,
        "UPDATE applications
            SET application_data = JSON_SET(COALESCE(application_data, '{}'), '$.customer_notes', ?)
          WHERE application_id = ?"
    );
    mysqli_stmt_bind_param($noteStmt, 'si', $notes, $application_id);
    mysqli_stmt_execute($noteStmt);
    mysqli_stmt_close($noteStmt);
}

if ($updated) {
    echo json_encode(['success' => true, 'message' => 'Documents submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update application status.']);
}
?>
