<?php
/** fix_expenses_tax.php **/
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';

try {
    $pdo = connectDB();
    $pdo->beginTransaction();

    // 1. Obtener todos los gastos activos
    // Solo procesamos 'applied' y 'pending' para evitar tocar cancelados
    $stmt = $pdo->query("SELECT expense_id, category_id, total_amount, is_deductible, tax_amount, concept FROM expenses WHERE status != 'cancelled'");
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Regularización de Impuestos y Deducibilidad en Gastos</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-family: sans-serif;'>
            <thead style='background: #eee;'>
                <tr>
                    <th>ID</th>
                    <th>Concepto</th>
                    <th>Monto</th>
                    <th>Cat</th>
                    <th>Deducible Orig.</th>
                    <th>Deducible Nuevo</th>
                    <th>IVA Nuevo</th>
                </tr>
            </thead>
            <tbody>";

    $updatedCount = 0;

    foreach ($expenses as $exp) {
        $id = $exp['expense_id'];
        $category = (int)$exp['category_id'];
        $total_amount = (float)$exp['total_amount'];
        
        // Variables para comparar cambios
        $current_deductible = (int)$exp['is_deductible'];
        $current_tax = (float)$exp['tax_amount'];
        
        // Lógica de asignación según tu switch
        $new_deductible = $current_deductible;
        $new_tax = 0.00;

        switch ($category) {
            case 3:
                // NÓMINA Y RRHH: Se respeta la elección original del usuario
                $new_deductible = $current_deductible;
                // Si es deducible en nómina, el IVA suele ser 0 (sueldos exentos), 
                // pero si hubiera algún IVA lo mantenemos o recalculamos aquí.
                $new_tax = $current_tax; 
                break;
        
            case 2: // RETIRO SOCIOS
            case 6: // FISCALES E IMPUESTOS
            case 7: // PAGO CAPITAL DEUDA
                // Estos NUNCA son deducibles para el cálculo de utilidad operativa/fiscal
                $new_deductible = 0;
                $new_tax = 0.00;
                break;
        
            default:
                // Cálculo estándar de IVA (16%)
                // Fórmula: Total - (Total / 1.16)
                $new_tax = $new_deductible ? ($total_amount - ($total_amount / 1.16)) : 0;
                break;
        }

        // Solo actualizamos si hubo algún cambio real para ahorrar recursos
        if ($new_deductible !== $current_deductible || abs($new_tax - $current_tax) > 0.01) {
            $update = $pdo->prepare("UPDATE expenses SET is_deductible = ?, tax_amount = ? WHERE expense_id = ?");
            $update->execute([$new_deductible, $new_tax, $id]);
            
            $statusColor = "style='background: #e8f5e9;'"; // Verde suave para cambios
            $updatedCount++;
        } else {
            $statusColor = "";
        }

        echo "<tr $statusColor>
                <td>$id</td>
                <td>{$exp['concept']}</td>
                <td>$ " . number_format($total_amount, 2) . "</td>
                <td>$category</td>
                <td>" . ($current_deductible ? 'SÍ' : 'NO') . "</td>
                <td><strong>" . ($new_deductible ? 'SÍ' : 'NO') . "</strong></td>
                <td>$ " . number_format($new_tax, 2) . "</td>
              </tr>";
    }

    echo "</tbody></table>";

    $pdo->commit();
    echo "<h4>Proceso completado. Se actualizaron $updatedCount gastos.</h4>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "<h2 style='color:red;'>Error: " . $e->getMessage() . "</h2>";
}