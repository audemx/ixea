<?php
/** includes/widgets/notes-processor.php **/
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/config.php';
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';

header('Content-Type: application/json');
$pdo = connectDB(); 
$action = $_POST['action'] ?? 'list';

try {
    if ($action === 'list') {
        // Obtenemos note_id, el texto y la fecha formateada
        $stmt = $pdo->prepare("SELECT note_id, note, DATE_FORMAT(updated_at, '%d/%m %H:%i') as fecha FROM notes WHERE usuario_id = ? ORDER BY updated_at DESC");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    if ($action === 'save') {
        $note_content = trim($_POST['note'] ?? '');
        $note_id = !empty($_POST['note_id']) ? $_POST['note_id'] : null;

        if (empty($note_content)) {
            if ($note_id) {
                // Si mandó vacío y había ID, borramos
                $stmt = $pdo->prepare("DELETE FROM notes WHERE note_id = ? AND usuario_id = ?");
                $stmt->execute([$note_id, $user_id]);
                echo json_encode(['status' => 'deleted']);
            } else {
                echo json_encode(['status' => 'no_action']);
            }
        } else {
            if ($note_id) {
                // Actualizar
                $stmt = $pdo->prepare("UPDATE notes SET note = ? WHERE note_id = ? AND usuario_id = ?");
                $stmt->execute([$note_content, $note_id, $user_id]);
            } else {
                // Insertar nuevo
                $stmt = $pdo->prepare("INSERT INTO notes (usuario_id, note) VALUES (?, ?)");
                $stmt->execute([$user_id, $note_content]);
            }
            echo json_encode(['status' => 'success']);
        }
    }

    if ($action === 'delete') {
        $id_to_delete = $_POST['note_id'] ?? null;
        $stmt = $pdo->prepare("DELETE FROM notes WHERE note_id = ? AND usuario_id = ?");
        $stmt->execute([$id_to_delete, $user_id]);
        echo json_encode(['status' => 'success']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}