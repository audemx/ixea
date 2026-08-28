<?php
// footer_scripts.php
?>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const brandbar = document.getElementById('brandbar'); 
    const navbar = document.querySelector('.ixea-navbar');
    const navLogo = document.getElementById('navLogo');
    const mobileNav = document.querySelector('.ixea-mobile-nav');
    
    let lastScrollTop = 0;
    // Usamos una función para obtener la altura siempre actualizada
    const getBrandHeight = () => brandbar ? brandbar.offsetHeight : 0;

    function handleScroll() {
        let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const threshold = 10; 
        
        // Evitar ejecución si el cambio es insignificante
        if (Math.abs(lastScrollTop - scrollTop) <= threshold) return;

        if (scrollTop > lastScrollTop && scrollTop > threshold) {
            // --- BAJANDO ---
            if(brandbar) brandbar.classList.add('navbar-hidden');
            if(navbar) {
                navbar.style.setProperty('top', '0', 'important');
                navLogo.classList.add('show-logo');
            }
            // Mobile Nav: Se esconde hacia abajo
            if(mobileNav) mobileNav.classList.add('nav-hidden');

        } else if (scrollTop < lastScrollTop) {
            // --- SUBIENDO ---
            if(brandbar) brandbar.classList.remove('navbar-hidden');
            
            if (scrollTop > threshold) {
                if(navbar) {
                    navbar.style.setProperty('top', getBrandHeight() + 'px', 'important');
                    navLogo.classList.remove('show-logo');
                }
            } else {
                // Tope total de la página
                if(navbar) {
                    navbar.style.setProperty('top', '0', 'important');
                    navLogo.classList.remove('show-logo');
                }
            }
            // Mobile Nav: Regresa a su posición (visible)
            if(mobileNav) mobileNav.classList.remove('nav-hidden');
        }

        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    }

    // Registramos el evento
    window.addEventListener('scroll', handleScroll, { passive: true });
    
    // Recalcular en resize por si cambian las alturas de las barras
    window.addEventListener('resize', handleScroll);
</script>

<script>
    // Muestro u oculta contraseña
    function togglePassword(inputId, button) {
        const passwordInput = document.getElementById(inputId);
        const icon = button.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
</script>