<?php
/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: Kinopoisk.php 700 2011-06-10 08:40:53Z macondos $
 */

class Lms_Service_Adapter_Imdb
{
    static private $rolesMap = array(
        'director' => 'режиссер',
        'actor' => 'актер'
    );
    static private $genresMap = array(
        "Action" => "Боевик",
        "Adventure" => "Приключения",
        "Animation" => "Мультфильм",
        "Biography" => "Биография",
        "Comedy" => "Комедия",
        "Crime" => "Криминал",
        "Documentary" => "Документальный",
        "Drama" => "Драма",
        "Family" => "Семейный",
        "Fantasy" => "Фэнтези",
        "Film-Noir" => "Фильм-нуар",
        "Game-Show" => "Игровое шоу",
        "History" => "История",
        "Horror" => "Ужасы",
        "Music" => "Музыка",
        "Musical" => "Мюзикл",
        "Mystery" => "Мистика",
        "News" => "Новости",
        "Reality-TV" => "Реал-ТВ",
        "Romance" => "Мелодрама",
        "Sci-Fi" => "Фантастика",
        "Short" => "Короткометражный",
        "Sport" => "Спорт",
        "Talk-Show" => "Ток-шоу",
        "Thriller" => "Триллер",
        "War" => "Война",
        "Western" => "Вестерн",
    );
    
