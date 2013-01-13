<?php

class Lms_Item_Relations_Linkator_CountryMovie {
    static function perform()
    {
        Lms_Item_Relations::add('Linkator_CountryMovie','Movie', 'movie_id', 'movie_id', Lms_Item_Relations::ONE);
        Lms_Item_Relations::add('Linkator_CountryMovie','Country', 'country_id', 'country_id', Lms_Item_Relations::ONE );
    }
}
