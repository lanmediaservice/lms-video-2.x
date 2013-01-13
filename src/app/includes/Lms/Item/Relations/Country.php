<?php
class Lms_Item_Relations_Country {
    public static function perform()
    {
        Lms_Item_Relations::add('Country', 'Linkator_CountryMovie','country_id', 'country_id', Lms_Item_Relations::MANY);
    }
}
