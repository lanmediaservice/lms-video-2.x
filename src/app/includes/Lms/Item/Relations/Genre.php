<?php
class Lms_Item_Relations_Genre {
    public static function perform()
    {
        Lms_Item_Relations::add('Genre', 'Linkator_GenreMovie','genre_id', 'genre_id', Lms_Item_Relations::MANY);
    }
}