    static private $countriesMap = array(
        "France" => "Франция",
        "UK" => "Великобритания",
        "USA" => "США",
        "Soviet Union" => "СССР",
        "Romania" => "Румыния",
        "Switzerland" => "Швейцария",
        "Russia" => "Россия",
        "Australia" => "Австралия",
        "West Germany" => "ФРГ",
        "Thailand" => "Тайланд",
        "China" => "Китай",
        "Hong Kong" => "Гонгконг",
        "Mexico" => "Мексика",
        "Italy" => "Италия",
        "Spain" => "Испания",
        "Germany" => "Германия",
        "Japan" => "Япония",
        "Canada" => "Канада",
        "South Korea" => "Южная Корея",
        "Netherlands" => "Нидерланды",
        "Taiwan" => "Тайвань",
        "Hungary" => "Венгрия",
        "Ireland" => "Ирландия",
        "Poland" => "Польша",
        "Czech Republic" => "Чехия",
        "Denmark" => "Дания",
        "Sweden" => "Швеция",
        "Norway" => "Норвегия",
        "Finland" => "Финляндия",
        "New Zealand" => "Новая Зеландия",
        "South Africa" => "ЮАР",
        "Ukraine" => "Украина",
        "Belgium" => "Бельгия",
        "Luxembourg" => "Люксембург",
        "Croatia" => "Хорватия",
        "Israel" => "Израиль",
        "Bulgaria" => "Болгария",
        "Turkey" => "Турция",
        "Malta" => "Мальта",
        "Bosnia-Herzegovina" => "Босния-Герцеговина",
        "Slovenia" => "Словения",
        "Greece" => "Греция",
        "East Germany" => "ГДР",
        "Slovakia" => "Словакия",
        "Singapore" => "Сингапур",
        "Austria" => "Австрия",
        "Afghanistan" => "Афганистан",
        "Albania" => "Албания",
        "Algeria" => "Алжир",
        "Andorra" => "Андорра",
        "Angola" => "Ангола",
        "Antigua and Barbuda" => "Антигуа и Барбуда",
        "Argentina" => "Аргентина",
        "Armenia" => "Армения",
        "Azerbaijan" => "Азербайджан",
        "Bahamas" => "Багамские Острова",
        "Bahrain" => "Бахрейн",
        "Bangladesh" => "Бангладеш",
        "Barbados" => "Барбадос",
        "Belarus" => "Беларусь",
        "Belize" => "Белиз",
        "Benin" => "Бенин",
        "Bhutan" => "Бутан",
        "Bolivia" => "Боливия",
        "Botswana" => "Ботсвана",
        "Brazil" => "Бразилия",
        "Burkina Faso" => "Буркина-Фасо",
        "Burma" => "Бирма",
        "Burundi" => "Бурунди",
        "Cambodia" => "Камбоджа",
        "Cameroon" => "Камерун",
        "Cape Verde" => "Зеленый мыс",
        "Central African Republic" => "Центрально-африканская Республика",
        "Chad" => "Чад",
        "Chile" => "Чили",
        "Colombia" => "Колумбия",
        "Congo" => "Конго",
        "Costa Rica" => "Коста Рика",
        "Cuba" => "Куба",
        "Cyprus" => "Кипр",
        "Czechoslovakia" => "Чехословакия",
        "Djibouti" => "Джибути",
        "Dominican Republic" => "Доминиканская Республика",
        "Ecuador" => "Эквадор",
        "Egypt" => "Египет",
        "El Salvador" => "Сальвадор",
        "Eritrea" => "Эритрея",
        "Estonia" => "Эстония",
        "Ethiopia" => "Эфиопия",
        "Faroe Islands" => "Фарерские острова",
        "Federal Republic of Yugoslavia" => "Федеральная Республика Югославии",
        "Fiji" => "Фиджи",
        "Gabon" => "Габон",
        "Georgia" => "Джорджия",
        "Ghana" => "Гана",
        "Greenland" => "Гренландия",
        "Guadeloupe" => "Гваделупа",
        "Guatemala" => "Гватемала",
        "Guinea" => "Гвинея",
        "Guinea-Bissau" => "Гвинея-Биссау",
        "Guyana" => "Гайана",
        "Haiti" => "Гаити",
        "Honduras" => "Гондурас",
        "Iceland" => "Исландия",
        "India" => "Индия",
        "Indonesia" => "Индонезия",
        "Iran" => "Иран",
        "Iraq" => "Ирак",
        "Ivory Coast" => "Берег Слоновой Кости",
        "Jamaica" => "Ямайка",
        "Jordan" => "Иордания",
        "Kazakhstan" => "Казахстан",
        "Kenya" => "Кения",
        "Korea" => "Корея",
        "Kosovo" => "Косово",
        "Kuwait" => "Кувейт",
        "Kyrgyzstan" => "Кыргызстан",
        "Laos" => "Лаос",
        "Latvia" => "Латвия",
        "Lebanon" => "Ливан",
        "Liberia" => "Либерия",
        "Libya" => "Ливия",
        "Liechtenstein" => "Лихтенштейн",
        "Lithuania" => "Литва",
        "Macau" => "Макао",
        "Madagascar" => "Мадагаскар",
        "Malaysia" => "Малайзия",
        "Mali" => "Мали",
        "Martinique" => "Мартиник",
        "Mauritania" => "Мавритания",
        "Mauritius" => "Маврикий",
        "Moldova" => "Молдова",
        "Monaco" => "Монако",
        "Mongolia" => "Монголия",
        "Morocco" => "Марокко",
        "Mozambique" => "Мозамбик",
        "Namibia" => "Намибия",
        "Nepal" => "Непал",
        "Nicaragua" => "Никарагуа",
        "Niger" => "Нигер",
        "Nigeria" => "Нигерия",
        "North Korea" => "Северная Корея",
        "North Vietnam" => "Северный Вьетнам",
        "Pakistan" => "Пакистан",
        "Palestine" => "Палестина",
        "Panama" => "Панама",
        "Papua New Guinea" => "Папуа Новая Гвинея",
        "Paraguay" => "Парагвай",
        "Peru" => "Перу",
        "Philippines" => "Филиппины",
        "Portugal" => "Португалия",
        "Puerto Rico" => "Пуэрто-Рико",
        "Republic of Macedonia" => "Республика Македония",
        "Rwanda" => "Руанда",
        "San Marino" => "Сан-Марино",
        "Saudi Arabia" => "Саудовская Аравия",
        "Senegal" => "Сенегал",
        "Serbia and Montenegro" => "Сербия и Черногория",
        "Seychelles" => "Сейшельские Острова",
        "Siam" => "Сиам",
        "Somalia" => "Сомали",
        "Sri Lanka" => "Шри Ланка",
        "Sudan" => "Судан",
        "Suriname" => "Суринами",
        "Syria" => "Сирия",
        "Tajikistan" => "Таджикистан",
        "Tanzania" => "Танзания",
        "Togo" => "Того",
        "Tonga" => "Тонга",
        "Trinidad And Tobago" => "Тринидад и Тобаго",
        "Tunisia" => "Тунис",
        "Turkmenistan" => "Туркменистан",
        "Uganda" => "Уганда",
        "United Arab Emirates" => "Объединенные Арабские Эмираты",
        "Uruguay" => "Уругвай",
        "Uzbekistan" => "Узбекистан",
        "Venezuela" => "Венесуэлла",
        "Vietnam" => "Вьетнам",
        "Western Sahara" => "Западная Сахара",
        "Yemen" => "Йемен",
        "Yugoslavia" => "Югославия",
        "Zaire" => "Заир",
        "Zambia" => "Замбия",
        "Zimbabwe" => "Зимбабве"
    );
    
    
    
