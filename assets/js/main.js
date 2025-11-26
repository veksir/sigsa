// Actualizar fecha y hora en tiempo real
function updateDateTime() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    const dateTimeString = now.toLocaleDateString('es-ES', options);
    
    const element = document.getElementById('currentDateTime');
    if (element) {
        element.textContent = dateTimeString;
    }
}

// Actualizar cada minuto
if (document.getElementById('currentDateTime')) {
    updateDateTime();
    setInterval(updateDateTime, 60000);
}

// Confirmar eliminaciones
document.querySelectorAll('[data-confirm]').forEach(element => {
    element.addEventListener('click', function(e) {
        if (!confirm(this.getAttribute('data-confirm'))) {
            e.preventDefault();
        }
    });
});

// Auto-ocultar alertas después de 5 segundos
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);

// Validación de formularios
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.style.borderColor = 'var(--danger-color)';
            } else {
                field.style.borderColor = '';
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Por favor complete todos los campos requeridos');
        }
    });
});

// Búsqueda en tablas
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (input && table) {
        input.addEventListener('keyup', function() {
            const filter = this.value.toUpperCase();
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j].textContent.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                
                row.style.display = found ? '' : 'none';
            }
        });
    }
}

// Calcular totales en órdenes de servicio
function calcularTotalOrden() {
    const costoManoObra = parseFloat(document.getElementById('costo_mano_obra')?.value || 0);
    let totalRepuestos = 0;
    
    document.querySelectorAll('.repuesto-row').forEach(row => {
        const cantidad = parseFloat(row.querySelector('[name="cantidad[]"]')?.value || 0);
        const precio = parseFloat(row.querySelector('[name="precio_unitario[]"]')?.value || 0);
        totalRepuestos += cantidad * precio;
    });
    
    const totalElement = document.getElementById('total_orden');
    if (totalElement) {
        totalElement.textContent = (costoManoObra + totalRepuestos).toFixed(2);
    }
}

// Agregar nueva fila de repuesto en orden de servicio
function agregarRepuesto() {
    const container = document.getElementById('repuestos-container');
    if (container) {
        const row = document.createElement('div');
        row.className = 'repuesto-row';
        row.innerHTML = `
            <select name="id_repuesto[]" required>
                <option value="">Seleccione un repuesto</option>
                ${window.repuestosData.map(r => 
                    `<option value="${r.id}" data-precio="${r.precio}">${r.nombre} - $${r.precio}</option>`
                ).join('')}
            </select>
            <input type="number" name="cantidad[]" min="1" value="1" required onchange="calcularTotalOrden()">
            <input type="number" name="precio_unitario[]" step="0.01" min="0" required onchange="calcularTotalOrden()">
            <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove(); calcularTotalOrden();">
                <i class="fas fa-trash"></i>
            </button>
        `;
        container.appendChild(row);
        
        // Actualizar precio cuando se selecciona un repuesto
        const select = row.querySelector('select');
        const precioInput = row.querySelector('[name="precio_unitario[]"]');
        select.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            if (option.dataset.precio) {
                precioInput.value = option.dataset.precio;
                calcularTotalOrden();
            }
        });
    }
}

// Imprimir reporte
function imprimirReporte() {
    window.print();
}

// Exportar tabla a CSV
function exportarCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        cols.forEach(col => csvRow.push(col.textContent));
        csv.push(csvRow.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.click();
}
