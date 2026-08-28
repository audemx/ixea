<?php
/**
 * Pie de página corporativo
 * includes/footer.php
 */
?>
<footer class="bg-dark pt-3 pb-5 pb-md-3 border-top">
    <div class="container text-center">
        <div class="brand-ixea d-flex align-items-center justify-content-center">
            <?php 
                $svg = file_get_contents($root_path . '/includes/isotipo.php');
                $svg = str_replace('var(--bg-color)', '#FFF', $svg);
                $svg = str_replace('var(--x-color)', '#000', $svg);
                echo $svg;
            ?>
            <span class="brand-text text-white fs-5">IXEA</span>
        </div>

        <p class="small text-secondary mb-0">
            &copy; <?php echo date('Y'); ?> IXEA. Todos los derechos reservados.
        </p>
    </div>
</footer>