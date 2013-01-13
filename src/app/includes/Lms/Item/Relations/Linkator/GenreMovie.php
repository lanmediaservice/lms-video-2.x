<?php

class Lms_Item_Relations_Linkator_GenreMovie {
    static function perform()
    {
        Lms_Item_Relations::add('Linkator_GenreMovie','Movie', 'movie_id', 'movie_id', Lms_Item_Relations::ONE);
        Lms_Item_Relations::add('Linkator_GenreMovie','Genre', 'genre_id', 'genre_id', Lms_Item_Relations::ONE );
    }
}
