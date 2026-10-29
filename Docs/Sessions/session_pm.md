# PODSUMOWANIE SESJI PROJECT MANAGERA

**Data:** 2026-10-29  
**Projekt:** MovieDatabase / TAI-project  
**Status sesji:** IMPLEMENTACJA I INTEGRACJA WYKONANE; QA i dokumentacja końcowa pozostały do realizacji.

## 1. Cel i punkt startowy

Projekt zastany był pustym szkieletem PHP/MySQL. `README.md` zawierał wyłącznie nazwę projektu, a wymagania, architektura, specyfikacja bazy, pliki PHP, CSS/JS i skrypty SQL były puste. Prace przeprowadzono etapami, od zebrania wymagań, przez projekt i implementację, po uruchomienie lokalnego stosu oraz przegląd bezpieczeństwa.

## 2. Ustalenia z wywiadu i zatwierdzony zakres

- Odbiorcy: widzowie przeglądający katalog filmów.
- Język interfejsu i komunikatów: angielski.
- Stos: PHP i MySQL; aplikacja ma działać lokalnie.
- Wersja podstawowa: katalog i wyszukiwanie filmów po tytule, szczegóły filmu, obsada N:M, profile aktorów, recenzje z oceną całkowitą 1–10.
- Recenzje są anonimowe; podpis jest opcjonalny. Treść recenzji jest wymagana zgodnie z kontraktem implementacyjnym.
- Bez kont, panelu administracyjnego, filtrowania według gatunku/roku/oceny, moderacji i rate limitingu. Są to funkcje poza zakresem bieżącej wersji.
- Nie ustalono wersji PHP/MySQL ani moderacji; decyzję dla środowiska developerskiego i MySQL doprecyzowano podczas implementacji.

## 3. Decyzje projektowe

### Architektura

- Prosta aplikacja bez frameworka i warstwy API. `index.php`, `film.php` i `aktor.php` obsługują żądania i renderują HTML.
- `backend/config/database.php` tworzy połączenie PDO na podstawie zmiennych środowiskowych; `backend/includes/functions.php` obsługuje zapytania, walidację, sesję i kodowanie HTML.
- Zapytania dynamiczne używają prepared statements; recenzja przechodzi przez POST, CSRF i wzorzec Post/Redirect/Get.
- Dane rekordów backend zwraca jako pola zgodne ze schematem, z listami `cast`, `reviews` oraz `films`.

### Baza danych

- Tabele: `filmy`, `aktorzy`, `obsada`, `recenzje`.
- `obsada` ma klucz złożony `(film_id, aktor_id)` i klucze obce utrzymujące relację N:M.
- Recenzja wskazuje film; `ocena` ma ograniczenie `CHECK` 1–10, autor może być `NULL`, a treść jest `NOT NULL`.
- Schemat używa InnoDB, `utf8mb4`, indeksów do katalogu i obsady; zakłada MySQL 8.0.16+, a Compose dostarcza MySQL 8.4.
- Dane przykładowe są fikcyjne. `seed.sql` można uruchamiać ponownie bez duplikowania rekordów próbki. Import inicjalizacyjny Compose wykonuje się tylko dla pustego wolumenu.

### Aplikacja i bezpieczeństwo

- Widoki są responsywne, po angielsku, z pustymi stanami, opisanymi etykietami i walidacją klienta oraz serwera.
- Dane dynamiczne HTML są kodowane przez `escapeHtml()`. Recenzje zachowują tekst jako tekst, nie aktywny HTML.
- Sesje używają strict mode, cookie-only, `HttpOnly`, `SameSite=Lax` i `Secure` przy HTTPS. Token CSRF jest porównywany przez `hash_equals()`.
- PDO korzysta z `utf8mb4`, wyjątków i natywnych prepared statements. Błędy są logowane, a użytkownik dostaje ogólny komunikat.
- Konfiguracja PHP wyłącza wyświetlanie błędów i ujawnianie wersji; Apache ukrywa wersję, wyłącza TRACE i directory listing, nakłada CSP bez `unsafe-inline`, nagłówki bezpieczeństwa i limit body 131072 bajtów.
- Apache odmawia dostępu HTTP do `backend/`, `database/`, `Docs/`, `docker/`, `.git`, dotfiles, SQL i dokumentacji.

