<?php


namespace app\controllers;


use app\forms\ReviewEditForm;

class ReviewEditCtrl
{

    private $form; //dane formularza

    public function __construct()
    {
        //stworzenie potrzebnych obiektów
        $this->form = new ReviewEditForm();
    }

    // Walidacja danych przed zapisem (nowe dane lub edycja).
    public function validateSave()
    {
        //0. Pobranie parametrów z walidacją
        $this->form->review_id = ParamUtils::getFromRequest('review_id', true, 'Błędne wywołanie aplikacji');
        $this->form->review_topic = ParamUtils::getFromRequest('review_topic', true, 'Błędne wywołanie aplikacji');
        $this->form->review = ParamUtils::getFromRequest('review', true, 'Błędne wywołanie aplikacji');
        $this->form->rating = ParamUtils::getFromRequest('rating', true, 'Błędne wywołanie aplikacji');

        if (App::getMessages()->isError())
            return false;

        // 1. sprawdzenie czy wartości wymagane nie są puste
        if (empty(trim($this->form->review_topic))) {
            Utils::addErrorMessage('Wpisz temat recenzji');
        }
        if (empty(trim($this->form->review))) {
            Utils::addErrorMessage('Wpisz dane do pola recenzji');
        }
        if (empty(trim($this->form->rating))) {
            Utils::addErrorMessage('Proszę o wybranie oceny z zakresu od 0 do 10');
        }

        if (App::getMessages()->isError())
            return false;

        // 2. Ta walidacja zasługuje na własny proces norymberski
        #TODO - popraw walidacje wejściową

        if ($this->form->rating <= '0' and $this->form->rating > '10' ) {
            Utils::addErrorMessage('Wpisano zły przedział ocen (system obsługuje oceny od 0 do 10)');
        }

        return !App::getMessages()->isError();
    }

    //validacja danych przed wyswietleniem do edycji
    public function validateEdit()
    {
        //pobierz parametry na potrzeby wyswietlenia danych do edycji
        //z widoku listy osób (parametr jest wymagany)
        $this->form->review_id = ParamUtils::getFromCleanURL(1, true, 'Błędne wywołanie aplikacji');
        return !App::getMessages()->isError();
    }

    public function action_reviewNew()
    {
        $this->generateView();
    }

    //wyświetlenie rekordu do edycji wskazanego parametrem 'id'
    public function action_reviewEdit()
    {
        // 1. walidacja id osoby do edycji
        if ($this->validateEdit()) {
            try {
                // 2. odczyt z bazy danych osoby o podanym ID (tylko jednego rekordu)
                $record = App::getDB()->get("review", "*", [
                    "review_id" => $this->form->review_id
                ]);
                // 2.1 jeśli osoba istnieje to wpisz dane do obiektu formularza
                $this->form->review_id = $record['review_id'];
                $this->form->review_topic = $record['review_topic'];
                $this->form->review = $record['review'];
                $this->form->rating = $record['rating'];
            } catch (\PDOException $e) {
                Utils::addErrorMessage('Wystąpił błąd podczas odczytu rekordu');
                if (App::getConf()->debug)
                    Utils::addErrorMessage($e->getMessage());
            }
        }

        // 3. Wygenerowanie widoku
        $this->generateView();
    }

    public function action_reviewDelete()
    {
        // 1. walidacja id recenzji do usuniecia
        if ($this->validateEdit()) {

            try {
                // 2. usunięcie rekordu
                App::getDB()->delete("review", [
                    "review_id" => $this->form->review_id
                ]);
                Utils::addInfoMessage('Pomyślnie usunięto recenzję');
            } catch (\PDOException $e) {
                Utils::addErrorMessage('Wystąpił błąd podczas usuwania recenzji');
                if (App::getConf()->debug)
                    Utils::addErrorMessage($e->getMessage());
            }
        }

        // 3. Przekierowanie na stronę listy osób
        App::getRouter()->forwardTo('ReviewList');
    }

    public function action_reviewSave()
    {

        // 1. Walidacja danych formularza (z pobraniem)
        if ($this->validateSave()) {
            // 2. Zapis danych w bazie
            try {

                //2.1 Nowy rekord
                if ($this->form->review_id == '') {
                    //sprawdź liczebność rekordów - nie pozwalaj przekroczyć 20
                    $count = App::getDB()->count("review");
                    if ($count <= 20) {
                        App::getDB()->insert("review", [
                            "review_topic" => $this->form->review_topic,
                            "review" => $this->form->review,
                            "rating" => $this->form->rating
                        ]);
                    } else { //za dużo rekordów
                        // Gdy za dużo rekordów to pozostań na stronie
                        Utils::addInfoMessage('Ograniczenie: Zbyt dużo rekordów. Aby dodać nowy usuń wybrany wpis.');
                        $this->generateView(); //pozostań na stronie edycji
                        exit(); //zakończ przetwarzanie, aby nie dodać wiadomości o pomyślnym zapisie danych
                    }
                } else {
                    //2.2 Edycja rekordu o danym ID
                    App::getDB()->update("review", [
                        "review_topic" => $this->form->review_topic,
                        "review" => $this->form->review,
                        "rating" => $this->form->rating
                    ], [
                        "review_id" => $this->form->review_id
                    ]);
                }
                Utils::addInfoMessage('Pomyślnie zapisano recenzję');
            } catch (\PDOException $e) {
                Utils::addErrorMessage('Wystąpił nieoczekiwany błąd podczas zapisu recenzji');
                if (App::getConf()->debug)
                    Utils::addErrorMessage($e->getMessage());
            }

            // 3b. Po zapisie przejdź na stronę listy recenzji (w ramach tego samego żądania http)
            App::getRouter()->forwardTo('ReviewList');
        } else {
            // 3c. Gdy błąd walidacji to pozostań na stronie
            $this->generateView();
        }
    }

    public function generateView()
    {
        App::getSmarty()->assign('form', $this->form); // dane formularza dla widoku
        App::getSmarty()->display('ReviewEdit.tpl');
    }

}
