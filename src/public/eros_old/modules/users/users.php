<?php
/** modules/users/users.php **/
?>
<div class="container-fluid h-100 d-flex flex-column py-3 px-4">
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4">
                <div class="card-body py-2">
                    <h6 class="small text-uppercase fw-bold text-muted mb-1">Total Usuarios</h6>
                    <div class="h4 mb-0 fw-bold" id="count_total_users">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-sfgreen border-4">
                <div class="card-body py-2">
                    <h6 class="small text-uppercase fw-bold text-sfgreen mb-1">Activos</h6>
                    <div class="h4 mb-0 fw-bold" id="count_active">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm border-start border-sfyellow border-4">
                <div class="card-body py-2">
                    <h6 class="small text-uppercase fw-bold text-sfyellow mb-1">Sin Biometría</h6>
                    <div class="h4 mb-0 fw-bold" id="count_no_face">0</div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex-grow-1 bg-white bg-opacity-75 backdrop-blur rounded-4 shadow-sm border d-flex flex-column overflow-hidden">
        <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold text-secondary"><i class="bi bi-people me-2"></i>Gestión de Personal</h5>
            <div class="d-flex gap-2">
                <input type="text" class="form-control form-control-sm border-0 shadow-sm" style="width:250px" 
                       placeholder="Buscar usuario..." onkeyup="UsersApp.filterUsers(this.value)">
                <button class="btn btn-sm btn-primary px-3" onclick="UsersApp.openCreateModal()">
                    <i class="bi bi-person-plus-fill me-1"></i> Nuevo
                </button>
            </div>
        </div>

        <div class="p-0 overflow-auto">
            <table id="tablaUsuarios" class="table table-hover align-middle mb-0">
                <thead class="bg-light small text-uppercase text-muted">
                    <tr>
                        <th class="ps-3">Nombre / Identificador</th>
                        <th>Rol / Puesto</th>
                        <th class="text-center">Estatus</th>
                        <th class="text-center">Biometría</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="small border-top-0">
                    </tbody>
            </table>
        </div>
    </div>
</div>

<template id="tpl-user-form">
    <div class="modal-header bg-light p-1">
    </div>

    <div class="modal-body px-4 py-2 bg-light">
        <form id="formUsuario" autocomplete="off">
            <input type="hidden" name="user_id" id="userId">
            <input type="hidden" id="faceDescriptorInput" name="face_descriptor">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Nombre(s) *</label>
                    <input type="text" name="first_name" class="form-control shadow-sm" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-secondary">Apellidos</label>
                    <input type="text" name="last_name" class="form-control shadow-sm">
                </div>

                <div class="col-md-5">
                    <label class="form-label small fw-bold text-secondary">RFC / ID *</label>
                    <input type="text" name="tax_id" class="form-control shadow-sm" maxlength="20" required>
                </div>
                <div class="col-md-7">
                    <label class="form-label small fw-bold text-secondary">Email *</label>
                    <input type="email" name="email" id="emailField" class="form-control shadow-sm" required>
                </div>

                <div class="col-md-5">
                    <label class="form-label small fw-bold text-secondary">Contraseña</label>
                    <input type="password" name="password" id="passwordField" class="form-control" placeholder="********">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary">Rol *</label>
                    <select id="sel_rol" name="role_id" class="form-select shadow-sm" required></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-secondary">PIN</label>
                    <input type="text" name="auth_pin" class="form-control text-center shadow-sm" maxlength="4" placeholder="0000">
                </div>

                <div class="col-md-12">
                    <label class="form-label small fw-bold text-secondary">Estado del Usuario</label>
                    <select name="status" class="form-select shadow-sm">
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                        <option value="terminated">Baja</option>
                    </select>
                </div>

                <div class="col-12 mt-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                        <div class="card-body px-4 py-2 text-center">
                            <h6 class="fw-bold text-primary mb-1"><i class="bi bi-person-bounding-box me-2"></i>Identidad Biométrica</h6>
                            
                            <div id="cameraBox" class="d-none mb-1 mx-auto rounded-4 overflow-hidden shadow" 
                                 style="width:280px; height:210px; background:#000; position: relative;">
                                
                                <video id="videoElement" autoplay muted playsinline 
                                       style="width:100%; height:100%; object-fit:cover; transform: scaleX(-1);"></video>
                                
                                <canvas id="faceCanvas" width="280" height="210" 
                                        style="position: absolute; top:0; left:0; z-index: 100; pointer-events: none;"></canvas>
                            </div>

                            <div id="scanProgress" class="d-flex justify-content-center gap-2 mb-1">
                                <span class="progress-step" id="step1"></span>
                                <span class="progress-step" id="step2"></span>
                                <span class="progress-step" id="step3"></span>
                            </div>

                            <div class="d-grid gap-2 d-md-block">
                                <button type="button" id="users-btnStartCamera" class="btn btn-outline-dark rounded-pill px-4" onclick="UsersApp.startCamera()">
                                    <i class="bi bi-camera-video me-2"></i>Iniciar Escaneo
                                </button>
                                <button type="button" id="users-btnStopCamera" class="btn btn-sfred text-white rounded-pill px-4 d-none" onclick="UsersApp.stopCamera()">
                                    <i class="bi bi-camera-video me-2"></i>Detener
                                </button>
                                <button type="button" id="users-btnCaptureFace" class="btn btn-sfblue text-white rounded-pill px-4 d-none" onclick="UsersApp.captureFace()">
                                    <i class="bi bi-camera-fill me-2"></i>Capturar (<span id="capCount">1</span>/3)
                                </button>
                            </div>

                            <div id="faceMessage" class="small mt-2 fw-bold"></div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal-footer border-0 bg-white">
        <button type="button" class="btn btn-dark rounded-pill px-5 fw-bold" onclick="UsersApp.saveUser()">
            <i class="bi bi-check2-circle me-2"></i>Guardar Usuario
        </button>
    </div>
</template>