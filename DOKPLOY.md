# Guía de Despliegue en Dokploy

## Requisitos previos
- Cuenta en Dokploy con servidor configurado
- Repositorio en GitHub/GitLab con el código de este proyecto
- Dominio apuntando al servidor de Dokploy

---

## Paso 1 — Crear la aplicación en Dokploy

1. Accede a tu panel de Dokploy.
2. Haz clic en **New Application** → **Docker Compose**.
3. Conecta el repositorio de Git donde subiste este proyecto.
4. Dokploy detectará automáticamente el `docker-compose.yml`.

---

## Paso 2 — Configurar las Variables de Entorno

En la sección **Environment Variables** de tu aplicación en Dokploy, pega el siguiente bloque y completa cada valor:

```env
APP_NAME=StockSekai
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://tu-dominio.com

LOG_CHANNEL=stderr
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=stocksekai
DB_USERNAME=stocksekai
DB_PASSWORD=CAMBIA_ESTA_PASSWORD_UNICA_Y_SEGURA

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.tuproveedor.com
MAIL_PORT=587
MAIL_USERNAME=tu@email.com
MAIL_PASSWORD=tu_password_smtp
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tu-dominio.com
MAIL_FROM_NAME=StockSekai

GOOGLE_CLIENT_ID=tu_google_client_id
GOOGLE_CLIENT_SECRET=tu_google_client_secret
GOOGLE_REDIRECT_URL=https://tu-dominio.com/auth/google/callback
```

> **IMPORTANTE:**
> - `APP_KEY` se puede dejar en blanco la primera vez; el entrypoint del contenedor **NO** lo genera automáticamente — debes generarlo manualmente (ver Paso 5).
> - `DB_PASSWORD` debe ser una contraseña única y segura.
> - `APP_URL` debe ser exactamente `https://tu-dominio.com` (sin barra final).
> - Si no usas Google Login, deja las variables `GOOGLE_*` vacías.
> - Si no configurarás correo ahora, cambia `MAIL_MAILER=log` para evitar errores.

---

## Paso 3 — Generar APP_KEY

Antes de hacer el deploy, genera la clave de la aplicación. Puedes hacerlo de una de estas maneras:

**Opción A — Usando PHP en tu máquina local:**
```bash
php artisan key:generate --show
```
Copia el resultado (empieza con `base64:...`) y pégalo en la variable `APP_KEY`.

**Opción B — Usando la consola de Dokploy después del primer deploy:**
Una vez que el contenedor esté corriendo, abre la terminal del contenedor en Dokploy y ejecuta:
```bash
php artisan key:generate --force
```
Luego copia el valor generado en `.env` al campo `APP_KEY` de las variables de entorno de Dokploy y redeploya.

---

## Paso 4 — Configurar el Dominio y SSL

1. En Dokploy, ve a la sección **Domains** de tu aplicación.
2. Agrega tu dominio (`tu-dominio.com`).
3. Activa **HTTPS / Let's Encrypt** para SSL automático.
4. Asegúrate de que el puerto apuntado sea el **80** del servicio `app`.

---

## Paso 5 — Primer Deploy

1. Haz clic en **Deploy** en Dokploy.
2. Dokploy construirá la imagen Docker (puede tardar 3-8 minutos la primera vez).
3. El entrypoint automáticamente:
   - Espera a que PostgreSQL esté listo.
   - Ejecuta `php artisan migrate --force`.
   - Detecta si la base de datos está vacía y ejecuta el seed con todos los datos de producción.
   - Optimiza la aplicación (`config:cache`, `route:cache`, `view:cache`).
4. Verifica los logs en Dokploy para confirmar que no hay errores.

---

## Paso 6 — Verificar el Despliegue

Abre tu dominio en el navegador. Deberías ver el login de StockSekai.

**Credenciales existentes del sistema (importadas del seed):**
- Los usuarios ya existen con sus contraseñas hasheadas.
- El usuario principal es `huamalialcantara@gmail.com`.

Si necesitas crear un nuevo administrador, usa la consola de Dokploy:
```bash
php artisan tinker
>>> \App\Models\User::create(['name' => 'Admin', 'email' => 'admin@tudominio.com', 'password' => bcrypt('tu_password')]);
```

---

## Comandos útiles en la consola de Dokploy

```bash
# Ver logs de la aplicación
php artisan log:tail

# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Regenerar caché de producción
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ver estado de la base de datos
php artisan migrate:status

# Ejecutar seed manualmente (solo si la BD está vacía)
php artisan db:seed --class=ProductionDataSeeder --force
```

---

## Estructura de los contenedores

| Servicio | Imagen | Puerto interno | Descripción |
|----------|--------|---------------|-------------|
| `app` | Dockerfile custom | 80 | Laravel + PHP-FPM + Nginx |
| `db` | postgres:15-alpine | 5432 | Base de datos PostgreSQL |

---

## Volúmenes persistentes

| Volumen | Ruta | Propósito |
|---------|------|-----------|
| `postgres_data` | `/var/lib/postgresql/data` | Datos de PostgreSQL |
| `storage_data` | `/var/www/html/storage/app` | Archivos subidos |
| `logs_data` | `/var/www/html/storage/logs` | Logs de Laravel |

---

## Solución de problemas

### Error: `SQLSTATE[08006] Connection refused`
- Verifica que las variables `DB_HOST=db`, `DB_PORT=5432` estén configuradas.
- Verifica que el servicio `db` esté corriendo en Dokploy.

### Error: `No application encryption key has been specified`
- La variable `APP_KEY` está vacía. Genera y agrega la clave (ver Paso 3).

### Error: `Class "ProductionDataSeeder" not found`
- Ejecuta `composer dump-autoload` en la consola del contenedor.

### La aplicación muestra un error 500
- Cambia `APP_DEBUG=true` temporalmente para ver el error.
- Revisa los logs: `storage/logs/laravel.log` o los logs de Dokploy.
- Verifica que `APP_KEY` esté configurado.

### El seed falla con errores de clave duplicada
- El seed detecta automáticamente si la BD ya tiene datos y no vuelve a sembrar.
- Si necesitas reimportar, primero borra los datos: 
  ```bash
  php artisan migrate:fresh --force
  php artisan db:seed --class=ProductionDataSeeder --force
  ```

### Cambios en código no se reflejan
- En Dokploy, haz un nuevo **Deploy** para reconstruir la imagen.
- Para cambios de configuración (variables de entorno), basta con guardar y reiniciar el contenedor.

---

## Actualización del sistema

Para actualizar la aplicación con nuevo código:
1. Haz push al repositorio.
2. En Dokploy, haz clic en **Redeploy**.
3. Las migraciones nuevas se ejecutarán automáticamente en el entrypoint.

---

## Backup de la base de datos

Desde la consola del contenedor `db`:
```bash
pg_dump -U stocksekai stocksekai > backup_$(date +%Y%m%d).sql
```

Para restaurar:
```bash
psql -U stocksekai stocksekai < backup_20260101.sql
```
