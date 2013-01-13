<?php
class Lms_Item_Relations_File {
    public static function perform()
    {
        Lms_Item_Relations::add('File', 'Linkator_FileMovie','file_id', 'file_id', Lms_Item_Relations::ONE);
    }
}