## 4. Przebieg prac i rezultaty ról

1. **Project Manager:** przeprowadzono wywiad i zapisano zakres, wymagania funkcjonalne/niefunkcjonalne, kryteria akceptacji, harmonogram i przydział ról w `Docs/Specs/requirements.md`.
2. **Software Architect:** przygotowano strukturę, odpowiedzialności modułów, kontrakty HTTP/PHP/DB, przepływy Mermaid, decyzje bezpieczeństwa i podział ustaleń źródłowych od założeń w `Docs/Specs/architecture.md`.
3. **Database Designer / Data Analyst:** przygotowano `database/schema.sql`, `database/seed.sql`, `database/queries.sql` i `Docs/Specs/database.md` z ERD, tabelami, kluczami, indeksami, regułami usuwania i instrukcją importu.
4. **Backend developer:** zaimplementowano `getPDO()`, `executeQuery()`, katalog/wyszukiwanie, szczegóły filmu i aktora, zapis recenzji, parsery ID/oceny, CSRF, sesję i `escapeHtml()`.
5. **Frontend Developer:** zaimplementowano `index.php`, `film.php`, `aktor.php`, `frontend/css/style.css` i `frontend/js/script.js`.
6. **Integration Developer:** dodano `Dockerfile`, `compose.yaml`, `.env.example`, `.gitignore`, `.dockerignore`, konfigurację Apache oraz kompletną instrukcję w `README.md`. Stos został uruchomiony na PHP 8.4/Apache i MySQL 8.4.
7. **Security Reviewer:** wykonano przegląd statyczny oraz testy HTTP/DB, poprawiono ustawienia PHP/Apache i utworzono `Docs/Reports/security_report.md`.
8. **QA Tester:** pełna osobna sesja QA nie została jeszcze wykonana; podstawowe scenariusze integracyjne i HTTP zostały wykonane przez integrację oraz przegląd bezpieczeństwa.
9. **Technical Writer:** pełna dokumentacja końcowa/raport projektu pozostają do wykonania; README zawiera już instrukcję lokalnego uruchomienia.

Szczegółowe podsumowania ról znajdują się w `Docs/Sessions/architect.md`, `data_analyst.md`, `backend_developer.md`, `frontend.md`, `integration_developer.md` i `security_reviewer.md`.

## 5. Napotkane problemy i rozwiązania

- Początkowo nie było klienta MySQL ani rozszerzenia `pdo_mysql` w kontenerze roboczym. Dodano Dockerfile instalujący wymagane rozszerzenia i Compose uruchamiający PHP/MySQL.
- Pierwsze połączenie PHP → MySQL timeoutowało mimo wspólnej sieci Compose. DNS `db` działał, baza słuchała na TCP, ale ruch kontener–kontener był blokowany w tym dev containerze. Testy wykazały osiągalność host gateway; Compose przełączono na `host.docker.internal`, a MySQL opublikowano tylko na Docker bridge gateway (`DB_GATEWAY_IP`, domyślnie `172.17.0.1`) i porcie `DB_HOST_PORT` (`3307`).
- Pierwsza konfiguracja `ServerTokens` w `<VirtualHost>` uniemożliwiła start Apache. Dyrektywy globalne przeniesiono do konfiguracji ładowanej po domyślnym `security.conf`.
- Nagłówek `Server` nadal ujawniał wersję Apache, ponieważ domyślne `security.conf` nadpisywało wcześniejszą konfigurację. Nazwę konfiguracji zmieniono tak, by ładowała się później; wynik końcowy to `Server: Apache`.
- Test dostępu HTTP wykrył publiczny plik `/docker/apache-security.conf`. Zablokowano cały katalog `docker/`; po rebuildzie konfiguracja PHP/Apache zwraca `403`.
- Test walidacji ujawnił obsługę cyfrowych wartości z zerami wiodącymi. Parsery ID i ocen zostały poprawione i ponownie przetestowane.
- Ripgrep nie był zainstalowany w środowisku; pojedynczą kontrolę zastąpiono `grep`.

## 6. Wykonane testy i wyniki

