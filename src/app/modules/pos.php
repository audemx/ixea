<?php
/** modules/sales/pos.php **/
?>
<div class="container-fluid h-100 py-2 px-4">
    <div class="row h-100 g-4">
        
        <div class="col-md-8 d-flex flex-column h-100">
            <div class="search-container mb-4">
                <div class="input-group input-group-lg shadow-sm rounded-4 overflow-hidden border-0">
                    <input type="text" id="product-search" class="form-control border-0 ps-5" placeholder="Ingresa código o producto (F2 busqueda avanzada)">
                    <span class="input-group-text bg-white border-0">
                        <btn class="btn" onclick="PosApp.openSearch()"><i class="bi bi-search"></i></btn>
                    </span>
                </div>
            </div>

            <div id="quick-results" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 align-content-start overflow-auto pe-2" style="flex-grow: 1;">
                </div>
        </div>

        <div class="col-md-4 h-100">
            <div class="card h-100 border-0 rounded-4 shadow-sm bg-white bg-opacity-75 backdrop-blur">
                <div class="card-header bg-transparent border-bottom pt-2 px-4 d-flex justify-content-between">
                    <h5 class="fw-bold m-0"><i class="bi bi-cart3 pe-2"></i> Carrito</h5>
                    <button class="btn btn-small btn-light small mt-0" onclick="PosApp.clearCart()"><i class="bi bi-trash"></i></button>
                </div>
                
                <div class="card-body overflow-auto px-4" id="cart-items">
                    <div class="text-center text-secondary mt-5 pt-5">
                        <i class="bi bi-cart3 fs-1 opacity-25"></i>
                        <p class="mt-2">El carrito está vacío</p>
                    </div>
                </div>

                <div class="card-footer bg-transparent border-top py-2 px-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fs-4 fw-bold">Total: </span>
                        <span id="total-val" class="fs-4 fw-bold text-primary">$0.00</span>
                    </div>
                    <button class="btn btn-primary w-100 rounded-4 fw-bold shadow" onclick="PosApp.checkout(event)" id="pos-btn-checkout">
                        <i class="bi bi-cash-stack me-2"></i> Finalizar (F12)
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="searchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow backdrop-blur">
            <div class="modal-title text-end">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <input type="text" id="adv-search-input" class="form-control form-control-lg rounded-3 mt-3 ms-3" placeholder="Filtrar por nombre, marca o categoría...">
                    <button type="button" class="btn-close mt-3 me-3" aria-label="Close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body py-2 px-4">
                <div class="table-responsive" style="max-height: 60vh;">
                    <table class="table table-hover align-middle">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th class="text-center">Unidad</th>
                                <th class="text-center">Stock</th>
                                <th class="text-end">Precio</th>
                            </tr>
                        </thead>
                        <tbody id="adv-search-results"></tbody>
                        <tr><td colspan="6" class="text-center py-4 text-secondary">Comienza la busqueda</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>