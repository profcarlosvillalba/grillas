# Control de equipos — Laravel 13

Sistema completo de grillas diarias inspirado en la planilla enviada.
PHP 8.3 o superior. Laravel 13.35.0. Blade, CSS y JavaScript locales; SQLite por defecto.
La interfaz está en español. No usa API de IA, servicios de pago ni compilación con Node.

## Abrirlo, paso a paso

1. Descomprimí el ZIP. Vas a ver una carpeta llamada **grillas-equipos**.
2. Entrá en esa carpeta. Deben verse los archivos **instalar.php**, **iniciar.php** y **artisan**.
3. Abrí una terminal ahí: en Linux, clic derecho → Abrir en terminal; en Windows, clic derecho → Abrir en Terminal.
4. Escribí **php -v**. Debe indicar PHP 8.3 o superior.
5. Ejecutá:

~~~bash
php instalar.php
php iniciar.php
~~~

6. Dejá esa terminal abierta. En el navegador abrí **http://127.0.0.1:8000**.
7. En una instalación nueva, ingresá con **admin@grillas.test** y contraseña **Admin12345!**. Si ya tenías usuarios, se conservan sus credenciales.
8. Ya hay técnicos, móviles y 35 equipos de ejemplo de la foto. Para verlos, elegí **26/07/2025** en Grilla diaria. Podés agregar tus datos desde los catálogos.
9. En Grilla diaria tocá Nueva grilla y seleccioná fecha, productivo (work) y dupla opcional. El código sale del legajo del productivo.
10. Al final de la tabla elegí HD, WIFI u otro tipo, enfocá MAC / Serie y escaneá. También podés escribir y presionar Tab o Enter para guardar y continuar.
11. Cuando un equipo se asigne a una OT, completá OT y Fecha OT y tocá Guardar en esa fila.

Para detener el sistema: Ctrl+C en la terminal.
Para abrirlo otro día: ejecutá **php iniciar.php** desde la misma carpeta.

El ZIP incluye vendor/ con las dependencias para evitar una descarga inicial.
Si vendor/ falta o querés reinstalarlo, ejecutá **composer install** antes de instalar.
Los archivos CSS y JavaScript están en public/assets: no hace falta npm.

## Qué representa cada dato

| Dato | Significado |
| --- | --- |
| Fecha registro | Fecha del escaneo diario. Una grilla por fecha y productivo. |
| Legajo | Código único de cada técnico, guardado como texto para conservar ceros iniciales. |
| Móvil / Código | Legajo del productivo de esa grilla; se completa automáticamente. |
| Productivo / Work | Técnico responsable obligatorio. Su legajo encabeza la planilla. |
| Dupla | Segundo técnico opcional; debe ser distinto del responsable. |
| Equipo | HD, WIFI, DCT, ISDBT, EXTENSOR, USB, ONT o un tipo que agregues. |
| MAC / Serie | Identificador escaneado. Admite también las series alfanuméricas HD de tu foto. |
| OT | Orden a la que se asignó ese equipo; inicialmente vacía. |
| Fecha OT | Fecha real de esa asignación; inicialmente vacía. |

Una MAC puede registrarse nuevamente al día siguiente. Dentro del mismo día se rechaza una MAC duplicada, incluso entre móviles distintos.
Las MAC se normalizan a mayúsculas y sin espacios, puntos, guiones o dos puntos. Se aceptan 4 a 64 letras o números.
El lector admitido es el que funciona como teclado USB o Bluetooth, con Tab o Enter al finalizar. No requiere una API ni drivers dentro de Laravel.
Si Raúl (207) es productivo y Exequiel (211) es dupla, la planilla dice **207 RAUL - EXEQUIEL**. Al intercambiar roles dice **211 EXEQUIEL - RAUL**.
La dupla puede cambiar cada día. No se elige un código de móvil separado del productivo.
Las grillas guardan copias de los nombres y códigos para conservar el historial si luego se modifica un catálogo.
OT y Fecha OT se completan juntas. Para quitar una asignación, se dejan ambas vacías.
Cambiar una OT de un día no modifica automáticamente otros escaneos históricos del mismo equipo.

## Funciones incluidas

