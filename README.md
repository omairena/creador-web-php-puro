# Creador Web PHP Puro

Incluye login, registro y flujo de "Olvidé mi contraseña" con soporte PHPMailer/SMTP, rate-limiting y script de limpieza.

Setup rápido:
- Ejecuta los SQL en la carpeta `sql/` para crear las tablas.
- Configura variables de entorno:
  - DB_HOST, DB_USER, DB_PASS, DB_NAME
  - APP_BASE_URL (p. ej. https://mi-dominio.com)
  - Para SMTP: SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS, SMTP_SECURE, MAIL_FROM, MAIL_FROM_NAME
- Instala dependencias con Composer:
  - `composer install`
- Inicializa el repo si está vacío y sube los archivos (ver instrucciones abajo).
- Añade un cron para ejecutar `php /path/to/cleanup_password_resets.php` cada hora.

Instrucciones para Git (en tu máquina local)
1) Si el repo aún está vacío en GitHub, inicializa localmente y sube:
   - git init
   - git remote add origin git@github.com:omairena/creador-web-php-puro.git
   - git checkout -b main
2) Crear la rama de trabajo y añadir archivos:
   - git checkout -b feature/forgot-password
   - git add .
   - git commit -m "feat: forgot password flow with PHPMailer, rate-limit and cleanup cron"
   - git push -u origin feature/forgot-password

Cron example (ejecutar cada hora):
- 0 * * * * /usr/bin/php /var/www/html/creador-web-php-puro/cleanup_password_resets.php >> /var/log/cleanup_password_resets.log 2>&1

Notas:
- Recomiendo configurar APP_BASE_URL correctamente antes de enviar correos.
- Usa HTTPS y cookies seguras en producción.
- Si quieres, te genero un archivo .env.example y el comando exacto para usar en tu entorno de hosting.
