<?php
// modules/purchases/pdf-purchase.php
$root_path = $_SERVER['DOCUMENT_ROOT'];
require_once $root_path . '/database.php';
require_once $root_path . '/security.php';
require_once $root_path . '/api/back-functions.php';
require_once USER_HOME . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$purchase_id = $_GET['purchase_id'] ?? 0;
if (!$purchase_id) die("ID no válido");

try {
    $pdo = connectDB();

    // 1. Obtener datos
    $data = getPurchaseData($pdo, $purchase_id);
    if (!$data) {
        http_response_code(404);
        die(json_encode(['success' => false, 'error' => 'No se encontró la compra']));
    }
    
    $purchase = $data['purchase'];
    $items = $data['items'];
    
    // 2. Generar un Token de seguridad único para cada compra
    $token = substr(hash('sha256', $purchase_id . "Compras_Inteligentes"), 0, 10);
    $oc = "OC-{$token}-{$purchase_id}";
    $filename = "$oc.pdf";
    
    $public_html = dirname($root_path); 
    $tempPath = $public_html . "/storage/temp_pdfs/";
    
    $fullPath = $tempPath . $filename;
    $url = "https://tlapayferrediego.com/view.php?t=oc&x=$oc";
    
    // 3. Si existe, lo servimos directamente. Si no, lo creamos.
    if (!file_exists($fullPath)) {
        
        // Configurar Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); // Para cargar tu logo
        $dompdf = new Dompdf($options);
    
        // 4. Crear el HTML (Template formal tamaño carta)
        $html = "
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                @page { margin: 1cm; }
                body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; line-height: 1.5; }
                
                /* Encabezado */
                .header-table { width: 100%; border-bottom: 2px solid #a65f4b; margin-bottom: 20px; }
                .brand-name { font-size: 22px; font-weight: bold; color: #a65f4b; margin: 0; }
                .order-title { text-align: right; font-size: 18px; color: #555; margin: 0; }
                
                /* Bloques de Info */
                .info-table { width: 100%; margin-bottom: 20px; }
                .info-table td { width: 50%; vertical-align: top; }
                .section-title { background: #f4f4f4; padding: 4px 8px; font-weight: bold; text-transform: uppercase; font-size: 10px; margin-bottom: 5px; border-left: 3px solid #a65f4b; }
                
                /* Tabla de Productos */
                .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                .items-table th { background: #a65f4b; color: white; padding: 8px; text-align: left; text-transform: uppercase; font-size: 10px; }
                .items-table td { padding: 8px; border-bottom: 1px solid #eee; }
                .items-table tr:nth-child(even) { background-color: #f9f9f9; }
                
                /* Totales */
                .total-wrapper { width: 100%; margin-top: 20px; }
                .total-box { float: right; width: 35%; border: 1px solid #eee; }
                .total-row { padding: 8px; font-size: 14px; font-weight: bold; text-align: right; background: #f4f4f4; color: #a65f4b; }
                
                .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
            </style>
        </head>
        <body>
            <table class='header-table'>
                <tr>
                    <td>
                        <h1 class='brand-name'>Tlapa y Ferre Diego</h1>
                        <p style='margin:0;'>Ecatepec de Morelos, Edo. Méx.</p>
                        <p style='margin:0;'>RFC: MIRE680611NX4</p>
                    </td>
                    <td class='order-title'>
                        ORDEN DE COMPRA<br>
                        <span style='font-size: 14px; color: #333;'>Folio: <b>{$purchase['folio']}</b></span><br>
                        <span style='font-size: 11px; color: #777;'>Fecha: " . date('d/m/Y H:i', strtotime($purchase['date'])) . "</span>
                    </td>
                </tr>
            </table>
        
            <table class='info-table'>
                <tr>
                    <td>
                        <div class='section-title'>Proveedor</div>
                        <div style='padding-left: 8px;'>
                            <b>{$purchase['company_name']}</b><br>
                            RFC: {$purchase['rfc']}<br>
                            Atn: {$purchase['contact_name']}<br>
                            Tel: {$purchase['phone']}
                        </div>
                    </td>
                    <td>
                        <div class='section-title'>Detalles de Entrega</div>
                        <div style='padding-left: 8px;'>
                            <b>Lugar:</b> Av. Carlos Hank Gonzalez Mz.61 Lt.20, Granjas Valle de Guadalupe, Ecatepec de Morelos, Estado de México<br>
                            <b>Comprador:</b> {$purchase['user_name']}<br>
                            <b>Condición:</b> " . ($purchase['is_credit'] ? 'Crédito' : 'Contado') . "
                        </div>
                    </td>
                </tr>
            </table>
        
            <table class='items-table'>
                <thead>
                    <tr>
                        <th width='45%'>Descripción</th>
                        <th width='10%' style='text-align:center;'>Cant.</th>
                        <th width='15%' style='text-align:right;'>Precio U.</th>
                        <th width='15%' style='text-align:right;'>Subtotal</th>
                    </tr>
                </thead>
                <tbody>";
        
                foreach ($items as $item) {
                    $html .= "
                    <tr>
                        <td>{$item['name']} <br><small style='color:#777'>SKU: {$item['sku']} | Unidad: {$item['unit']}</small></td>
                        <td style='text-align:center;'>{$item['qty']}</td>
                        <td style='text-align:right;'>$ " . number_format($item['cost'], 2) . "</td>
                        <td style='text-align:right;'>$ " . number_format($item['subtotal'], 2) . "</td>
                    </tr>";
                }
        
        $html .= "
                </tbody>
            </table>
        
            <div class='total-wrapper'>
                <div class='total-box'>
                    <div class='total-row'>
                        TOTAL: $ " . number_format($purchase['total_amount'], 2) . " MXN
                    </div>
                </div>
            </div>
        
            <div style='margin-top: 100px;'>
                <table width='100%'>
                    <tr>
                        <td style='text-align: center; border-top: 1px solid #ccc; width: 40%; padding-top: 8px;'>Firma Autorizada</td>
                        <td width='20%'></td>
                        <td style='text-align: center; border-top: 1px solid #ccc; width: 40%; padding-top: 8px;'>Sello de Recibido</td>
                    </tr>
                </table>
            </div>
        
            <div class='footer'>
                Documento generado automáticamente por IXEA OS. Tlapalería y Ferretería Diego.
            </div>
        </body>
        </html>";
    
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait'); // Cambié A4 por Letter (Carta) que es más común en México
        $dompdf->render();
    
        file_put_contents($fullPath, $dompdf->output());
    }
    
    // 5. Enviar ruta
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'url' => $url,
        'filename' => $filename
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}