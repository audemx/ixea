<?php
/* * Funciones y Bibliotecas
 * includes/scripts.php
 */
?>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.0.1"></script>

<script>
window.IXEA_USER = {
    permissions: <?php echo json_encode($user_permissions); ?>,
    user_id: "<?php echo $user_id; ?>",
    user_name: "<?php echo $user_name; ?>",
    user_role: "<?php echo $role_name; ?>",
    user_email: "<?php echo $user_email; ?>"
};

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('SW registrado'))
            .catch(err => console.log('Error registrando SW', err));
    });
}
</script>

<script src="/assets/js/os-kernel.js?v=<?php echo filemtime($root_path . '/assets/js/os-kernel.js'); ?>"></script>
<script src="/assets/js/os-ui.js?v=<?php echo filemtime($root_path . '/assets/js/os-ui.js'); ?>"></script>
<script src="/assets/js/os-widgets.js?v=<?php echo filemtime($root_path . '/assets/js/os-widgets.js'); ?>"></script>
<script src="/assets/js/os-apis.js?v=<?php echo filemtime($root_path . '/assets/js/os-apis.js'); ?>"></script>