- Compose: `docker compose config --quiet`, build i start przeszły; MySQL osiąga status `healthy`.
- Dane po inicjalizacji: 3 filmy i 2 recenzje przykładowe. Testowy zapis recenzji wykonywano w transakcji z rollbackiem; nie pozostawiono danych testowych.
- PHP: `php -l` przeszedł dla `index.php`, `film.php`, `aktor.php` i obu modułów backendu.
- JavaScript: `node --check frontend/js/script.js` przeszedł.
- Parsery ID/ocen: poprawne wartości graniczne i z zerami wiodącymi zaakceptowane; ujemne, zerowe, niecałkowite, niecyfrowe i przepełnione wartości odrzucone.
- CSRF: token z bieżącej sesji zaakceptowany, błędny token odrzucony; przez HTTP brak CSRF zwraca `403`.
- Walidacja HTTP: katalog, istniejący film i aktor zwracają `200`; niepoprawne ID `400`; nieistniejący rekord `404`; ocena `11` z poprawnym tokenem `422`; body ponad limit `413`.
- DB/integracja: wyszukiwanie jest niewrażliwe na wielkość liter; ciąg zawierający składnię SQL nie omija filtra; szczegóły filmu, recenzje i obsada działają, a filmografia aktora odzwierciedla relację N:M.
- XSS: recenzja i podpis zawierające znaczniki HTML zostały zakodowane w odpowiedzi; testowe dane wycofano.
- Ekspozycja HTTP: katalog działa; pliki SQL, PHP backendu, dokumentacja, katalog `docker/`, `.gitignore` i `.env.example` zwracają `403`.
- Nagłówki: obecne CSP, `Referrer-Policy`, `X-Content-Type-Options`, `X-Frame-Options`, `Permissions-Policy`; nagłówek `Server` nie ujawnia wersji.
- PHP runtime: `display_errors`/`display_startup_errors`/`expose_php` wyłączone, `log_errors` i strict session mode włączone, `HttpOnly` i `SameSite=Lax` ustawione.
- `git diff --check` i diagnostyka edytora dla zmienionych plików nie wykazały błędów.

Testy są lokalne i nie stanowią zewnętrznego pentestu ani walidacji wdrożenia produkcyjnego.

## 7. Aktualne uruchomienie

- Aplikacja: `http://localhost:8081`.
- Usługi Compose `web` i `db` są uruchomione; baza ma status healthy.
- MySQL jest publikowany wyłącznie na skonfigurowanym Docker bridge gateway. Port aplikacji jest publikowany na wszystkich interfejsach hosta, aby działało przekazywanie portów dev containera.
- Stos testowano z `--env-file .env.example`. Przed samodzielnym uruchomieniem należy skopiować `.env.example` do `.env`, zmienić hasła developerskie i sprawdzić adres bramy Docker.
- Wolumen MySQL przechowuje dane; `docker compose down` zachowuje bazę, a `docker compose down -v` usuwa ją bezpowrotnie.

## 8. Otwarte ryzyka i następne kroki

- Anonimowe recenzje nie mają rate limitu ani moderacji. Przed ekspozycją publiczną dodać ochronę antyspamową i politykę moderacji.
- Compose wystawia port web na wszystkich interfejsach hosta dla lokalnego port-forwardingu; na współdzielonym/LAN hoście ograniczyć go zaporą. Nie publikować konfiguracji developerskiej publicznie.
- HTTPS/HSTS nie są skonfigurowane, bo lokalny Compose działa po HTTP. Są wymagane przy wdrożeniu zewnętrznym.
- Obrazy i fonty są pobierane z Unsplash i Google Fonts; CSP ogranicza źródła, ale połączenia ujawniają przeglądarce te domeny i zależą od dostępności usług.
- Minimalne wersje PHP/MySQL, limity pól, możliwość wielu gatunków i moderacja wymagają decyzji przed rozszerzeniem zakresu.
- Następne role: QA Tester powinien wykonać pełną macierz testów funkcjonalnych/responsywności i zapisu recenzji; Technical Writer powinien uzupełnić raport końcowy. Przed wdrożeniem produkcyjnym wymagany jest ponowny security review w środowisku docelowym.