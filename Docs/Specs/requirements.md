# MovieDatabase: wymagania i plan realizacji

## 1. Cel i zakres

MovieDatabase to lokalna aplikacja katalogowa dla widzów, umożliwiająca przeglądanie filmów, poznawanie obsady oraz dodawanie anonimowych recenzji. Interfejs i komunikaty będą w języku angielskim. Ustalony stos to PHP i MySQL.

Wersja podstawowa nie obejmuje kont użytkowników ani panelu redakcyjnego. Ponieważ obecne pliki aplikacji i dokumenty techniczne są puste, wymagania opisują zakres do zbudowania, a nie potwierdzone zachowanie istniejącego kodu.

## 2. Wymagania funkcjonalne

### Obowiązkowe

- **MF-01**: Użytkownik może przeglądać katalog filmów.
- **MF-02**: Użytkownik może wyszukiwać filmy po tytule; wyszukiwanie nie rozróżnia wielkości liter.
- **MF-03**: Strona filmu pokazuje co najmniej tytuł, opis, rok wydania, gatunek, obsadę oraz opublikowane recenzje i oceny.
- **MF-04**: Film może być powiązany z wieloma aktorami, a aktor może występować w wielu filmach (relacja N:M).
- **MF-05**: Użytkownik może wysłać anonimową recenzję filmu wraz z oceną całkowitą od 1 do 10; podpis jest opcjonalny.
- **MF-06**: Aplikacja waliduje wymagane pola i zakres oceny po stronie serwera, a po niepoprawnym zgłoszeniu pokazuje zrozumiały komunikat.
- **MF-07**: Po poprawnym zapisie recenzja i ocena są widoczne na stronie właściwego filmu.
- **MF-08**: Brak wyników wyszukiwania, brak recenzji i niedostępny film mają czytelne stany, bez błędów technicznych ujawnianych użytkownikowi.

### Opcjonalne / poza zakresem wersji podstawowej

- **OF-01**: Filtrowanie katalogu według roku, gatunku lub oceny.
- **OF-02**: Rejestracja i logowanie, profile użytkowników oraz recenzje przypisane do kont.
- **OF-03**: Panel administracyjny do dodawania i edycji filmów, aktorów oraz recenzji.

Funkcje opcjonalne są propozycjami do przyszłego rozszerzenia i nie są warunkiem odbioru tej wersji.

## 3. Wymagania niefunkcjonalne

- **MNF-01 Bezpieczeństwo danych**: Zapytania do MySQL są wykonywane z użyciem PDO i parametrów/prepared statements; dane użytkownika są walidowane i kodowane przy wyświetlaniu, aby ograniczyć ryzyko SQL injection i XSS.
- **MNF-02 Walidacja**: Walidacja serwerowa jest obowiązująca niezależnie od walidacji formularza w przeglądarce.
- **MNF-03 Użyteczność i dostępność**: Widoki działają na telefonie i komputerze; formularze mają etykiety, a komunikaty błędów są powiązane z polami i zrozumiałe.
- **MNF-04 Język**: Tekst interfejsu i komunikaty aplikacji są po angielsku.
- **MNF-05 Środowisko**: Aplikację można uruchomić lokalnie z PHP i MySQL; instrukcja uruchomienia opisuje wymagane konfiguracje i import bazy.
- **MNF-06 Obsługa błędów**: Błędy połączenia i zapisu są obsługiwane bez ujawniania użytkownikowi danych dostępowych, zapytań SQL ani śladów stosu.

## 4. Harmonogram, zależności i przydział ról

Szacunek: 8 dni roboczych od zatwierdzenia wymagań. Prace frontendowe i projekt bazy mogą rozpocząć się równolegle po uzgodnieniu architektury. Kierownik projektu odpowiada za kolejność, decyzje zakresowe i statusy; nie jest jedną z ośmiu ról wykonawczych poniżej.

| Etap | Szacunek | Agent / rola | Wymagane wejście | Oczekiwany rezultat | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | 1 dzień | SOFTWARE ARCHITECT | Ten dokument | `Docs/Specs/architecture.md`: podział aplikacji, przepływy i decyzje techniczne | TODO |
| 2 | 1 dzień | Database Designer (Data Analyst) | Wymagania i architektura | `database/schema.sql`, ERD oraz opis relacji filmu, aktora i recenzji | TODO |
| 3 | 2 dni | Frontend Developer | Wymagania i architektura | Angielskie, responsywne widoki katalogu, filmu i formularza recenzji | TODO |
| 4 | 2 dni | Backend developer | Architektura i schemat bazy | Obsługa PHP/PDO dla katalogu, wyszukiwania, szczegółów i zapisu recenzji | TODO |
| 5 | 1 dzień | Integration Developer | Widoki, backend i schemat bazy | Zintegrowana aplikacja uruchamialna lokalnie oraz naprawione problemy integracyjne | TODO |
| 6 | 1 dzień | Security Reviewer | Zintegrowana aplikacja | Raport bezpieczeństwa z weryfikacją SQL injection, XSS, walidacji i błędów | TODO |
| 7 | 1 dzień | QA Tester | Aplikacja i scenariusze akceptacyjne | Raport testów: przebiegi pozytywne, walidacja granic ocen i błędne/nieistniejące dane | TODO |
| 8 | 1 dzień | TECHNICAL WRITER | Zatwierdzona aplikacja i raporty | README z uruchomieniem oraz raport końcowy | TODO |

Etapy 2 i 3 zależą od etapu 1 i mogą przebiegać równolegle. Etap 4 zależy od etapów 1 i 2. Etap 5 zależy od etapów 2–4. Etapy 6 i 7 rozpoczynają się po integracji; dokumentacja końcowa uwzględnia ich wyniki. Szacunki są orientacyjne i mogą się zmienić po przeglądzie architektury.

## 5. Kryteria akceptacji

- [ ] Aplikację można uruchomić lokalnie z PHP i MySQL zgodnie z instrukcją w README.
- [ ] Katalog wyświetla dostępne filmy, a wyszukiwanie tytułu zwraca właściwe wyniki i czytelny stan pusty.
- [ ] Strona szczegółów pokazuje dane filmu, aktorów z relacji N:M oraz zapisane recenzje i oceny.
- [ ] Relacja N:M działa w obie strony: film może mieć wielu aktorów, a aktor może być przypisany do wielu filmów.
- [ ] Recenzja bez wymaganych danych lub z oceną poniżej 1, powyżej 10 albo niecałkowitą nie jest zapisywana; oceny 1 i 10 są akceptowane.
- [ ] Poprawna anonimowa recenzja jest zapisywana i wyświetlana przy właściwym filmie.
- [ ] Wartości wprowadzane przez użytkownika nie są wykonywane jako SQL ani renderowane jako aktywny HTML/JavaScript.
- [ ] Błędy połączenia, nieistniejący film i błędne dane wejściowe nie ujawniają szczegółów technicznych i nie powodują nieczytelnej strony.
- [ ] Główne widoki i formularz pozostają używalne na szerokości ekranu telefonu i komputera.

## 6. Otwarte kwestie i ryzyka

- Nie ustalono moderacji ani ograniczania nadużyć w anonimowych recenzjach. Ewentualna moderacja jest poza zakresem wersji podstawowej i wymaga osobnej decyzji.
- Konkretne minimalne wersje PHP i MySQL należy wskazać w architekturze po sprawdzeniu środowiska docelowego.
- Brak istniejącej implementacji oznacza, że wszystkie kryteria akceptacji wymagają weryfikacji po zakończeniu prac.
