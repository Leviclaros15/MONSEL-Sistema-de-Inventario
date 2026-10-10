# MONSEL S.A. de C.V. — Sistema de Inventario (Prototipo)

Prototipo funcional en **PHP + MySQL** del sistema de inventario para
control de materiales y montajes eléctricos, basado en los mockups
proporcionados.

## Pantallas incluidas

1. Inicio de sesión
2. Panel principal (Dashboard) con KPIs reales
3. Inventario (listado, búsqueda, agregar/editar/eliminar material)
4. Registrar entrada / salida (con actualización automática de stock)
5. Proyectos realizados (listado, búsqueda, agregar/eliminar proyecto)

El formulario "Agregar material" del mockup 6 está integrado como
modal dentro de la pantalla de Inventario.

## Usuario de prueba

| Usuario | Contraseña |
|---|---|
| `emely`  | `123456` |
| `carlos` | `123456` |

## Estructura del proyecto

```
monsel_inventario/
├── config/
│   └── db.php                 Conexión a la base de datos (PDO)
├── includes/
│   ├── auth.php                Protección de sesión + mensajes flash
│   ├── sidebar.php              Menú lateral
│   └── topbar.php                Barra superior (usuario en sesión)
├── assets/
│   ├── css/style.css           Estilos de todo el sistema
│   └── js/main.js               Lógica del modal de materiales
├── database/
│   └── monsel_inventario.sql   Script de creación + datos de prueba
├── index.php                    Login
├── logout.php                   Cerrar sesión
├── dashboard.php                 Panel principal
├── inventario.php                Inventario + modal agregar/editar
├── material_guardar.php          Guarda/actualiza un material (POST)
├── material_eliminar.php         Elimina un material (POST)
├── movimiento.php                Registrar entrada/salida + historial
├── movimiento_guardar.php        Procesa el movimiento y actualiza stock
├── proyectos.php                  Proyectos realizados + modal nuevo
├── proyecto_guardar.php           Guarda un proyecto nuevo (POST)
└── proyecto_eliminar.php          Elimina un proyecto (POST)
```

## Funcionalidad ya implementada

- Autenticación con sesiones y contraseñas encriptadas (`password_hash`
  / `password_verify`).
- Todas las consultas usan sentencias preparadas (PDO), para evitar
  inyección SQL.
- Al registrar una **entrada**, el stock del material aumenta. Al
  registrar una **salida**, disminuye — y el sistema **bloquea** la
  operación si no hay stock suficiente.
- Cuando el stock de un material llega a 0, su estado cambia
  automáticamente a **Agotado**; si vuelve a tener existencias, regresa
  a **Disponible**.
- El dashboard calcula sus KPIs en tiempo real desde la base de datos
  (no son datos fijos).
- Búsqueda en inventario y en proyectos.
- Mensajes de éxito/error después de cada acción (agregar, editar,
  eliminar, registrar movimiento).

## Posibles siguientes pasos

- Agregar roles (administrador vs. técnico) con permisos distintos.
- Agregar edición de proyectos (actualmente solo se crean y eliminan).
- Agregar alertas visuales cuando un material llegue a su stock mínimo
  (la columna `stock_minimo` ya existe en la base de datos, lista para
  usarse).
- Agregar reportes/exportación (PDF o Excel) para la sección de
  Reportes del mockup original.
