<?php
class Lms_Item_Relations_Comment {
    public static function perform()
    {
        Lms_Item_Relations::add('Comment', 'Linkator_CommentMovie','comment_id', 'comment_id', Lms_Item_Relations::ONE);
    }
}
