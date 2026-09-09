<?php
/** 
 * /modules/customers/customers.php
 */
?>

<div class="container-fluid h-100 d-flex flex-column py-2 px-4">
    <div class="flex-grow-1 bg-white bg-opacity-75 backdrop-blur overflow-hidden d-flex flex-column">
        <div class="p-2">
            <table id="tableCustomers" class="table table-hover align-middle w-100">
                <thead>
                    <tr class="small text-uppercase text-muted border-bottom">
                        <th>RFC</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Crédito</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody class="small">
                    </tbody>
            </table>
        </div>
    </div>
</div>


<div class="modal fade" id="clientModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="clientForm" class="needs-validation" novalidate>
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="modalTitle">Nuevo Cliente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" id="clientTabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabGeneral"><i class="bi bi-person me-1"></i>General</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabFiscal"><i class="bi bi-file-earmark-text me-1"></i>Facturación</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabDireccion"><i class="bi bi-geo-alt me-1"></i>Dirección Fiscal</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabCredito"><i class="bi bi-shield-check me-1"></i>Estatus</a></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tabGeneral">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold">Nombre de Contacto *</label>
                                    <input type="text" name="nombre_contacto" id="input_nombre_contacto" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Correo de Registro *</label>
                                    <input type="email" name="correo_registro" id="input_correo_registro" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Teléfono de Contacto</label>
                                    <input type="text" name="telefono_contacto" id="input_telefono_contacto" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Fecha de Nacimiento</label>
                                    <input type="date" name="fecha_nacimiento" id="input_fecha_nacimiento" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Género</label>
                                    <select name="genero" id="input_genero" class="form-select">
                                        <option value="">Seleccione...</option>
                                        <option value="Masculino">Masculino</option>
                                        <option value="Femenino">Femenino</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tabFiscal">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">RFC (ID único) *</label>
                                    <input type="text" name="rfc" id="input_rfc" class="form-control" required maxlength="13" placeholder="XAXX010101000" required>
                                </div>
                                <div class="col-8">
                                    <label class="form-label small fw-bold">Razón Social</label>
                                    <input type="text" name="razon_social" id="input_razon_social" class="form-control text-uppercase">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Correo de Facturación</label>
                                    <input type="email" name="correo_facturacion" id="input_correo_facturacion" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Régimen Fiscal</label>
                                    <select name="regimen_fiscal" id="input_regimen_fiscal" class="form-select">
                                        <option value="">Seleccione...</option>
                                        <option value="601">601 - General de Ley Personas Morales</option>
                                        <option value="612">612 - Personas Físicas (Act. Empresarial)</option>
                                        <option value="626">626 - RESICO</option>
                                        <option value="605">605 - Sueldos y Salarios</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tabDireccion">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold">Calle</label>
                                    <input type="text" name="fiscal_calle" id="input_fiscal_calle" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Ext.</label>
                                    <input type="text" name="fiscal_num_ext" id="input_fiscal_num_ext" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold">Int.</label>
                                    <input type="text" name="fiscal_num_int" id="input_fiscal_num_int" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Colonia</label>
                                    <input type="text" name="fiscal_colonia" id="input_fiscal_colonia" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Municipio / Alcaldía</label>
                                    <input type="text" name="fiscal_municipio" id="input_fiscal_municipio" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Estado</label>
                                    <input type="text" name="fiscal_estado" id="input_fiscal_estado" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Código Postal</label>
                                    <input type="text" name="fiscal_cp" id="input_fiscal_cp" class="form-control" maxlength="10">
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="tabCredito">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Estatus Operativo</label>
                                    <select name="status" id="select_status" class="form-select"></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Estatus de Crédito</label>
                                    <select name="status_credito" id="select_credito" class="form-select"></select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <input type="hidden" name="action" id="form_action" value="create">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Guardar Cliente</button>
                </div>
            </div>
        </form>
    </div>
</div>
