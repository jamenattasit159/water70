<?php
// api/get_organizations.php - Get organizations filtered by district
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$district_id = isset($_GET['district_id']) ? intval($_GET['district_id']) : 0;

if ($district_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid district id', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, name_th, type FROM organizations WHERE district_id = ? ORDER BY type DESC, name_th ASC");
    $stmt->execute([$district_id]);
    $organizations = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'district_id' => $district_id,
        'count' => count($organizations),
        'data' => $organizations
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
