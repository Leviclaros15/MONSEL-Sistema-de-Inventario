function abrirModalAgregar() {
  const form = document.querySelector('#modalMaterial form');
  form.reset();
  document.getElementById('f_id_material').value = '';
  document.getElementById('modalTitulo').textContent = 'Agregar material';
  document.getElementById('modalMaterial').classList.add('open');
}

function abrirModalEditar(material) {
  document.getElementById('modalTitulo').textContent = 'Editar material';
  document.getElementById('f_id_material').value = material.id_material;
  document.getElementById('f_codigo').value = material.codigo;
  document.getElementById('f_nombre').value = material.nombre;
  document.getElementById('f_id_categoria').value = material.id_categoria;
  document.getElementById('f_unidad_medida').value = material.unidad_medida;
  document.getElementById('f_cantidad').value = material.cantidad;
  document.getElementById('f_ubicacion').value = material.ubicacion;
  document.getElementById('f_estado').value = material.estado;
  document.getElementById('f_observaciones').value = material.observaciones || '';
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
