<?php
/** 
 * Grupo de funciones para validaciones
 * api/validation-functions.php
 **/
 
/**
 * Verifica si existe un snapshot para un periodo específico (fecha de cierre).
 */
function isPeriodClosed($pdo, $date) {
    $sql = "SELECT COUNT(*) FROM finance_snapshots WHERE period = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Obtiene la fecha del último cierre realizado.
 * Útil para bloqueos de seguridad en ventas/gastos.
 */
function getLastClosingDate($pdo) {
    $sql = "SELECT MAX(period) as last_closed FROM finance_snapshots";
    $stmt = $pdo->query($sql);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return $res['last_closed'] ?? null;
}