    public static function constructPath($action, $params)
    {
        switch($action){
            case 'search':
                $query = Lms_Text::translit($params['query']);
                $query = urlencode($query);
                return "http://www.imdb.com/find?q=$query&s=tt";
                break;
            case 'film':
                return "http://www.imdb.com/title/tt" . sprintf('%07d', $params['id']) . "/";
                break;
        }
    }

    public static function getImdbIdFromUrl($url)
    {
        if (preg_match('{http://www.imdb.com/title/tt(\d+)}', $url, $matches)) {
            return (int)$matches[1];
        } else {
            throw new Lms_Exception("Invalid imdb url: $url");
        }
    }

    public static function afterParseSearchResults($url, &$data)
    {
        if (isset($data['attaches']['film'])) {
            $film = $data['attaches']['film'];
            $url = $data['suburls']['film'][2];
            
            self::afterParseMovie($url, $film);
            
            $cast = array();
            $directors = array();
            foreach ($film['persones'] as $person) {
                switch ($person['role']) {
                    case 'режиссер':
                        $directors[] = array_pop($person['names']);
                        break;
                    case 'актер': // break intentionally omitted
                    case 'актриса':
                        $cast[] = array_pop($person['names']);
                        break;
                    default:
                        break;
                }
            }            
            $data['items'] = array();
            $data['items'][] = array(
                "names" => $film['names'],
                "year" => $film['year'],
                "url" => $url,
                "image" => $film['poster'],
                "country" => implode(", ", $film['countries']),
                "director" => implode(", ", $directors),
                "genre" => implode(", ", $film['genres']),
                "actors" => Lms_Text::tinyString(implode(", ", $cast), 100, 1),
                "rating" => isset($film['rating_imdb_value'])? $film['rating_imdb_value'] : null,
            );
        }
    }    
    
    public static function afterParseMovie($url, &$data)
    {
        $imdbId = self::getImdbIdFromUrl($url);
        if ($data) {
            $data['international_name'] = $data['names'][0];
            $data['poster'] = isset($data['posters'][0])? $data['posters'][0] : '';
            $data['imdb_id'] = $imdbId;
            $data['rating_imdb_value'] = isset($data['rating'])? $data['rating'] : '';
            $data['rating_imdb_count'] = isset($data['rating_count'])? $data['rating_count'] : '';
            foreach ($data['genres'] as &$genre) {
                $genre = isset(self::$genresMap[$genre])? self::$genresMap[$genre] : $genre;
            }
            foreach ($data['countries'] as &$country) {
                $country = isset(self::$countriesMap[$country])? self::$countriesMap[$country] : $country;
            }
            foreach ($data['persones'] as &$person) {
                $person['role'] = isset(self::$rolesMap[$person['role']])? self::$rolesMap[$person['role']] : $person['role'];
            }
        }
    }
    
    public static function afterParsePerson($url, &$data)
    {
    }    
}

