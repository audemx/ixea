<?php
/** fix_daily_sales.php **/
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';

try {
    $pdo = connectDB();
    $pdo->beginTransaction();

    // 1. Buscamos todas las ventas globales (daily)
    $stmtSales = $pdo->query("SELECT sale_id, folio FROM sales WHERE payment_status = 'daily'");
    $sales = $stmtSales->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Corrigiendo items_count en Ventas Diarias</h3>";
    echo "<ul>";

    $updatedCount = 0;

    foreach ($sales as $sale) {
        $sale_id = $sale['sale_id'];

        // 2. Contamos cuántas filas únicas tiene en los detalles
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM sale_details WHERE sale_id = ?");
        $stmtCount->execute([$sale_id]);
        $realCount = (int)$stmtCount->fetchColumn();

        // 3. Actualizamos la tabla sales
        $update = $pdo->prepare("UPDATE sales SET items_count = ? WHERE sale_id = ?");
        $update->execute([$realCount, $sale_id]);

        echo "<li>Folio: <strong>{$sale['folio']}</strong> (ID: $sale_id) -> Nuevo items_count: <strong>$realCount</strong></li>";
        $updatedCount++;
    }

    echo "</ul>";
    $pdo->commit();
    echo "<p><strong>Proceso finalizado. Se actualizaron $updatedCount registros.</strong></p>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}