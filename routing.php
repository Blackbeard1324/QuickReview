<?php

use core\App;
use core\Utils;

App::getRouter()->setDefaultRoute('movieList'); // akcja/ścieżka domyślna
App::getRouter()->setLoginRoute('login'); // akcja/ścieżka na potrzeby logowania (przekierowanie, gdy nie ma dostępu)
#TODO - popraw akcje, bo chyba są niepoprawne
Utils::addRoute('loginShow',     'LoginCtrl');
Utils::addRoute('login',         'LoginCtrl');
Utils::addRoute('logout',        'LoginCtrl');
Utils::addRoute('personNew',     'PersonEditCtrl',	['user','admin']);
Utils::addRoute('personEdit',    'PersonEditCtrl',	['user','admin']);
Utils::addRoute('personSave',    'PersonEditCtrl',	['user','admin']);
Utils::addRoute('personDelete',  'PersonEditCtrl',	['admin']);
Utils::addRoute('reviewNew',     'ReviewEditCtrl',	['user','admin']);
Utils::addRoute('reviewEdit',    'ReviewEditCtrl',	['user','admin']);
Utils::addRoute('reviewDelete',  'ReviewEditCtrl',	['user','admin']);
Utils::addRoute('movieNew',      'MovieEditCtrl',	['admin']);
Utils::addRoute('movieEdit',     'MovieEditCtrl',	['admin']);
Utils::addRoute('movieSave',     'MovieEditCtrl',	['admin']);
Utils::addRoute('movieDelete',   'MovieEditCtrl',	['admin']);