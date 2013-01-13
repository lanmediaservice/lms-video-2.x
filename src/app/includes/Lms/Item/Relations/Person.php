<?php

class Lms_Item_Relations_Person {
    public static function perform()
    {
         Lms_Item_Relations::add('Person', 'Participant', 'person_id', 'person_id', Lms_Item_Relations::MANY);
    }
}
