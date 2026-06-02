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
$is_session_flow = ($application_id === 0);

if ($is_session_flow) {
    // Session-based flow
    $appData = $_SESSION['temp_application_data'] ?? null;
    if ($appData) {
        if (empty($appData['client_name']) && isset($_SESSION['name'])) {
            $appData['client_name'] = $_SESSION['name'];
        }
        if (empty($appData['client_phone']) && isset($_SESSION['phone'])) {
            $appData['client_phone'] = $_SESSION['phone'];
        }
        $_SESSION['temp_application_data'] = $appData;
    }
    $temp_plan_id = $_SESSION['temp_plan_id'] ?? 0;
    $category_id = $_SESSION['temp_category_id'] ?? 1;
    if (!$appData || $temp_plan_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'No active application session found. Please start over.']);
        exit;
    }
} else {
    // ── Validate database application belongs to this customer and is in waiting_docs ──────
    $stmt = mysqli_prepare($connect,
        "SELECT application_id, status, category_id FROM applications WHERE application_id = ? AND customer_id = ?"
    );
    mysqli_stmt_bind_param($stmt, 'ii', $application_id, $customer_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $app = mysqli_fetch_assoc($res);

    if (!$app) {
        echo json_encode(['success' => false, 'message' => 'Application not found.']);
        exit;
    }

    if (!in_array($app['status'], ['waiting_docs', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Documents have already been submitted for this application.']);
        exit;
    }
    $category_id = intval($app['category_id']);
}

// ── Fetch category name to determine type ───────────────────────────────────────
$category_name = '';
$catQuery = mysqli_prepare($connect, "SELECT name FROM categories WHERE category_id = ?");
mysqli_stmt_bind_param($catQuery, 'i', $category_id);
mysqli_stmt_execute($catQuery);
$catRes = mysqli_stmt_get_result($catQuery);
if ($catRow = mysqli_fetch_assoc($catRes)) {
    $category_name = $catRow['name'];
}
mysqli_stmt_close($catQuery);

$is_health = (stripos($category_name, 'health') !== false || stripos($category_name, 'medical') !== false || $category_id == 2);
$is_life = (stripos($category_name, 'life') !== false || $category_id == 5);
$is_property = (stripos($category_name, 'property') !== false || $category_id == 3);

if ($is_health) {
    $fileFields = [
        'national_id'   => 'National ID',
        'health_report' => 'Medical History Report',
        'prescription_docs' => 'Prescription Documents',
    ];
    $requiredFields = ['national_id', 'health_report'];
    $multiFields = ['prescription_docs'];
} else if ($is_life) {
    $fileFields = [
        'national_id'     => 'National ID',
        'medical_history' => 'Health Checkup & Medical History',
        'income_proof'    => 'Income Proof Documents',
    ];
    $requiredFields = ['national_id', 'medical_history'];
    $multiFields = ['income_proof'];
} else if ($is_property) {
    $fileFields = [
        'national_id'     => 'National ID',
        'property_deed'   => 'Property Title Deed',
        'property_photos' => 'Property Photos',
    ];
    $requiredFields = ['national_id', 'property_deed'];
    $multiFields = ['property_photos'];
} else {
    // Car / default
    $fileFields = [
        'national_id'  => 'National ID',
        'car_license'  => 'Car License',
        'car_photos'   => 'Car Photos',
    ];
    $requiredFields = ['national_id', 'car_license'];
    $multiFields = ['car_photos'];
}

// ── Upload directory ───────────────────────────────────────────────────────────
$uploadDir = __DIR__ . '/uploads/documents/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$validFiles = [];
$errors     = [];

foreach ($fileFields as $fieldName => $docType) {
    $isMulti = in_array($fieldName, $multiFields);

    if (!isset($_FILES[$fieldName]) || empty($_FILES[$fieldName]['name'][0])) {
        if (in_array($fieldName, $requiredFields)) {
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

        // Store file details for post-validation processing
        $validFiles[] = [
            'fieldName' => $fieldName,
            'docType'   => $docType,
            'tmpName'   => $tmpNames[$i],
            'ext'       => $ext
        ];
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

if (empty($validFiles)) {
    $reqLabels = [];
    foreach ($requiredFields as $rf) {
        $reqLabels[] = $fileFields[$rf];
    }
    echo json_encode(['success' => false, 'message' => 'Please upload the required documents: ' . implode(', ', $reqLabels) . '.']);
    exit;
}

// ── Now we have passed ALL validations. We can proceed with DB updates and file writing ──
$db_updated = false;

if ($is_session_flow) {
    // 1. Insert application row
    $sql = "INSERT INTO applications (customer_id, category_id, plan_id, status, application_data, created_at)
            VALUES (?, ?, ?, 'under_review', ?, NOW())";
    $appStmt = mysqli_prepare($connect, $sql);
    if (!$appStmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . mysqli_error($connect)]);
        exit;
    }
    $appDataJson = json_encode($appData);
    mysqli_stmt_bind_param($appStmt, 'iiis', $customer_id, $category_id, $temp_plan_id, $appDataJson);
    if (mysqli_stmt_execute($appStmt)) {
        $application_id = mysqli_insert_id($connect);
        $db_updated = true;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create application: ' . mysqli_stmt_error($appStmt)]);
        exit;
    }
    mysqli_stmt_close($appStmt);
} else {
    // 1. Update application status to under_review for existing application
    if ($app['status'] === 'rejected') {
        $updStmt = mysqli_prepare($connect,
            "UPDATE applications 
             SET status = 'under_review', 
                 application_data = JSON_SET(COALESCE(application_data, '{}'), '$.was_rejected_before', true) 
             WHERE application_id = ? AND customer_id = ?"
        );
    } else {
        $updStmt = mysqli_prepare($connect,
            "UPDATE applications SET status = 'under_review' WHERE application_id = ? AND customer_id = ?"
        );
    }
    mysqli_stmt_bind_param($updStmt, 'ii', $application_id, $customer_id);
    if (mysqli_stmt_execute($updStmt)) {
        $db_updated = true;
    }
    mysqli_stmt_close($updStmt);
}

if (!$db_updated) {
    echo json_encode(['success' => false, 'message' => 'Failed to process application status.']);
    exit;
}

// 2. Move files to directory using final application_id
$uploadedDocs = [];
$move_errors = [];

foreach ($validFiles as $file) {
    $newName   = 'app_' . $application_id . '_' . $file['fieldName'] . '_' . uniqid() . '.' . $file['ext'];
    $destPath  = $uploadDir . $newName;
    $dbPath    = 'uploads/documents/' . $newName;

    if (move_uploaded_file($file['tmpName'], $destPath)) {
        $uploadedDocs[] = [
            'type' => $file['docType'],
            'path' => $dbPath,
        ];
    } else {
        $move_errors[] = "Failed to save {$file['docType']} file.";
    }
}

if (!empty($move_errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $move_errors)]);
    exit;
}

// 3. Insert documents into the documents table
$insStmt = mysqli_prepare($connect,
    "INSERT INTO documents (application_id, doc_type, file_path, uploaded_at) VALUES (?, ?, ?, NOW())"
);

foreach ($uploadedDocs as $doc) {
    mysqli_stmt_bind_param($insStmt, 'iss', $application_id, $doc['type'], $doc['path']);
    mysqli_stmt_execute($insStmt);
}
mysqli_stmt_close($insStmt);

// 4. Also store any notes in application_data
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

// 5. If it was the session flow, clean up session draft variables
if ($is_session_flow) {
    unset($_SESSION['temp_application_data']);
    unset($_SESSION['temp_category_id']);
    unset($_SESSION['temp_plan_id']);
}

echo json_encode(['success' => true, 'message' => 'Documents submitted successfully.']);
?>
