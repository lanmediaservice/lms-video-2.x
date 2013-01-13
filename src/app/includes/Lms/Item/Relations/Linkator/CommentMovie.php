<?php

class Lms_Item_Relations_Linkator_CommentMovie {
    static function perform()
    {
        Lms_Item_Relations::add('Linkator_CommentMovie', 'Movie', 'movie_id', 'movie_id', Lms_Item_Relations::ONE);
        Lms_Item_Relations::add('Linkator_CommentMovie', 'Comment', 'comment_id', 'comment_id', Lms_Item_Relations::ONE);
    }
}
