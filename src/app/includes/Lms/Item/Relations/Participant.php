<?php
class Lms_Item_Relations_Participant {
    public static function perform()
    {
        Lms_Item_Relations::add('Participant', 'Movie', 'movie_id', 'movie_id', Lms_Item_Relations::ONE);
        Lms_Item_Relations::add('Participant', 'Person', 'person_id', 'person_id', Lms_Item_Relations::ONE);
        Lms_Item_Relations::add('Participant', 'Role', 'role_id', 'role_id', Lms_Item_Relations::ONE);
    }
}
