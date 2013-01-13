<?php
class Lms_Item_Relations_Movie {
    public static function perform()
    {
        Lms_Item_Relations::add('Movie', 'User', 'created_by', 'ID', Lms_Item_Relations::ONE);
        Lms_Item_Relations::add('Movie', 'Hit', 'movie_id', 'movie_id', Lms_Item_Relations::MANY );
        Lms_Item_Relations::add('Movie', 'Bookmark', 'movie_id', 'movie_id', Lms_Item_Relations::MANY );
        Lms_Item_Relations::add('Movie', 'Linkator_CountryMovie', 'movie_id', 'movie_id', Lms_Item_Relations::MANY);
        Lms_Item_Relations::add('Movie', 'Linkator_GenreMovie', 'movie_id', 'movie_id', Lms_Item_Relations::MANY);
        Lms_Item_Relations::add('Movie', 'Participant', 'movie_id', 'movie_id', Lms_Item_Relations::MANY);
        Lms_Item_Relations::add('Movie', 'Rating', 'movie_id', 'movie_id', Lms_Item_Relations::MANY);
        Lms_Item_Relations::add('Movie', 'Linkator_FileMovie', 'movie_id', 'movie_id', Lms_Item_Relations::MANY);
        Lms_Item_Relations::add('Movie', 'Linkator_CommentMovie', 'movie_id', 'movie_id', Lms_Item_Relations::MANY);
        Lms_Item_Relations::add('Movie', 'MovieUserRating', 'movie_id', 'movie_id', Lms_Item_Relations::MANY);
    }
}
