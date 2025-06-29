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
Pirmą kartą paleidus IS, sukuriamas IT administratoriaus rolę turintis naudotojas. Prisijungimo duomenys specifikuoti .env faile.

## Instaliacijos instrukcija

1. Įvykdykite šias komandas:
```sh
cd ~/Documents;
git clone https://github.com/archlich03/pbpis.git;
cd pbpis;
cp .env.example .env;
sudo docker compose up -d;
```
2. Pirmą kartą paleidžiant įvykdykite DB migraciją ir pirminę konfigūraciją:
```sh
sleep 5;
sudo docker exec pbpis php artisan migrate:fresh --seed
sudo docker exec pbpis php artisan key:generate
sudo docker compose restart pbpis
```
3. Atidarykite web aplikaciją per naršyklę: `http://localhost:8000`

## Licencija

PBPIS veikia su GNU GPLv3 licencija.
