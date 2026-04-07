# 📦 Sistema de Inventario Inteligente - ControlStockSekai

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-red?style=for-the-badge&logo=laravel" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2-blue?style=for-the-badge&logo=php" alt="PHP 8.2">
  <img src="https://img.shields.io/badge/AdminLTE-3-green?style=for-the-badge" alt="AdminLTE 3">
  <img src="https://img.shields.io/badge/Bootstrap-5-purple?style=for-the-badge&logo=bootstrap" alt="Bootstrap 5">
</p>

## 🎯 Descripción

Sistema de gestión de inventario inteligente desarrollado en Laravel con AdminLTE, diseñado para control de stock en tiempo real con verificación, importación/exportación de Excel y autenticación moderna con Google OAuth.

## ✨ Características Principales

- ✅ **Autenticación Moderna**: Login/Register estético + Google OAuth
- ✅ **Dashboard AdminLTE**: Interfaz profesional y responsive
- ✅ **Importación Excel**: Soporta .xlsx, .xls, .csv
- ✅ **Stock Verificado**: Sistema de colores (🔴 Rojo / 🟢 Verde / 🟡 Amarillo)
- ✅ **Guardado Automático**: AJAX autosave en 1 segundo
- ✅ **Historial Completo**: Auditoría de cambios
- ✅ **Exportación Excel**: Descarga instantánea del inventario
- ✅ **Optimizado Móvil**: Perfecto para trabajar desde celulares
- ✅ **1,300+ Productos**: Ya importados y listos

## 🚀 Inicio Rápido

```bash
# Clonar e instalar
cd c:\xampp\htdocs\ControlStockSekai
composer install
npm install

# Configurar
cp .env.example .env
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Importar productos
php artisan stock:import

# Iniciar servidor
php artisan serve
```

**Acceder a**: http://127.0.0.1:8000

## 📋 Requisitos

- PHP >= 8.2
- MySQL/MariaDB
- Composer
- Node.js & NPM

## 📁 Documentación

- 📘 [INICIO_RAPIDO.md](INICIO_RAPIDO.md) - Guía de inicio en 2 minutos
- 📗 [SISTEMA_COMPLETO.md](SISTEMA_COMPLETO.md) - Documentación completa
- 📙 [MANUAL_USUARIO.md](MANUAL_USUARIO.md) - Manual detallado con Google OAuth
- 📕 [RESUMEN_EJECUTIVO.md](RESUMEN_EJECUTIVO.md) - Resumen del proyecto

## 🎨 Tecnologías

| Backend | Frontend | Database | Herramientas |
|---------|----------|----------|--------------|
| Laravel 12 | AdminLTE 3 | MySQL | Laravel Excel |
| PHP 8.2 | Bootstrap 5 | | Laravel Socialite |
| | jQuery + AJAX | | Font Awesome 6 |

## 📊 Estructura de Base de Datos

### Tabla `products`
- Código, Producto, Marca, Costo, Precio Cliente
- Stock original + Stock verificado
- Registro de verificador y fecha

### Tabla `stock_verifications`
- Historial completo de cambios
- Usuario, stock anterior/nuevo, timestamp

## 🎯 Uso del Sistema

### Importar Productos
```bash
php artisan stock:import
```

### Verificar Stock
1. Ingresar al sistema
2. Buscar producto en la tabla
3. Escribir stock real en "Stock Verificado"
4. Esperar 1 segundo → "✓ Guardado"
5. El color indica la diferencia:
   - 🔴 Rojo: Falta producto
   - 🟢 Verde: Coincide
   - 🟡 Amarillo: Hay más

### Exportar Inventario
Click en "Exportar Excel" → Descarga instantánea

## 📱 Desde el Celular

1. Misma red WiFi
2. IP de la PC: `ipconfig`
3. En celular: `http://[IP]:8000`

## 🔐 Google OAuth (Opcional)

Configurar credenciales en `.env`:
```env
GOOGLE_CLIENT_ID=tu-client-id
GOOGLE_CLIENT_SECRET=tu-client-secret
GOOGLE_REDIRECT_URL=http://localhost:8000/auth/google/callback
```

Ver [MANUAL_USUARIO.md](MANUAL_USUARIO.md) para instrucciones detalladas.

## 🛠️ Comandos Útiles

```bash
# Desarrollo
php artisan serve
npm run dev

# Producción
npm run build

# Base de datos
php artisan migrate:fresh
php artisan stock:import

# Caché
php artisan optimize:clear
```

## 🎓 Capturas de Pantalla

- **Login**: Diseño moderno con Google OAuth
- **Dashboard**: AdminLTE con sidebar
- **Inventario**: Tabla con colores y guardado automático
- **Móvil**: Totalmente responsive

## 📈 Estadísticas

- **1,300+** productos importados
- **3** tablas principales
- **Guardado en 1 segundo**
- **100%** responsive

## 🤝 Soporte

Para dudas o problemas:
1. Revisar documentación
2. Verificar servidor: `php artisan serve`
3. Limpiar caché: `php artisan optimize:clear`

## 📄 Licencia

Este proyecto es de uso privado para ControlStockSekai.

## 🎉 Estado

**✅ Sistema 100% Funcional**

- Servidor: http://127.0.0.1:8000
- Productos: 1,300 importados
- Listo para usar

---

**Desarrollado con ❤️ para ControlStockSekai**  
*Sistema de Inventario Inteligente - 2026*


In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
