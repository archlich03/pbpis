# PBPIS

Posėdžių balsavimo ir protokolavimo informacinė sistema - IS, kuri skirta Vilniaus universiteto Kauno fakulteto studijų programų komitetų elektroninių posėdžių administravimui bei jų protokolų generavimui. Ši IS kuriama, siekiant įgyvendinti Informacijos sistemų ir kibernetinės saugos „Kursinio darbo" modulio keliamus reikalavimus.

## IS funkcijos
- posėdžių ir darinių valdymas;
- vartotojų balsavimo procesas;
- protokolų generavimas;
- vartotojų valdymas;
- šviesus/tamsus režimo perjungimas.

## Sistemos reikalavimai
- **Operacinė sistema**: Linux aplinka (rekomenduojama Debian distribucija)
- **Docker**: [Docker Engine](https://docs.docker.com/engine/install/debian/) su Docker Compose
- **Node.js**: 18+ versija (frontend assets kompiliavimui)
- **Naršyklė**: Moderni naršyklė su JavaScript palaikymu

## Greitas paleidimas

### 1. Sistemos paruošimas
```bash
# Klonuoti repozitoriją
cd ~/Documents
git clone https://github.com/archlich03/pbpis.git
cd pbpis

# Nukopijuoti aplinkos konfigūraciją
cp .env.example .env
```

### 2. Microsoft OAuth konfigūracija (būtina)
Redaguokite `.env` failą ir nustatykite Microsoft OAuth parametrus:

```env
# Microsoft OAuth - gauti iš Azure Portal
MSGRAPH_CLIENT_ID=your-client-id-here
MSGRAPH_SECRET_ID=your-client-secret-here
MSGRAPH_TENANT_ID=common

# OAuth URL (keisti domain jei reikia)
MSGRAPH_OAUTH_URL=http://localhost/login/microsoft/callback
MSGRAPH_LANDING_URL=http://localhost/user/dashboard
```

**Kaip gauti Microsoft OAuth duomenis:**
1. Eikite į [Azure Portal](https://portal.azure.com)
2. Registruokite naują aplikaciją "App registrations"
3. Nukopijuokite Application (client) ID į `MSGRAPH_CLIENT_ID`
4. Sukurkite client secret ir nukopijuokite į `MSGRAPH_SECRET_ID`
5. Pridėkite redirect URI: `http://localhost/login/microsoft/callback`

### 3. Docker konteinerių paleidimas
```bash
# Paleisti visus servisus
sudo docker compose up -d

# Palaukti kol konteineriai pilnai pasileis
sleep 10
```

### 4. Aplikacijos inicializacija
```bash
# Sugeneruoti aplikacijos raktą
sudo docker exec pbpis php artisan key:generate

# Įvykdyti duomenų bazės migracijas ir seed duomenis
sudo docker exec pbpis php artisan migrate:fresh --seed

# Sukompiliuoti frontend assets
sudo docker exec pbpis npm install
sudo docker exec pbpis npm run build

# Perkrauti konteinerius
sudo docker compose restart
```

### 5. Prisijungimas
- **URL**: http://localhost
- **Admin prisijungimas**: 
  - El. paštas: `admin@knf.vu.lt` 
  - Slaptažodis: `admin123`

## Plėtojimo aplinka

### Frontend plėtojimas su hot reload
```bash
# Paleisti Vite dev serverį
sudo docker exec -it pbpis npm run dev

# Arba lokaliai (jei turite Node.js)
npm install
npm run dev
```

### Naudingos komandos
```bash
# Peržiūrėti logus
sudo docker logs pbpis -f

# Prisijungti prie konteinerio
sudo docker exec -it pbpis bash

# Sustabdyti visus servisus
sudo docker compose down

# Perkrauti duomenų bazę
sudo docker exec pbpis php artisan migrate:fresh --seed

# Išvalyti cache
sudo docker exec pbpis php artisan cache:clear
sudo docker exec pbpis php artisan config:clear
sudo docker exec pbpis php artisan view:clear
```

## Funkcijos

### Tema (Light/Dark Mode)
- Automatinis temos išsaugojimas localStorage ir cookies
- Perjungimas per navigation meniu
- Palaikoma visuose puslapiuose (dashboard, login, welcome)
- Responsive dizainas
- Alpine.js reaktyvumas

### Microsoft OAuth
- Prisijungimas per Microsoft/Office 365 paskyrą
- Automatinis vartotojo sukūrimas
- Saugus token valdymas
- Palaikomi universiteto ir asmeniniai Microsoft paskyrų tipai

### Daugiakalbystė
- Lietuvių kalba (pagrindinė)
- Anglų kalba
- Lengvas naujų kalbų pridėjimas

## Trikčių šalinimas

### Dažnos problemos:

**1. "Permission denied" klaidos:**
```bash
sudo chown -R $USER:$USER .
sudo chmod -R 755 storage bootstrap/cache
```

**2. Microsoft OAuth neveikia:**
- Patikrinkite ar teisingai nustatyti `.env` parametrai
- Įsitikinkite, kad redirect URI sutampa Azure Portal
- Patikrinkite ar `MSGRAPH_OAUTH_URL` yra pilnas URL
- Įsitikinkite, kad Azure aplikacijoje įjungti teisingi API permissions

**3. Frontend assets neužsikrauna:**
```bash
sudo docker exec pbpis npm install
sudo docker exec pbpis npm run build
sudo docker compose restart nginx
```

**4. Duomenų bazės problemos:**
```bash
sudo docker exec pbpis php artisan migrate:fresh --seed
```

**5. Tema neperjungia:**
- Patikrinkite ar Tailwind CSS sukompiliuotas su `darkMode: 'class'`
- Perkraukite puslapį ir išvalykite naršyklės cache
- Patikrinkite browser console ar nėra JavaScript klaidų

**6. Konteineriai nepasileido:**
```bash
# Patikrinti konteinerių statusą
sudo docker ps -a

# Peržiūrėti klaidas
sudo docker logs pbpis
sudo docker logs nginx
sudo docker logs mysql

# Perkrauti visą sistemą
sudo docker compose down
sudo docker compose up -d
```

## Architektūra

### Backend
- **Framework**: Laravel 11
- **Database**: MySQL 8.0
- **Authentication**: Laravel Sanctum + Microsoft Graph
- **Server**: Nginx + PHP-FPM

### Frontend
- **CSS Framework**: Tailwind CSS 3.x
- **JavaScript**: Alpine.js 3.x
- **Build Tool**: Vite 6.x
- **Icons**: Heroicons

### Deployment
- **Containerization**: Docker + Docker Compose
- **Web Server**: Nginx
- **Process Management**: Docker services

## Prisijungimo informacija
Pirmą kartą paleidus IS, sukuriamas IT administratoriaus rolę turintis naudotojas. Prisijungimo duomenys specifikuoti `.env` faile (`DEFAULT_EMAIL` ir `DEFAULT_PASSWORD`).

## Saugumas
- CSRF apsauga
- XSS apsauga
- SQL injection apsauga
- Saugus session valdymas
- Microsoft OAuth 2.0 integracija

## Licencija
PBPIS veikia su GNU GPLv3 licencija.

---

## Papildoma informacija

### Portai
- **Web aplikacija**: http://localhost (port 80)
- **Vite dev server**: http://localhost:5173 (development)
- **MySQL**: localhost:3306 (internal)

### Failų struktūra
```
pbpis/
├── app/                 # Laravel aplikacijos logika
├── resources/           # Views, CSS, JS failai
├── docker/             # Docker konfigūracijos
├── database/           # Migracijos ir seeders
├── public/             # Viešai prieinami failai
└── storage/            # Logai, cache, uploads
```

### Plėtojimo patarimai
1. Naudokite `npm run dev` frontend plėtojimui
2. Laravel logai: `storage/logs/laravel.log`
3. Duomenų bazės seeders: `database/seeders/`
4. Blade templates: `resources/views/`
5. Tailwind konfigūracija: `tailwind.config.js`
