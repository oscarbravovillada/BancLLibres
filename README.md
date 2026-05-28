# BancLLibres

Sistema de gestió del banc de llibres per a centres d'educació secundària. Permet controlar el préstec i la devolució d'exemplars, gestionar incidències i enviar albarans per correu a les famílies.

**Entorn:** PHP 8.2 · MariaDB 10.x · XAMPP sobre Ubuntu 22.04 LTS

---

## Funcionalitats principals

- **Préstecs i devolucions** — assignació de lots de llibres per curs i devolució exemplar per exemplar, amb generació automàtica de PDF i enviament per correu a la família
- **Gestió d'alumnes** — fitxa per alumne amb historial complet de préstecs, devolucions i incidències
- **Incidències** — registre de pèrdues i deterioraments, càrrec econòmic i control de pagaments
- **Importació CSV** — alta massiva d'alumnes, professors, classes, matèries i llibres
- **Dos rols d'usuari** — *admin* (accés total) i *professor* (accés restringit a les seves classes)
- **Tema clar/fosc** — preferència guardada per usuari a la base de dades
- **Sidebar col·lapsable** — es redueix a icones per guanyar espai; en tauleta es col·lapsa automàticament

## Tecnologies

| Component | Versió |
|---|---|
| PHP | 8.2 |
| MariaDB | 10.x (via XAMPP) |
| Bootstrap | 5.3.3 |
| Bootstrap Icons | 1.11.3 |
| PHPMailer | 6.x |
| FPDF | 1.8x |

## Instal·lació ràpida

Consulta la documentació completa a la carpeta [`docs/`](docs/):

- [`manual_installacio.html`](docs/manual_installacio.html) — instal·lació pas a pas
- [`guia_desplegament.html`](docs/guia_desplegament.html) — desplegament en producció
- [`manual_professor.html`](docs/manual_professor.html) — manual d'usuari per al professorat
- [`memoria_projecte.html`](docs/memoria_projecte.html) — memòria tècnica del projecte

**Resum:**

```bash
# 1. Clonar el repositori
cd /opt/lampp/htdocs
git clone https://github.com/oscarbravovillada/BancLLibres.git

# 2. Crear la base de dades
sudo /opt/lampp/bin/mysql -u root
# CREATE DATABASE banc_llibres CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
# CREATE USER 'banc_user'@'localhost' IDENTIFIED BY 'la_teva_contrasenya';
# GRANT ALL PRIVILEGES ON banc_llibres.* TO 'banc_user'@'localhost';

# 3. Importar l'esquema
sudo /opt/lampp/bin/mysql -u banc_user -p banc_llibres < database/schema.sql

# 4. Configurar l'aplicació
cp config/config.example.php config/config.php
nano config/config.php   # ajustar BD, SMTP i BASE_URL
```

L'usuari admin per defecte és `admin@admin.com` amb contrasenya `Admin1234!`. **Canvia-la al primer accés.**

## Estructura del projecte

```
BancLLibres/
├── alumnes/        fitxa, llista, importació
├── classes/        llistat i importació
├── exemplars/      inventari d'exemplars físics
├── incidencies/    registre d'incidències
├── llibres/        catàleg i importació
├── materies/       matèries i importació
├── prestecs/       préstecs, devolucions, historial
├── professors/     importació de professorat
├── admin/          utilitats d'administrador
├── config/         config.php (no inclòs al repo)
├── database/       schema.sql
├── docs/           documentació
├── src/
│   ├── helpers/    Auth, Database, Codis, Codificació
│   └── views/      layout compartit (top, bottom, CSS)
└── vendor/         PHPMailer, FPDF
```

## Autor

Desenvolupat per **Óscar Bravo Villada** — IES PORÇONS · Curs 2025/2026
