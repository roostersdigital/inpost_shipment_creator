# InPost Shipment Creator

## Spis Treści

* [Funkcje](#funkcje)
* [Struktura Projektu](#struktura-projektu)
* [Logika Sterowania](#logika-sterowania)
* [Dodatkowe Uwagi](#dodatkowe-uwagi)
* [Instalacja](#instalacja)
* [Użycie](#uzycie)
* [Zależności](#zaleznosci)


---

## Funkcje

* **Tworzenie Przesyłek**: Tworzenie nowych przesyłek InPost poprzez dostarczenie szczegółowych informacji o nadawcy, odbiorcy, paczce i usłudze.
* **Monitorowanie Statusu Przesyłki**: Automatyczne sprawdzanie statusu utworzonej przesyłki i oczekiwanie, aż osiągnie status `CONFIRMED` (potwierdzony), zanim przejdzie do dalszych działań.
* **Tworzenie Zlecenia Odbioru**: Po potwierdzeniu przesyłki, można wygenerować zlecenie odbioru (żądanie odbioru przez kuriera).
* **Komunikacja API**: Obsługuje bezpieczną komunikację z API InPost za pomocą klienta HTTP Guzzle i uwierzytelniania tokenem Bearer.
* **Logowanie**: Zapewnia solidne logowanie do plików dla wiadomości informacyjnych, odpowiedzi API i błędów.
* **Zarządzanie Konfiguracją**: Używa `phpdotenv` do ładowania danych uwierzytelniających API i innych konfiguracji z pliku `.env`.

---

## Struktura Projektu

Projekt ma modułową strukturę, aby zapewnić łatwość utrzymania i jasny rozdział odpowiedzialności:

```
├── App/
│   ├── Api/
│   │   └── InpostApiClient.php           # Obsługuje bezpośrednią komunikację z API InPost.
│   ├── Contracts/
│   │   ├── ApiClientInterface.php        # Definiuje interfejs dla klientów API.
│   │   └── LoggerInterface.php           # Definiuje interfejs dla logowania.
│   ├── Enums/
│   │   └── InpostShipmentStatusEnum.php  # Definiuje stałe statusów przesyłek InPost i metody pomocnicze na podstawie dokumentacji Inpostu.
│   ├── Logger/
│   │   └── FileLogger.php                # Implementuje logowanie do plików.
│   └── Service/
│       ├── DispatchOrderService.php      # Logika biznesowa do tworzenia zleceń odbioru.
│       └── ShipmentService.php           # Logika biznesowa do tworzenia i sprawdzania statusu przesyłek.
├── config/
│   └── api_config.php                    # Ładuje konfigurację API ze zmiennych środowiskowych.
├── storage/
│   └── logs/
│       └── log.json                      # Domyślna lokalizacja dla plików logów.
├── vendor/                               # Zależności Composera.
├── .gitignore                            # Określa pliki celowo ignorowane przez kontrolę wersji.
├── composer.json                         # Konfiguracja Composera i zarządzanie zależnościami.
├── index.php                             # Główny punkt wejścia aplikacji.
└── payload.json                          # Przykładowy ładunek JSON dla danych przesyłki i zlecenia
```


## Logika Sterowania

Plik `index.php` działa jako główny punkt wejścia i orkiestrator procesu tworzenia przesyłki i zlecenia odbioru:

1.  **Bootstraping**: Konfiguruje raportowanie błędów i ładuje autoloader Composera oraz konfigurację API.
2.  **Inicjalizacja Loggera**: Inicjalizuje `FileLogger` do zapisywania logów w `storage/logs/log.json`.
3.  **Ładowanie Danych Wejściowych**: Odczytuje dane przesyłki i zlecenia odbioru z `payload.json`. Można to zmienić na `php://input` dla żądań API.
4.  **Walidacja Konfiguracji**: Upewnia się, że niezbędne dane uwierzytelniające API (URL bazowy, token, ID organizacji) są dostępne.
5.  **Inicjalizacja Serwisów**: Tworzy instancje `InpostApiClient`, `ShipmentService` i `DispatchOrderService`, wstrzykując loggera i klienta API jako zależności.
6.  **Tworzenie Przesyłki**: Wywołuje `ShipmentService::createShipment()` w celu wysłania danych przesyłki do API InPost.
7.  **Pętla Pollingowa Statusu**: Wchodzi w pętlę, która wielokrotnie sprawdza status przesyłki za pomocą `ShipmentService::getShipmentStatus()`. Wstrzymuje działanie na określony czas (`6` sekund) między sprawdzeniami i kontynuuje, aż status przesyłki zostanie `CONFIRMED` (potwierdzony) lub zostanie osiągnięta maksymalna liczba prób (`10`).
8.  **Tworzenie Zlecenia Odbioru**: Jeśli przesyłka zostanie `CONFIRMED`, wywoływana jest metoda `DispatchOrderService::createDispatchOrder()` w celu zażądania odbioru kurierskiego dla nowo utworzonej przesyłki.
9.  **Obsługa Odpowiedzi i Błędów**: Zwraca odpowiedź JSON o sukcesie lub przechwytuje różne wyjątki (`InvalidArgumentException`, `Exception`, `Throwable`), aby zapewnić odpowiednie kody statusu HTTP i komunikaty o błędach.

## Dodatkowe Uwagi

Domyślny payload tworzy przesyłkę z metodą **inpost_courier_c2c** zamiast **inpost_courier_standard**.
W sandboxie Inpostu nie mogłem uruchomić usług kurierskich dla firm. 
Używając proponowanego przez nich numeru umowy w konfiguracji, dostawałem 400 - Upps! Nie ma takiej umowy.
Zgłosiłem się z pytaniem, ale, że jest weekend to nie liczę na odpowiedź :)
Generalnie proces obsługi metody standard czy c2c jest tożsamy na potrzeby tego zadania.

* **Prostota `index.php`**: Bezpośrednie bootstraping i przepływ wykonania w `index.php` są zaprojektowane dla prostoty i łatwości użycia w tym samodzielnym skrypcie. W większej aplikacji lub frameworku, logika ta byłaby zazwyczaj zarządzana przez bardziej zaawansowany kontener wstrzykiwania zależności i system routingu.
* **Modułowość i Testowalność**: Użycie serwisów, kontraktów (interfejsów) i wstrzykiwania zależności w całym katalogu `App/` jest kluczowe. Ta architektura pozwala na:
    * **Łatwe Testowanie**: Możesz łatwo tworzyć mockowe implementacje `ApiClientInterface` lub `LoggerInterface` do testów jednostkowych, izolując logikę biznesową od zewnętrznych zależności.
    * **Wymiana Providera**: Jeśli w przyszłości zajdzie potrzeba integracji z inną usługą kurierską, kontrakt `ApiClientInterface` pozwala na zamianę `InpostApiClient` na klienta innego dostawcy bez zmiany podstawowej logiki `ShipmentService` lub `DispatchOrderService`.
* **Pętla Pollingowa (Uproszczona)**: Mechanizm pollingu w `index.php` do sprawdzania statusu przesyłki jest zaimplementowany dla prostoty i natychmiastowej informacji zwrotnej. W środowisku produkcyjnym, zwłaszcza w przypadku długotrwałych procesów lub scenariuszy o dużej objętości, bardziej niezawodne byłoby użycie asynchronicznych zadań (np. za pośrednictwem kolejki komunikatów, takiej jak RabbitMQ lub Redis z workerem zadań) do wysyłania i obsługi aktualizacji statusu, unikając blokowania głównego wątku wykonania.

---

## Instalacja

1.  **Repozytorium**:
    ```bash
    git clone https://github.com/roostersdigital/inpost_shipment_creator.git
    cd inpost_shipment_creator
    ```
2.  **Zainstaluj paczki Composera**:
    ```bash
    composer i
    ```

3.  **Utwórz Plik `.env`**:
    Utwórz plik `.env` w katalogu głównym projektu (obok `composer.json`) i wypełnij go swoimi danymi uwierzytelniającymi API InPost:
    ```
    INPOST_API_BASE_URL="[https://sandbox-api-shipx-pl.easypack24.net](https://sandbox-api-shipx-pl.easypack24.net)"
    INPOST_API_TOKEN="TWÓJ_TOKEN_API_INPOST"
    INPOST_ORGANIZATION_ID="TWÓJ_ID_ORGANIZACJI_INPOST"
    ```
    Plik `.gitignore` jest skonfigurowany tak, aby ignorować `.env` i zapobiegać jego dodawaniu do kontroli wersji.

---

## Użycie

Aby uruchomić skrypt:

1.  Przykładowy payload znajduje się w `payload.json`.
2.  **Wykonaj skrypt**:
    ```bash
    php index.php
    ```
    Skrypt zwraca odpowiedź JSON wskazującą na sukces lub porażkę i zapisze szczegółowe informacje w `storage/logs/log.json`.

    Dla lepszego formatowania można strzelić POST na localhost/index w Postmanie albo innym kliencie.
    
    Uruchomić domyślny server php.

    ```bash
    php -S localhost:8000
    ```

    W folderze .http jest gotowy request dla klienta w PhpStorm.

---