- Inicio de sesión y configuración inicial del administrador.
- Usuarios administradores y operadores; cuentas activas/inactivas.
- Catálogos editables de técnicos con legajo único y tipos de equipo.
- Grilla diaria por legajo del productivo; roles editables con vista previa e intercambio.
- Carga continua con Tab o Enter desde el pie de la tabla, manteniendo tipo y foco.
- Cola de escaneos: conserva cada código mientras se guarda y permite corregir o reintentar errores.
- Asignación de OT y fecha en la misma fila, con guardado explícito.
- Corrección de tipo, MAC y OT; eliminación confirmada de registros.
- Prevención de duplicados en validación y por índice único de base de datos.
- Historial por MAC/serie u OT, rango de fechas, móvil, tipo y estado.
- Excel XLSX con el formato de la foto, grillas por móvil y ceros iniciales conservados.
- Impresión de grillas y diseño adaptable a pantallas pequeñas.
- Protección de cambios simultáneos: una fila modificada por otra persona exige actualizar antes de guardar.
- Respaldo SQLite y recuperación de contraseña desde terminal.

Los operadores pueden consultar, registrar, corregir y eliminar equipos/grillas vacías. Solo administradores gestionan catálogos y usuarios.
Los tipos desactivados y los técnicos inactivos siguen visibles en el historial. Los códigos anteriores se conservan para los filtros.

## Cargar las semillas con los comandos de Laravel

Con el proyecto ya configurado (su .env, clave y base de datos), ejecutá:

~~~bash
php artisan migrate
php artisan db:seed
~~~

DatabaseSeeder llama, en orden, a TiposEquipoSeeder, UsuariosSeeder, TecnicosSeeder, MovilesSeeder y PlanillasSeeder.
La instalación nueva recibe un administrador, 12 técnicos con legajo, 7 códigos históricos, 7 tipos de equipo y las 7 grillas / 35 equipos de la foto.
RAUL tiene legajo **207** y EXEQUIEL **211**. HIPOLITO 205, RICHARD 208 y HUGO 209 siguen los códigos de la referencia. Los legajos de las duplas que la foto no informa empiezan con **DEMO-**: son ejemplos y deben reemplazarse por los reales en Técnicos y legajos.
La foto histórica contiene RAUL en 207 y 213 y EXEQUIEL en 211 y 215. Esos bloques de ejemplo conservan sus encabezados originales para reproducir la referencia; las grillas nuevas usan siempre el legajo del productivo.
Los registros de ejemplo conservan la fecha de la foto: **26/07/2025**. Elegí esa fecha para verlos y exportarlos.
Las OT y sus fechas se cargan vacías. Podés asignarlas después para probar el sistema.
Repetir db:seed conserva los datos existentes, las contraseñas, los legajos ya informados y las OT editadas. Completa los legajos de ejemplo solo si estaban vacíos y el código está libre. No borra ni reemplaza tu base.
Si ya existe una cuenta administradora, se usa esa cuenta para crear las grillas de ejemplo y no se agrega el administrador inicial.

Usuario inicial, solo en una instalación sin usuarios:

- Correo: **admin@grillas.test**
- Contraseña: **Admin12345!**

php instalar.php también ejecuta migrate y db:seed. No hace falta migrate:fresh.
Si querés cargar únicamente los tipos de equipo, usá **php artisan db:seed --class=TiposEquipoSeeder**.

## Actualizar una instalación anterior

1. Guardá un respaldo con **php artisan datos:respaldar**.
2. Copiá los archivos de esta versión sobre el proyecto existente, conservando **.env** y **database/database.sqlite** (el ZIP no incluye esos archivos). Si usás MySQL, conservá su configuración y base.
3. Desde la carpeta del proyecto ejecutá:

~~~bash
php artisan migrate
php artisan db:seed
php artisan optimize:clear
~~~

La nueva migración agrega legajo sin borrar técnicos, usuarios, equipos ni OT.
Completá en Técnicos y legajos los códigos pendientes de tus técnicos anteriores y reemplazá los legajos DEMO por los reales.
Los nombres y códigos guardados en las grillas anteriores se conservan. Si cambiás el productivo de una grilla, el código pasa al legajo del nuevo work; sus equipos y OT se mantienen.
No se permite cambiarlo a un productivo que ya tenga otra grilla ese día.

## Carga continua con teclado

El campo MAC / Serie está al final de cada tabla. Elegí el tipo una vez y escribí o escaneá el código.
**Tab o Enter** agrega la fila, vacía el campo y mantiene el foco para cargar el siguiente código.
Los envíos se guardan en orden y cada fila muestra En cola / Guardando hasta recibir confirmación; recién entonces se actualizan los totales.
Si falla la conexión, hay un duplicado o el código no es válido, la fila conserva el código y su error. Usá Reintentar, Corregir o Descartar.
Antes de salir, resolvé las filas sin guardar. El navegador avisa si aún hay envíos pendientes.
Shift+Tab, Tab con MAC vacía y Tab en los otros controles mantienen la navegación normal.
El botón Registrar equipo sigue disponible para carga con mouse o sin JavaScript.

