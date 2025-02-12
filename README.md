# Readme #

Das ist ein altes Projekt, mit dem ich Symfony gelernt habe. Es ist ein einfacher Pokedex in einem Docker-Container.

Es nutzt die Pokemon-API als Single Source of Truth beim Importieren von Pokemon, deren Typen und der Korrektur von Pokemon. Davon abgesehen können Pokemon manuell hinzugefügt werden.

## Installation und Nutzung ##

1. Repository klonen

2. Container bauen und starten

```bash
docker compose up -d
```

3. In den php container wechseln

```bash
docker exec -it pma-app-1 bash
```

4. [Pokemon-Typen importieren](src/Command/PokemonImportTypesCommand.php)

```bash
bin/console pokemon:import-types
```

5. Login

```
http://localhost:8080/login
Benutzer: admin@localhost.com
Passwort: IAmRoot
```

6. Erstes Pokemon hinzufügen z. B. Bisasam

```
http://localhost:8080/pokemon
Pokedex Nummer: 1
Name: Bisasam
Größe: 70
Typ: Pflanze
```

7. [Beliebiges Pokemon importieren z. B. Bisaknosp](src/Command/PokemonAddMissingCommand.php)

```bash
docker exec -it pma-app-1 bash
bin/console pokemon:add-missing 2
```

8. [Korrigiere Bisasam](src/Command/PokemonFixAllCommand.php)

```bash
docker exec -it pma-app-1 bash
bin/console pokemon:fix:all
```

Viel Spaß :-)