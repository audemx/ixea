<?php
/** 
 * Grupo de funciones auxiliares
 * api/auxiliary-functions.php
 **/
 
 /**
 * Devuelve un array con estandar de periodos
 **/
function getPeriodDates($period) {
    $start = date('Y-m-d 00:00:00');
    $end   = date('Y-m-d 23:59:59');

    switch ($period) {
        case 'week':
            $start = date('Y-m-d 00:00:00', strtotime('monday this week'));
            break;
            
        case 'month':
            $start = date('Y-m-01 00:00:00');
            break;
            
        case 'last_month':
            $start = date('Y-m-01 00:00:00', strtotime('first day of last month'));
            $end   = date('Y-m-t 23:59:59', strtotime('last day of last month'));
            break;
            
        case 'next_month':
            $start = date('Y-m-01 00:00:00', strtotime('first day of next month'));
            $end   = date('Y-m-t 23:59:59', strtotime('last day of next month'));
            break;
            
        case 'day':
        default:
            $start = date('Y-m-d 00:00:00');
            break;
    }
    return ['start' => $start, 'end' => $end];
}

// Función auxiliar para UX en español
function translateMonth($date) {
    $months = [
        'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
        'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
        'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
        'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
    ];
    $monthName = date('F', strtotime($date));
    $year = date('Y', strtotime($date));
    return ($months[$monthName] ?? $monthName) . " " . $year;
}