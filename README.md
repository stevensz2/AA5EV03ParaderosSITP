# Paraderos SITP por localidad - Bogota (AA5-EV03)

Sitio web en PHP y API que consumen los datos abiertos de Bogota ("Paraderos Zonales del SITP")
para mostrar los paraderos de una localidad que el usuario selecciona.

**Aprendiz:** Steven Diaz Morales - **Evidencia:** GA7-220501096-AA5-EV03

## Requisitos
- PHP 7.4 o superior (con la extension `curl` activa; si no esta, usa `file_get_contents`)
- Conexion a Internet (los datos se consultan en linea)

## Como ejecutarlo
**Opcion A - servidor integrado de PHP**
```
cd AA5_EV03_ParaderosSITP
php -S localhost:8000
```
Abrir http://localhost:8000/index.php

**Opcion B - XAMPP**
Copiar la carpeta `AA5_EV03_ParaderosSITP` en `C:\xampp\htdocs\`, iniciar Apache y abrir
http://localhost/AA5_EV03_ParaderosSITP/

## Servicios de la API
- `GET /api/localidades.php`
- `GET /api/paraderos.php?localidad=NOMBRE`

La documentacion completa esta en `docs/DOCUMENTACION_API.md`.

## Problemas comunes
- **"SSL certificate problem"** (XAMPP en Windows): en `config.php` cambiar `'verificar_ssl'` a `false`.
- **"No fue posible consultar los datos"**: revisar la conexion a Internet; los servicios de Datos Abiertos
  pueden estar temporalmente fuera de linea.

## Versionamiento con Git
```
git init
git add .
git commit -m "Proyecto AA5-EV03: paraderos SITP por localidad"
git branch -M main
git remote add origin https://github.com/stevensz2/AA5EV03ParaderosSITP.git
git push -u origin main
```
