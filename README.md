# Sistema de Stock y Ventas de Bebidas

Sistema web para que un local de bebidas controle su stock y registre pedidos. Desarrollado como proyecto final de la materia Modelado de Software.

![Catálogo de bebidas](capturas/bebidas.jpg)

## Funcionalidades

El sistema tiene dos partes:

### Tienda (para los compradores, sin registrarse)
- **Catálogo** con filtro por categoría (aguas, cervezas, gaseosas, isotónicas y jugos) y stock visible.
- **Carrito de pedidos** guardado en la sesión, con cantidades editables, subtotales y total calculado automáticamente.
- **Control de stock**: no se puede pedir más de lo disponible; al confirmar se vuelve a verificar el stock y el precio dentro de una transacción, así un pedido se guarda completo o no se guarda.
- **Resumen del pedido** confirmado, visible solo para quien lo hizo.

### Panel de administración (con usuario y contraseña)
- **Inicio de sesión** con contraseñas guardadas como hash (`password_hash`), bloqueo temporal tras 5 intentos fallidos y cierre de sesión por inactividad.
- **Resumen del día**: productos a la venta, productos con stock bajo, pedidos y monto vendido hoy.
- **Productos**: alta, edición y opción de ocultarlos de la tienda sin borrarlos (los pedidos viejos los siguen referenciando).
- **Pedidos**: listado con fecha, productos, total y estado.
- Formularios protegidos contra CSRF y acceso discreto desde el pie de la tienda ("Acceso del personal").

## Tecnologías

- PHP (sin frameworks), con `mysqli` y consultas preparadas
- MySQL
- HTML5 y CSS3

## Capturas

| Carrito | Alta de producto | Pedidos |
|---|---|---|
| ![Carrito](capturas/carrito.jpg) | ![Alta de producto](capturas/agregar-producto.jpg) | ![Pedidos](capturas/pedidos.jpg) |

## Cómo ejecutarlo

1. Instalar [XAMPP](https://www.apachefriends.org/) e iniciar **Apache** y **MySQL**.
2. Copiar esta carpeta dentro de `C:\xampp\htdocs\`.
3. Abrir phpMyAdmin (`http://localhost/phpmyadmin`) e importar `database.sql`. Crea la base `bebidas_db` con datos de ejemplo. La base usa `utf8mb4` para poder guardar emojis.
4. Crear el usuario administrador desde una consola, en la carpeta del proyecto:
   ```
   C:\xampp\php\php.exe crear_admin.php
   ```
   Pide un usuario y una contraseña (mínimo 8 caracteres). Ejecutándolo de nuevo con el mismo usuario se cambia la contraseña.
5. Entrar a la tienda en `http://localhost/<nombre-de-la-carpeta>/` y al panel en `http://localhost/<nombre-de-la-carpeta>/admin/`.

La conexión usa por defecto la configuración de XAMPP (`root` sin contraseña). Para otro servidor se pueden definir las variables de entorno `DB_HOST`, `DB_USER`, `DB_PASS` y `DB_NAME`.

## Estructura

```
├── index.php               # Tienda: catálogo y filtro por categoría
├── carrito.php             # Tienda: carrito y confirmación del pedido
├── resumen.php             # Tienda: resumen del pedido confirmado
├── admin/
│   ├── login.php           # Inicio de sesión del personal
│   ├── index.php           # Resumen del día y listado de productos
│   ├── producto.php        # Alta y edición de productos
│   ├── pedidos.php         # Listado de pedidos
│   └── logout.php          # Cierre de sesión
├── includes/
│   ├── db.php              # Conexión a la base de datos
│   ├── sesion.php          # Sesión con cookie segura
│   ├── auth.php            # Login, bloqueo por intentos y protección CSRF
│   └── funciones.php       # Formato de precios y escape de HTML
├── crear_admin.php         # Script de consola para crear el administrador
├── assets/style.css        # Estilos
└── database.sql            # Estructura y datos de ejemplo
```

## Autor

Miguel Alesandro Villalba · [Portfolio](https://alesandrovi.github.io/portafolio/) · [LinkedIn](https://www.linkedin.com/in/alesandro-villalba-bb6508367)
