# PBPIS

Posėdžių balsavimo ir protokolavimo informacinė sistema - IS, kuri skirta Vilniaus universiteto Kauno fakulteto studijų programų komitetų elektroninių posėdžių administravimui bei jų protokolų generavimui. Ši IS kuriama, siekiant įgyvendinti Informacijos sistemų ir kibernetinės saugos „Kursinio darbo“ modulio keliamus reikalavimus.

## IS funkcijos
- posėdžių ir darinių valdymas;
- vartotojų balsavimo procesas;
- protokolų generavimas;
- vartotojų valdymas.

## Sistemos reikalavimai:
- IS leidžiama Linux aplinkoje (Rekomendojama Debian distribucija);
- Įrašyta [Docker Engine](https://docs.docker.com/engine/install/debian/) programinė įranga (privalo būti suinstaliuotas Docker Compose).

## Prisijungimo informacija:
Pirmą kartą paleidus IS, sukuriami šie vartotojai. Esant poreikiui, IT administratoriaus rolę turintis naudotojas gali juos ištrinti:
- **IT administratorius:** 
  - El. paštas: `admin@knf.vu.lt`
  - Slaptažodis: `admin123`

Įprastas DB prisijungimas specifikuotas .env faile.

## Instaliacijos instrukcija

1. Įvykdykite šias komandas:
```shell
cd ~/Documents;
git clone https://github.com/archlich03/pbpis.git;
cp .env.example .env
sudo docker compose up -d;
```
2. Pirmą kartą paleidžiant įvykdykite DB migraciją ir pirminę konfigūraciją:
```sh
sudo docker exec pbpis php artisan migrate:fresh --seed
sudo docker exec pbpis php artisan key:generate
sudo docker compose restart pbpis
```
3. Atidarykite web aplikaciją per naršyklę: `http://localhost:8000`

## Licencija

PBPIS veikia su GNU GPLv3 licencija.
