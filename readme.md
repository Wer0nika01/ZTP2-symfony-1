Instrukcja do instalacji:

Pobrać/sklonować repozytorium
Wykonać polecenie
composer install (w przypadku braku composera zainstalować go)
Wykonać polecenie istalacji kontenerów
docker-compose build (jeżeli docker nie znajduję się w naszym systemie należy go doinstalować)
Następnie wystartować kontenery za pomocą
docker-compose up -d
W pliku app/.env należy ustawić prawidłowe dostępy do bazy danych tak aby zgadzały się ze wzorem:
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name
Jeżeli chcemy wypełnić naszą bazę przykładowymi danymi należy wykonać dodatkowo w bashu
docker-compose exec php bash wywołać komendę
bin/console doctrine:fixtures:load
