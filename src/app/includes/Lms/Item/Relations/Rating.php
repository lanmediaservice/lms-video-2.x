<?php
class Lms_Item_Relations_Rating {
    public static function perform()
    {
        Lms_Item_Relations::add('Rating', 'Movie', 'movie_id', 'movie_id', Lms_Item_Relations::ONE);
    }
}
