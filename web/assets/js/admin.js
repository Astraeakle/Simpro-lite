// File: web/assets/js/admin.js
/**
 * Funciones para el panel de administración
 */

document.addEventListener('DOMContentLoaded', function() {
    // Gestión de usuarios
    const tablaUsuarios = document.getElementById('tablaUsuarios');
    if (tablaUsuarios) {
        // Lógica para editar usuarios
        tablaUsuarios.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-editar')) {
                const id = e.target.dataset.id;
                cargarUsuarioParaEditar(id);
            }
        });
    }
    
    // Gestión de configuración
    const formConfig = document.getElementById('formConfig');
    if (formConfig) {
        formConfig.addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch('/api/v1/config', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
                },
                body: JSON.stringify({
                    config: Object.fromEntries(new FormData(formConfig))
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlerta('Configuración actualizada', 'success');
                }
            });
        });
    }
});

function cargarUsuarioParaEditar(id) {
    fetch(`/api/v1/usuarios/${id}`, {
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Llenar modal de edición
            const usuario = data.data;
            document.getElementById('editNombre').value = usuario.nombre_completo;
            document.getElementById('editUsuario').value = usuario.nombre_usuario;
            document.getElementById('editRol').value = usuario.rol;
            document.getElementById('editEstado').value = usuario.estado;
            
            // Mostrar modal
            new bootstrap.Modal(document.getElementById('modalEditarUsuario')).show();
        }
    });
}

function mostrarAlerta(mensaje, tipo = 'success') {
    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
    alerta.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.getElementById('alertContainer') || document.body;
    container.prepend(alerta);
    
    setTimeout(() => {
        alerta.classList.remove('show');
        setTimeout(() => alerta.remove(), 150);
    }, 5000);
}