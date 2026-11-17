document.addEventListener('DOMContentLoaded', () => {
    // Botón del ojo para mostrar u ocultar la contraseña
    document.querySelectorAll('.ver-clave').forEach(boton => {
        boton.addEventListener('click', () => {
            const campo = document.getElementById(boton.dataset.objetivo);
            const visible = campo.type === 'text';
            campo.type = visible ? 'password' : 'text';
            boton.innerHTML = visible ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
    });

    iniciarPedido();
    iniciarModalesFormulario();
    iniciarModalMovimiento();
});

function unidadTexto(cantidad, unidad) {
    if (cantidad === 1 || unidad === 'kg') return unidad;
    return unidad + (/[aeiou]$/.test(unidad) ? 's' : 'es');
}

// Pantalla "Hacer un pedido": botones de más y menos, resumen y total
function iniciarPedido() {
    const formulario = document.getElementById('formPedido');
    if (!formulario) return;

    const filas = formulario.querySelectorAll('.fila-producto');
    const lista = document.getElementById('listaResumen');
    const total = document.getElementById('totalPedido');
    const campoDireccion = document.getElementById('campoDireccion');

    const formato = n => '$' + n.toFixed(2);

    function actualizar() {
        let suma = 0;
        let html = '';
        filas.forEach(fila => {
            const cantidad = parseFloat(fila.querySelector('input').value) || 0;
            if (cantidad > 0) {
                const subtotal = cantidad * parseFloat(fila.dataset.precio);
                suma += subtotal;
                html += `<div class="linea-resumen"><span>${fila.dataset.nombre} · ${cantidad} ${unidadTexto(cantidad, fila.dataset.unidad)}</span><strong>${formato(subtotal)}</strong></div>`;
            }
        });
        lista.innerHTML = html || '<p class="texto-secundario small mb-0">Todavía no agregas productos.</p>';
        total.textContent = formato(suma) + ' MXN';
    }

    filas.forEach(fila => {
        const campo = fila.querySelector('input');
        const paso = parseFloat(campo.step) || 1;
        fila.querySelector('.mas').addEventListener('click', () => {
            campo.value = Math.min(50, (parseFloat(campo.value) || 0) + paso);
            actualizar();
        });
        fila.querySelector('.menos').addEventListener('click', () => {
            campo.value = Math.max(0, (parseFloat(campo.value) || 0) - paso);
            actualizar();
        });
        campo.addEventListener('input', actualizar);
    });

    formulario.querySelectorAll('input[name="tipo_entrega"]').forEach(opcion => {
        opcion.addEventListener('change', () => {
            campoDireccion.classList.toggle('d-none', opcion.value !== 'Domicilio' || !opcion.checked);
        });
    });

    actualizar();
}

// Ventanas de Inventario y Productos: la misma ventana sirve para crear y para editar
function iniciarModalesFormulario() {
    ['modalInsumo', 'modalProducto'].forEach(id => {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.addEventListener('show.bs.modal', evento => {
            const boton = evento.relatedTarget;
            if (!boton) return;
            const formulario = modal.querySelector('form');
            const accion = boton.dataset.accion || 'crear';
            const titulo = modal.querySelector('.modal-title');

            formulario.reset();
            formulario.accion.value = accion;
            titulo.textContent = accion === 'editar' ? titulo.dataset.tituloEditar : titulo.dataset.tituloCrear;
            modal.querySelectorAll('.nota-editar').forEach(nota => nota.classList.toggle('d-none', accion !== 'editar'));

            const campoId = formulario.querySelector('[name^="id_"][type="hidden"]');
            campoId.value = accion === 'editar' ? boton.dataset.id : '';

            if (accion === 'editar') {
                // Cada data-* del botón llena el campo del formulario con el mismo nombre
                Object.entries(boton.dataset).forEach(([clave, valor]) => {
                    const campo = formulario.elements[clave];
                    if (!campo || clave === 'accion' || clave === 'id') return;
                    if (campo.type === 'checkbox') {
                        campo.checked = valor === '1';
                    } else {
                        campo.value = valor;
                    }
                });
            }
        });
    });
}

// Ventana para registrar entradas y salidas de un insumo
function iniciarModalMovimiento() {
    const modal = document.getElementById('modalMovimiento');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', evento => {
        const boton = evento.relatedTarget;
        const formulario = modal.querySelector('form');
        formulario.reset();
        formulario.id_insumo.value = boton.dataset.id;
        modal.querySelector('.mov-nombre').textContent = boton.dataset.nombre;
        modal.querySelector('.mov-existencia').textContent = boton.dataset.existencia + ' ' + boton.dataset.unidad;
    });
}
