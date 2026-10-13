# Verificación del proyecto actualizado

- Laravel 13.35.0 y PHP 8.3.29.
- 43 pruebas PHPUnit aprobadas, con 345 verificaciones.
- 11 comprobaciones de teclado y DOM sobre el HTML generado por Laravel.
- Legajo obligatorio y único al crear o editar un técnico; conservación de ceros iniciales.
- El código de una nueva grilla sale del legajo del productivo, incluso si una petición intenta enviar un código distinto.
- RAUL productivo y EXEQUIEL dupla: 207 RAUL - EXEQUIEL. Roles invertidos: 211 EXEQUIEL - RAUL.
- Intercambiar los roles conserva los registros, sus OT y fechas; se rechaza un work que ya tenga otra grilla ese día.
- Vista previa del encabezado, intercambio de roles y trabajo individual sin dupla.
- La migración se verificó sobre una base con datos anteriores: conserva usuarios, contraseñas, técnicos, encabezados, equipos y OT.
- migrate y db:seed cargan una instalación nueva y pueden repetirse sin duplicar los ejemplos ni reemplazar legajos informados, contraseñas, OT o estados de los catálogos.
- Los legajos desconocidos de las duplas se distinguen con el prefijo DEMO; el catálogo permite reemplazarlos por los reales.
- Tab y Enter desde MAC agregan una fila y conservan el tipo y el foco; Shift+Tab y Tab vacío mantienen la navegación normal.
- Tres escaneos seguidos antes de la primera respuesta conservan orden, códigos y tipos.
- Un código con error queda visible para reintentar, corregir o descartar. Una respuesta tardía no borra el siguiente código escrito.
- Los totales se actualizan tras confirmar el guardado; los envíos pendientes activan el aviso de salida del navegador.
- Integración con servidor PHP real: inicio de sesión, CSRF, creación por work, escaneo, duplicados, OT, intercambio de roles y exportación.
- El XLSX mantiene EQUIPOS, MAC, OT y FECHA; el legajo a la izquierda y productivo - dupla en el encabezado combinado.
- Plantilla y Excel de ejemplo conservados sin modificar sus estilos. Se verificaron encabezados y ceros iniciales en el Excel descargado tras intercambiar los roles.
- Instalación desde una carpeta descomprimida verificada: crea configuración, tablas, legajos y los 35 registros; repetir db:seed mantiene las cantidades.
- El instalador comprueba el controlador PDO disponible para el motor configurado y comunica los errores con código de salida distinto de cero.
- Vistas Blade compiladas, JavaScript validado y archivos locales servidos correctamente.

Las pruebas de teclado usan eventos DOM; no se probó un lector físico ni se hizo una nueva revisión visual de navegador. La comparación visual del Excel con la foto corresponde a la verificación anterior de la misma plantilla. Las pruebas funcionales usan SQLite; no se ejecutó una instancia MySQL. El ZIP no incluye .env ni bases de datos de trabajo.