## Formato de Exportar Excel

La salida reproduce las grillas de la foto: fecha arriba, legajo del productivo a la izquierda y técnicos en un encabezado combinado (productivo - dupla).
La fecha aparece al inicio y se repite en la segunda grilla, como en la foto.
Cada bloque tiene exactamente cuatro columnas: **EQUIPOS, MAC, OT, FECHA**.
Las grillas se colocan una debajo de otra, con bordes negros, fondo blanco y encabezados en negrita.
La columna OT mantiene el espacio amplio para completar la orden. FECHA es la fecha de asignación a la OT.
Las MAC y OT se guardan como texto para conservar ceros iniciales; las fechas son fechas nativas de Excel.
La impresión queda configurada en A4 vertical, ajustada a una página de ancho. La cantidad de páginas de alto depende de los registros.
Los filtros de la pantalla se aplican antes de exportar. No se agregan columnas, totales ni colores que no aparecen en la planilla.
Una grilla creada sin equipos se exporta con su encabezado y una fila vacía.
Dentro de **ejemplos/** se incluye un Excel generado desde las semillas para revisar el resultado.

## Si PHP indica que falta una extensión

Ejecutá **php --ini** para ver qué php.ini usa tu terminal. Editá ese archivo y habilitá las extensiones instaladas:
pdo_sqlite, sqlite3, mbstring, openssl, curl, fileinfo, zip, dom y xml.
En Windows, habitualmente se habilitan quitando el punto y coma de extension=pdo_sqlite, extension=sqlite3, extension=mbstring y extension=zip.
No agregues líneas duplicadas. Después cerrá y abrí la terminal y verificá con **php -m**.
En Linux, instalá las extensiones de la misma versión de PHP que usás.
Si no reconoce el comando php, agregá tu instalación de PHP al PATH.

## Guardar una copia de los datos

~~~bash
php artisan datos:respaldar
~~~

El comando muestra la ruta del respaldo en storage/app/backups.
Copiá también .env a una ubicación privada: contiene la clave necesaria para las sesiones cifradas.
La base principal es database/database.sqlite.
Para restaurar una copia, detené el servidor, conservá una copia de la base actual y reemplazá database/database.sqlite con el respaldo.
No uses migrate:fresh: borra los datos.

## Olvidé mi contraseña

Desde la carpeta del proyecto:

~~~bash
php artisan usuario:clave tu-correo@ejemplo.com
~~~

La terminal solicita la nueva contraseña dos veces sin mostrarla.

## Usar MySQL en lugar de SQLite

Configurá en .env:

~~~ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=grillas_equipos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_clave
~~~

Creá previamente esa base de datos y habilitá pdo_mysql. Después ejecutá **php instalar.php**.
Cambiar de motor no copia los datos existentes: primero exportalos/migrálos si necesitás conservarlos.
El esquema usa índices y claves foráneas compatibles con SQLite y MySQL.

## Uso en otra computadora / hosting

El arranque incluido usa el servidor de desarrollo de PHP en tu computadora.
Para uso compartido permanente, instalá el proyecto en un servidor PHP con HTTPS y apuntá la raíz web a **public/**.
Usá APP_ENV=production, APP_DEBUG=false, APP_URL con tu dominio y SESSION_SECURE_COOKIE=true.
La base es compartida por los usuarios de esta instalación; no hay conexión automática a Telecom ni validación externa de OT.
Nunca sirvas la carpeta raíz del proyecto: .env y la base deben quedar fuera del acceso web.

## Soporte y verificación

Laravel no ofrece una edición LTS diferenciada actualmente. Se eligió Laravel 13 por su soporte de seguridad hasta el 17/03/2028.
Fuente: https://laravel.com/framework/docs/13.x/releases
Laravel requiere PHP 8.3. El lock se resolvió con plataforma PHP 8.3.29 para evitar dependencias que exijan PHP 8.4.

Pruebas funcionales incluidas:

~~~bash
php artisan test
~~~

Las pruebas usan SQLite en memoria y no alteran la base real.
Proyecto MIT; las dependencias conservan sus respectivas licencias.
