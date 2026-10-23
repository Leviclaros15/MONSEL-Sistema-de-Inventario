function alCambiarCategoriaOUnidadModal() {
  const catSel = document.getElementById('f_id_categoria');
  const unitSel = document.getElementById('f_unidad_medida');
  const inputStockMin = document.getElementById('f_stock_minimo');

  const catText = (catSel && catSel.selectedIndex >= 0) ? (catSel.options[catSel.selectedIndex].dataset.nombre || catSel.options[catSel.selectedIndex].text || '').toLowerCase() : '';
  const unitVal = (unitSel && unitSel.value ? unitSel.value : '').toLowerCase();

  const esHerramientaOUnidad = catText.includes('herramienta') || unitVal.includes('unidad') || unitVal.includes('pieza');

  if (esHerramientaOUnidad) {
    if (inputStockMin) {
      inputStockMin.step = '1';
      inputStockMin.min = '0';
      if (inputStockMin.value && inputStockMin.value % 1 !== 0) {
        inputStockMin.value = Math.round(inputStockMin.value);
      }
    }
  } else {
    if (inputStockMin) {
      inputStockMin.step = '0.01';
      inputStockMin.min = '0';
    }
  }
}

function abrirModalAgregar() {
  const form = document.querySelector('#modalMaterial form');
  form.reset();
  document.getElementById('f_id_material').value = '';
  document.getElementById('f_stock_minimo').value = '0';
  document.getElementById('f_unidad_medida').value = '';
  document.getElementById('modalTitulo').textContent = 'Agregar material';
  alCambiarCategoriaOUnidadModal();
  document.getElementById('modalMaterial').classList.add('open');
}

function abrirModalEditar(material) {
  document.getElementById('modalTitulo').textContent = 'Editar material';
  document.getElementById('f_id_material').value = material.id_material;
  document.getElementById('f_codigo').value = material.codigo;
  document.getElementById('f_nombre').value = material.nombre;
  document.getElementById('f_id_categoria').value = material.id_categoria;

  // Asignar valor a la lista de unidad_medida (o crear opción si era personalizada)
  const unitSelect = document.getElementById('f_unidad_medida');
  const unitVal = (material.unidad_medida || '').toLowerCase();
  let found = false;
  for (let i = 0; i < unitSelect.options.length; i++) {
    if (unitSelect.options[i].value.toLowerCase() === unitVal) {
      unitSelect.selectedIndex = i;
      found = true;
      break;
    }
  }
  if (!found && unitVal) {
    const opt = document.createElement('option');
    opt.value = material.unidad_medida;
    opt.text = material.unidad_medida;
    unitSelect.add(opt);
    unitSelect.value = material.unidad_medida;
  }

  document.getElementById('f_stock_minimo').value = material.stock_minimo || 0;
  document.getElementById('f_ubicacion').value = material.ubicacion;
  document.getElementById('f_observaciones').value = material.observaciones || '';

  alCambiarCategoriaOUnidadModal();
  document.getElementById('modalMaterial').classList.add('open');
}

function cerrarModal() {
  document.getElementById('modalMaterial').classList.remove('open');
}

// Cierra el modal si se hace clic fuera del cuadro
document.addEventListener('click', function (e) {
  const overlay = document.getElementById('modalMaterial');
  if (overlay && e.target === overlay) {
    cerrarModal();
  }
});

// Control de apertura/cierre del sidebar responsive
function toggleSidebar(forceState) {
  const sidebar = document.getElementById('appSidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  if (!sidebar) return;

  const shouldOpen = typeof forceState === 'boolean' 
    ? forceState 
    : !sidebar.classList.contains('open');

  sidebar.classList.toggle('open', shouldOpen);
  if (backdrop) {
    backdrop.classList.toggle('active', shouldOpen);
  }
  document.body.style.overflow = shouldOpen ? 'hidden' : '';
}

