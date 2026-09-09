<?php
/** /modules/rrhh/checker-handler.php **/
header('Content-Type: application/json');
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once $root_path . '/api/logs-functions.php';
require_once $root_path . '/api/back-functions.php';

try {
    $pdo = connectDB();
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'];
    
    
    switch ($action) {
        case 'attendance':
            $descriptor = $data['descriptor'];
            if (!$descriptor) throw new Exception("No se recibió huella facial");
            
            $stmt = $pdo->prepare("SELECT user_id, first_name, face_descriptor FROM users WHERE status = 'active' AND face_descriptor IS NOT NULL");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $matchedUser = null;
            $threshold = 0.3; // Rigurosidad (menor es más estricto)
        
            foreach ($users as $user) {
                $face_descriptor = json_decode($user['face_descriptor'], true);
                
                // Calcular Distancia Euclidiana
                $distance = 0;
                for ($i = 0; $i < 128; $i++) {
                    $diff = $descriptor[$i] - $face_descriptor[$i];
                    $distance += $diff * $diff;
                }
                $distance = sqrt($distance);
        
                if ($distance < $threshold) {
                    $matchedUser = $user;
                    break; 
                }
            }
        
            if (!$matchedUser) {
                echo json_encode([
                    'success' => true,
                    'checkin' => false,
                    'message' => 'Identidad no reconocida'
                ]);
                break;
            }
        
            $uid = $matchedUser['user_id'];
            $name = $matchedUser['first_name'];
        
            /** Definir si es Entrada o Salida **/
            // Lógica: Si ya hay una entrada hoy, la siguiente es salida.
            $today = date('Y-m-d');
            $checkLog = $pdo->prepare("SELECT type FROM attendance WHERE user_id = ? AND DATE(created_at) = ? ORDER BY created_at DESC LIMIT 1");
            $checkLog->execute([$uid, $today]);
            $lastRecord = $checkLog->fetch();
        
            $type = 'in';
            if ($lastRecord) {
                if ($lastRecord['type'] === 'in') {
                    $type = 'out';
                } else {
                    throw new Exception("Ya registraste hoy $name.");
                }
            }
        
            // Registrar
            $ins = $pdo->prepare("INSERT INTO attendance (user_id, type) VALUES (?, ?)");
            $ins->execute([$uid, $type]);
        
            echo json_encode([
                'success' => true,
                'checkin' => true,
                'name' => $name,
                'type_label' => ($type == 'in' ? 'Entrada' : 'Salida')
            ]);
            break;
        
        default:
            throw new Exception("Acción no válida.");
            break;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
        ]);
}