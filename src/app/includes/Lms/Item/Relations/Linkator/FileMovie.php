<?php

class Lms_Item_Relations_Linkator_FileMovie {
    static function perform()
    {
        Lms_Item_Relations::add('Linkator_FileMovie','Movie', 'movie_id', 'movie_id', Lms_Item_Relations::ONE);
        Lms_Item_Relations::add('Linkator_FileMovie','File', 'file_id', 'file_id', Lms_Item_Relations::ONE );
    }
}
