<?php

class Lms_Item_Relations_Role {
    public static function perform()
    {
        Lms_Item_Relations::add('Role', 'Participant', 'role_id', 'role_id', Lms_Item_Relations::MANY);
    }
}