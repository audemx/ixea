<?php
// /modules/reports/sales-report-controler.php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/config.php';
require_once $root_path . '/database.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';
$periodo = $_GET['periodo'] ?? 'mes';

try {
    $pdo = connectDB();

    if ($action === 'get_analytics') {
        // 1. Definir rango de fechas
        $baseWhere = "WHERE v.estado_pago = 'pagado' AND v.fecha_operacion ";
        switch ($periodo) {
            case 'hoy':
                $whereFecha = $baseWhere . ">= CURDATE()";
                break;
            case 'semana':
                $whereFecha = $baseWhere . ">= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'anio':
                $whereFecha = $baseWhere . ">= DATE_FORMAT(NOW() ,'%Y-01-01')";
                break;
            case 'lastMonth':
                // Desde el primer día del mes pasado 00:00:00 hasta el último día 23:59:59
                $whereFecha = $baseWhere . ">= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01 00:00:00') 
                               AND v.fecha_operacion <= LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH)) + INTERVAL 1 DAY - INTERVAL 1 SECOND";
                break;
            case 'lastYear':
                $whereFecha = $baseWhere . "BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y-01-01 00:00:00') 
                               AND DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y-12-31 23:59:59')";
                break;
            default: // mes actual
                $whereFecha = $baseWhere . ">= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00')";
                break;
        }

        // 2. KPIs Principales
        $sqlKpis = "SELECT 
                        SUM(v.total_operacion) as total, 
                        AVG(v.total_operacion) as avg_ticket,
                        COUNT(DISTINCT v.id) as tickets
                    FROM ventas v $whereFecha";
        $kpis = $pdo->query($sqlKpis)->fetch(PDO::FETCH_ASSOC);

        // 3. Cálculo de Utilidad Bruta Real (Ingreso - Costo)
        $sqlUtilidad = "SELECT SUM((dv.precio_unitario - IFNULL(costos.ultimo_costo, 0)) * dv.cantidad) as util
                        FROM detalle_ventas dv
                        JOIN ventas v ON dv.venta_id = v.id
                        LEFT JOIN (
                            SELECT pu1.id_producto, pu1.precio_compra as ultimo_costo 
                            FROM producto_unidades pu1 
                            WHERE pu1.es_compra = 1 
                            AND pu1.codigo_unidad IN (
                                SELECT MAX(pu2.codigo_unidad) 
                                FROM producto_unidades pu2 
                                WHERE pu2.es_compra = 1 
                                GROUP BY pu2.id_producto
                            )
                        ) costos ON dv.producto_id = costos.id_producto
                        $whereFecha";
        $resUtil = $pdo->query($sqlUtilidad)->fetch(PDO::FETCH_ASSOC);

        // 4. Comparativa de Crecimiento Dinámica
        $sqlPrev = "";
        $labelComparativa = "";
        
        switch ($periodo) {
            case 'hoy':
                // Compara hoy contra ayer
                $sqlPrev = "SELECT SUM(total_operacion) as total FROM ventas v
                            $baseWhere = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                $labelComparativa = "vs. ayer";
                break;
        
            case 'semana':
                // Compara los últimos 7 días contra los 7 días previos a esos
                $sqlPrev = "SELECT SUM(total_operacion) as total FROM ventas v
                            $baseWhere BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY) 
                            AND DATE_SUB(CURDATE(), INTERVAL 8 DAY)";
                $labelComparativa = "vs. sem. ant.";
                break;
        
            case 'lastMonth':
                // Compara Mes Anterior (Ene) vs Mes Ante-anterior (Dic)
                $sqlPrev = "SELECT SUM(total_operacion) as total FROM ventas v
                            $baseWhere >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 MONTH), '%Y-%m-01 00:00:00') 
                            AND fecha_operacion <= LAST_DAY(DATE_SUB(NOW(), INTERVAL 2 MONTH)) + INTERVAL 1 DAY - INTERVAL 1 SECOND";
                $labelComparativa = "vs. mes ant.";
                break;
        
            case 'anio':
                // Compara Este Año (2026) vs Año Anterior (2025)
                $sqlPrev = "SELECT SUM(total_operacion) as total FROM ventas v
                            $baseWhere BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y-01-01 00:00:00') 
                            AND DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 YEAR), '%Y-12-31 23:59:59')";
                $labelComparativa = "vs. año ant.";
                break;
        
            case 'lastYear':
                // Compara Año Anterior (2025) vs Año Ante-anterior (2024)
                $sqlPrev = "SELECT SUM(total_operacion) as total FROM ventas v
                            $baseWhere BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 YEAR), '%Y-01-01 00:00:00') 
                            AND DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 YEAR), '%Y-12-31 23:59:59')";
                $labelComparativa = "vs. año ant.";
                break;
        
            default: // 'mes' (Este mes vs Mes pasado)
                $sqlPrev = "SELECT SUM(total_operacion) as total FROM ventas v
                            $baseWhere >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01 00:00:00') 
                            AND fecha_operacion <= LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH)) + INTERVAL 1 DAY - INTERVAL 1 SECOND";
                $labelComparativa = "vs. mes ant.";
                break;
        }
        
        $ventasPeriodoAnterior = $pdo->query($sqlPrev)->fetchColumn() ?: 1; 
        $crecimiento = (($kpis['total'] - $ventasPeriodoAnterior) / $ventasPeriodoAnterior) * 100;

        // 5. Gráfico de Tendencia Adaptativo (Día o Mes según periodo)
        $esAnual = in_array($periodo, ['anio', 'lastYear']);
        $agrupar = $esAnual ? "%Y-%m" : "%Y-%m-%d";
        $formatoLabel = $esAnual ? "M y" : "d M";

        $sqlGrafico = "SELECT 
                            DATE_FORMAT(v.fecha_operacion, '$agrupar') as periodo_label, 
                            SUM(v.total_operacion) as total 
                        FROM ventas v 
                        $whereFecha 
                        GROUP BY periodo_label 
                        ORDER BY v.fecha_operacion ASC";
        $resGrafico = $pdo->query($sqlGrafico)->fetchAll(PDO::FETCH_ASSOC);
        
        // Traducción rápida para meses
        $mesesTraduccion = [
            'Jan' => 'Ene', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Abr', 
            'May' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ago', 
            'Sep' => 'Sep', 'Oct' => 'Oct', 'Nov' => 'Nov', 'Dec' => 'Dic'
        ];

        $labels = array_map(function($r) use ($formatoLabel, $mesesTraduccion) {
            $fechaRef = (strlen($r['periodo_label']) <= 7) ? $r['periodo_label'] . "-01" : $r['periodo_label'];
            $fechaFormateada = date($formatoLabel, strtotime($fechaRef));
            
            // Traducimos el mes de inglés a español (ej: Jan 26 -> Ene 26)
            return strtr($fechaFormateada, $mesesTraduccion);
        }, $resGrafico);

        // 6. Ventas por Categoría (Top 5)
        $sqlCat = "SELECT c.nombre_categoria as cat, SUM(dv.precio_unitario * dv.cantidad) as total
                    FROM detalle_ventas dv
                    JOIN productos p ON dv.producto_id = p.id_producto
                    JOIN categorias c ON p.id_categoria = c.id_categoria
                    JOIN ventas v ON dv.venta_id = v.id
                    $whereFecha
                    GROUP BY c.id_categoria, c.nombre_categoria ORDER BY total DESC LIMIT 5";
        $resCat = $pdo->query($sqlCat)->fetchAll(PDO::FETCH_ASSOC);

        // 7. Top 10 Productos con Margen
        $sqlTop = "SELECT 
                        p.nombre, 
                        -- Multiplicamos la cantidad vendida por el factor de la unidad usada en esa venta
                        SUM(dv.cantidad * IFNULL(u_venta.factor_venta, 1)) as cant, 
                        SUM(dv.precio_unitario * dv.cantidad) as v_total,
                        SUM((dv.precio_unitario - IFNULL(costos.ultimo_costo, 0)) * dv.cantidad) as m_total
                    FROM detalle_ventas dv 
                    JOIN productos p ON dv.producto_id = p.id_producto
                    JOIN ventas v ON dv.venta_id = v.id
                    -- Buscamos el factor de conversión de la unidad con la que se vendió
                    LEFT JOIN producto_unidades u_venta ON dv.producto_id = u_venta.id_producto 
                        AND dv.unidad_venta = u_venta.codigo_unidad
                    LEFT JOIN (
                       SELECT pu1.id_producto, pu1.precio_compra as ultimo_costo 
                       FROM producto_unidades pu1 
                       WHERE pu1.es_compra = 1 
                       AND pu1.codigo_unidad IN (
                           SELECT MAX(pu2.codigo_unidad) 
                           FROM producto_unidades pu2 
                           WHERE pu2.es_compra = 1 
                           GROUP BY pu2.id_producto
                       )
                    ) costos ON dv.producto_id = costos.id_producto
                    $whereFecha 
                    GROUP BY p.id_producto 
                    ORDER BY m_total DESC 
                    LIMIT 10";
        $resTop = $pdo->query($sqlTop)->fetchAll(PDO::FETCH_ASSOC);

        // Enviamos respuesta
        echo json_encode([
            'success' => true,
            'kpis' => [
                'ventas' => '$' . number_format($kpis['total'] ?? 0, 2),
                'ticket' => '$' . number_format($kpis['avg_ticket'] ?? 0, 2),
                'clientes' => $kpis['tickets'] ?? 0,
                'utilidad' => '$' . number_format($resUtil['util'] ?? 0, 2),
                'crecimiento' => round($crecimiento, 1),
                'labelComparativa' => $labelComparativa
            ],
            'graficoVentas' => [
                'labels' => $labels,
                'valores' => array_column($resGrafico, 'total')
            ],
            'graficoCategorias' => [
                'labels' => array_column($resCat, 'cat'),
                'valores' => array_column($resCat, 'total')
            ],
            'topProductos' => $resTop
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}