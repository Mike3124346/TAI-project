# Raport z przeglądu bezpieczeństwa

**Data:** 2026-10-29  
**Zakres:** PHP/Apache, obsługa parametrów i recenzji, sesje/CSRF, PDO/MySQL, konfiguracja Docker Compose oraz dostęp HTTP do plików projektu.  
**Typ przeglądu:** statyczna inspekcja kodu plus testy HTTP i integracyjne uruchomione lokalnie na danych demonstracyjnych.

## Podsumowanie

Nie stwierdzono aktywnego SQL injection, stored XSS ani obejścia CSRF w sprawdzonych przepływach. Znalezione problemy konfiguracyjne zostały poprawione: wyświetlanie błędów PHP i ujawnianie jego wersji, brak polityki CSP/nagłówków obronnych, szczegółowy nagłówek Apache oraz brak limitu rozmiaru żądania. Pozostają dwa ryzyka akceptowane w lokalnym projekcie: brak limitowania anonimowych recenzji oraz publikacja portu web na wszystkich interfejsach hosta dla przekazywania portów w dev containerze.

## Ustalenia

| ID | Ważność | Ustalenie | Status |
| :--- | :--- | :--- | :--- |
| SEC-01 | Średnia | Anonimowy endpoint recenzji nie ma rate limitu ani moderacji; możliwy jest spam i nieograniczony wzrost danych. | Otwarte; poza zakresem wymagań wersji podstawowej. |
| SEC-02 | Średnia | `web` publikuje port przez mapowanie `${APP_PORT}:80`, domyślnie na wszystkich interfejsach hosta. Usługa jest przeznaczona wyłącznie do lokalnego developmentu. | Otwarte; opisano w README. Ograniczyć zaporą hosta na współdzielonym/LAN hoście i nie publikować publicznie. |
| SEC-03 | Niska | Zewnętrzne fonty i obrazy ujawniają przeglądarce użytkownika połączenia do Google Fonts i Unsplash oraz zależą od ich dostępności. | Ryzyko akceptowane; źródła ogranicza CSP. |
| SEC-04 | Niska | HSTS nie jest ustawione, ponieważ dostarczony Compose obsługuje lokalny HTTP bez terminacji TLS. | Nie dotyczy lokalnej konfiguracji; wymagane przy wdrożeniu HTTPS. |

## Poprawki wdrożone podczas przeglądu

- PHP: `display_errors` i `display_startup_errors` są wyłączone, `log_errors` włączone, `expose_php` wyłączone. Sesje używają strict mode, wyłącznie cookie, `HttpOnly` i `SameSite=Lax`; aplikacja ustawia `Secure`, gdy żądanie widzi HTTPS.
- Apache: dodano CSP bez `unsafe-inline`, `Referrer-Policy`, `X-Content-Type-Options`, `X-Frame-Options` i `Permissions-Policy`. Wyłączono TRACE, directory listing i sygnaturę wersji serwera. Body requestu ograniczono do 131072 bajtów.
- Apache blokuje bezpośredni HTTP dostęp do katalogu `docker/`; test wykrył i usunął wcześniejsze udostępnienie konfiguracji wdrożeniowej.
- CSP dopuszcza skrypty tylko z własnego originu, arkusze Google Fonts, fonty `fonts.gstatic.com` oraz obrazy Unsplash; inline atrybuty `style` usunięto z widoków.
- Parametr wyszukiwania ograniczono po stronie serwera do 255 znaków UTF-8.
- Sekretne konfiguracje, SQL, backend i dokumentację blokuje Apache; `.env` jest ignorowany przez Git. MySQL jest publikowany wyłącznie na skonfigurowanym Docker bridge gateway.

## Wykonane testy

- Prepared-query check: wyszukanie ciągiem zawierającym składnię SQL zwróciło brak wyników, nie cały katalog.
- Stored-XSS check: recenzja i podpis zawierające znaczniki HTML zostały zakodowane w odpowiedzi. Testowe rekordy wycofano transakcją.
- CSRF: brak tokenu zwrócił `403`; poprawny token z oceną `11` zwrócił `422`.
- Limit requestu: body przekraczające 131072 bajtów zwróciło `413`.
- Walidacja route: katalog, istniejący film i aktor zwróciły `200`; niepoprawne ID `400`; nieistniejący rekord `404`.
- Ekspozycja plików: bezpośredni HTTP dostęp do SQL, backendu, dokumentacji, `docker/`, `.gitignore` i `.env.example` zwrócił `403` po poprawce.
- Po hardeningu: strona zwróciła `200`, `Server` zawiera wyłącznie `Apache`, CSP i nagłówki obronne są obecne; PHP runtime ma wymagane ustawienia.
- Build Compose, healthcheck MySQL, lint PHP/JS oraz `git diff --check` przeszły.

## Ograniczenia i zalecenia

- Testy przeprowadzono na lokalnym MySQL 8.4 z przykładowymi danymi. Nie jest to zewnętrzny pentest ani audyt produkcyjny.
- Przed udostępnieniem poza lokalnym developmentem należy dodać rate limiting i moderację recenzji, ograniczyć ekspozycję HTTP, skonfigurować HTTPS/HSTS, przejrzeć politykę zewnętrznych zasobów oraz przeprowadzić testy w środowisku docelowym.