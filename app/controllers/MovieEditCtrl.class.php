<?php

namespace app\controllers;

use core\App;
use core\Utils;
use core\ParamUtils;
use core\Validator;
use app\forms\MovieEditForm;

class MovieEditCtrl {

    private $form; //dane formularza

    public function __construct() {
        //stworzenie potrzebnych obiektów
        $this->form = new MovieEditForm();
    }

    // Walidacja danych przed zapisem (nowe dane lub edycja).
    public function validateSave() {
        //0. Pobranie parametrów z walidacją
        $this->form->ID = ParamUtils::getFromRequest('ID', true, 'Błędne wywołanie aplikacji');
        $this->form->genre = ParamUtils::getFromRequest('genre', true, 'Błędne wywołanie aplikacji');
        $this->form->movie_name = ParamUtils::getFromRequest('movie_name', true, 'Błędne wywołanie aplikacji');
        $this->form->release_date = ParamUtils::getFromRequest('release_date', true, 'Błędne wywołanie aplikacji');
        $this->form->director = ParamUtils::getFromRequest('director', true, 'Błędne wywołanie aplikacji');

        if (App::getMessages()->isError())
            return false;

        // 1. sprawdzenie czy wartości wymagane nie są puste
        if (empty(trim($this->form->genre))) {
            Utils::addErrorMessage('Wprowadź gatunek filmu');
        }
        if (empty(trim($this->form->movie_name))) {
            Utils::addErrorMessage('Wprowadź nazwę filmu');
        }
        if (empty(trim($this->form->release_date))) {
            Utils::addErrorMessage('Wprowadź datę premiery filmu');
        }
        if (empty(trim($this->form->director))) {
            Utils::addErrorMessage('Wprowadź imie i nazwisko reżysera filmu');
        }

        if (App::getMessages()->isError())
            return false;

        // 2. sprawdzenie poprawności przekazanych parametrów

        $d = \DateTime::createFromFormat('Y-m-d', $this->form->release_date);
        if ($d === false) {
            Utils::addErrorMessage('Proszę wpisać datę w poprawnym formacie. Przykład: 2021-03-01');
        }

        return !App::getMessages()->isError();
    }

    //validacja danych przed wyswietleniem do edycji
    public function validateEdit() {
        //pobierz parametry na potrzeby wyswietlenia danych do edycji
        //z widoku listy osób (parametr jest wymagany)
        $this->form->ID = ParamUtils::getFromCleanURL(1, true, 'Błędne wywołanie aplikacji');
        return !App::getMessages()->isError();
    }

    public function action_movieNew() {
        $this->generateView();
    }

    //wysiweltenie rekordu do edycji wskazanego parametrem 'id'
    public function action_movieEdit() {
        // 1. walidacja id filmu do edycji
        if ($this->validateEdit()) {
            try {
                // 2. odczyt z bazy danych osoby o podanym ID (tylko jednego rekordu)
                $record = App::getDB()->get("movie", "*", [
                    "ID" => $this->form->ID
                ]);
                // 2.1 jeśli osoba istnieje to wpisz dane do obiektu formularza
                $this->form->ID = $record['ID'];
                $this->form->genre = $record['genre'];
                $this->form->movie_name = $record['movie_name'];
                $this->form->release_date = $record['release_date'];
                $this->form->director = $record['director'];

            } catch (\PDOException $e) {
                Utils::addErrorMessage('Wystąpił błąd podczas odczytu rekordu');
                if (App::getConf()->debug)
                    Utils::addErrorMessage($e->getMessage());
            }
        }

        // 3. Wygenerowanie widoku
        $this->generateView();
    }

    public function action_movieDelete() {
        // 1. walidacja id osoby do usuniecia
        if ($this->validateEdit()) {

            try {
                // 2. usunięcie rekordu
                App::getDB()->delete("movie", [
                    "ID" => $this->form->ID
                ]);
                Utils::addInfoMessage('Usunięto film z bazy danych');
            } catch (\PDOException $e) {
                Utils::addErrorMessage('Wystąpił błąd podczas usuwania rekordu');
                if (App::getConf()->debug)
                    Utils::addErrorMessage($e->getMessage());
            }
        }

        // 3. Przekierowanie na stronę listy osób
        App::getRouter()->forwardTo('movieList');
    }

    public function action_movieSave() {

        // 1. Walidacja danych formularza (z pobraniem)
        if ($this->validateSave()) {
            // 2. Zapis danych w bazie
            try {

                //2.1 Nowy rekord
                if ($this->form->ID == '') {
                    //sprawdź liczebność rekordów - nie pozwalaj przekroczyć 20
                    $count = App::getDB()->count("movie");
                    if ($count <= 20) {
                        App::getDB()->insert("movie", [
                            "genre" => $this->form->genre,
                            "movie_name" => $this->form->movie_name,
                            "release_date" => $this->form->release_date,
                            "director" => $this->form->director
                        ]);
                    } else { //za dużo rekordów
                        // Gdy za dużo rekordów to pozostań na stronie
                        Utils::addInfoMessage('Ograniczenie: Zbyt dużo rekordów. Aby dodać nowy usuń wybrany wpis.');
                        $this->generateView(); //pozostań na stronie edycji
                        exit(); //zakończ przetwarzanie, aby nie dodać wiadomości o pomyślnym zapisie danych
                    }
                } else {
                    //2.2 Edycja rekordu o danym ID
                    App::getDB()->update("movie", [
                        "genre" => $this->form->genre,
                        "movie_name" => $this->form->movie_name,
                        "release_date" => $this->form->release_date,
                        "director" => $this->form->director
                    ], [
                        "ID" => $this->form->ID
                    ]);
                }
                Utils::addInfoMessage('Pomyślnie zapisano rekord');
            } catch (\PDOException $e) {
                Utils::addErrorMessage('Wystąpił nieoczekiwany błąd podczas zapisu rekordu');
                if (App::getConf()->debug)
                    Utils::addErrorMessage($e->getMessage());
            }

            // 3b. Po zapisie przejdź na stronę listy osób (w ramach tego samego żądania http)
            App::getRouter()->forwardTo('movieList');
        } else {
            // 3c. Gdy błąd walidacji to pozostań na stronie
            $this->generateView();
        }
    }

    public function generateView() {
        App::getSmarty()->assign('form', $this->form); // dane formularza dla widoku
        App::getSmarty()->display('MovieEdit.tpl');
    }

}
