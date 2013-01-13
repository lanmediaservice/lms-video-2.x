<?php
/**
 * LMS Library
 * 
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @author Alex Tatulchenkov <webtota@gmail.com>
 * @version $Id: Relations.php 260 2009-11-29 14:11:11Z macondos $
 * @copyright 2008-2009
 * @package Lms_Item 
 */

/**
 * Хранилище определений связей между сущностями. Для быстрого поиска нужных
 * связей отдельно хранятся во все стороны от объектов. Т.к. определения связей
 * относятся к реальным таблицам, то определение связи многие-ко многим напрямую
 * не может быть реализовано
 * Примеры:
 * а)  // Один-ко-многим [Movie]1←→∞[Rating]
 *     //у фильма куча рейтингов пользователей:
 *     Lms_Item_Relations::add(
 *         'Movie', 'Rating',
 *         'movie_id', 'movie_id',
 *         Lms_Item_Relations::MANY
 *     );
 *     //у рейтинга может быть только 1 фильм:
 *     Lms_Item_Relations::add(
 *         'Rating', 'Movie',
 *         'movie_id', 'movie_id',
 *         Lms_Item_Relations::ONE
 *     );
 * 
 * б)  // Один-ко-многим [User]1←→∞[Rating]
 *     //у юзера куча рейтингов фильмов:
 *     Lms_Item_Relations::add(
 *         'User', 'Rating',
 *         'user_id', 'user_id',
 *         Lms_Item_Relations::MANY
 *     );
 *     //у рейтинга может быть только 1 юзер:
 *     Lms_Item_Relations::add(
 *         'Rating', 'User',
 *         'user_id', 'user_id',
 *         Lms_Item_Relations::ONE
 *     );
 * 
 * в) варианты а) и б) образуют реализацию связи
 *    многие-ко-многим для [User]∞←→∞[Movie]
 * 
 * г)  //Один-к-одному [User]1←→1[Profile]
 *     //у юзера может быть только один профиль:
 *     Lms_Item_Relations::add(
 *         'User', 'Profile',
 *         'user_id', 'user_id',
 *         Lms_Item_Relations::ONE
 *     );
 *     //у профиля может быть только 1 юзер, его владелец:
 *     Lms_Item_Relations::add(
 *         'Profile', 'User',
 *         'user_id', 'user_id',
 *         Lms_Item_Relations::ONE
 *     );
 * 
 * д)
 *     // реализация многие-ко-многим с суррогатным ключом
 *     //у фильма куча участников (Linkator_CharacterMoviePersonRole)
 *     Lms_Item_Relations::add(
 *         'Movie', 'Linkator_CharacterMoviePersonRole',
 *         'movie_id', 'movie_id',
 *         Lms_Item_Relations::MANY
 *     );
 *     //персонаж участвовал в разном
 *     Lms_Item_Relations::add(
 *         'Character', 'Linkator_CharacterMoviePersonRole',
 *         'character_id', 'character_id',
 *         Lms_Item_Relations::MANY
 *     );
 *     //персоналия участвовала в разном
 *     Lms_Item_Relations::add(
 *         'Person', 'Linkator_CharacterMoviePersonRole',
 *         'person_id', 'person_id',
 *         Lms_Item_Relations::MANY
 *     );
 *     //В какой-то роли (профессии) на съемочной площадке может быть много кто:
 *     Lms_Item_Relations::add(
 *         'Role', 'Linkator_CharacterMoviePersonRole',
 *         'role_id', 'role_id',
 *         Lms_Item_Relations::MANY
 *     );
 *     
 *     //у одного участвующего субъекта в контексте фильма, роли, персонажа,
 *     //исполнителя может быть только:
 *     //один фильм:
 *     Lms_Item_Relations::add(
 *         'Linkator_CharacterMoviePersonRole', 'Movie',
 *         'movie_id', 'movie_id',
 *         Lms_Item_Relations::ONE
 *     );
 *     //один персонаж:
 *     Lms_Item_Relations::add(
 *         'Linkator_CharacterMoviePersonRole', 'Character',
 *         'character_id', 'character_id',
 *         Lms_Item_Relations::ONE
 *     );
 *     //одна персоналия:
 *     Lms_Item_Relations::add(
 *         'Linkator_CharacterMoviePersonRole', 'Person',
 *         'person_id', 'person_id',
 *         Lms_Item_Relations::ONE
 *     );
 *     //одна роль:
 *     Lms_Item_Relations::add(
 *         'Linkator_CharacterMoviePersonRole', 'Role',
 *         'role_id', 'role_id',
 *         Lms_Item_Relations::ONE
 *     );
 *  
 */
class Lms_Item_Relations
{
    const ONE = 1;
    const MANY = 2;
    
    /**
     * Определения связей
     * (_relations[<ParentEntityName>][<childEntityName>] = array(
     *      'parent_key'=>...
     *      'foreign_key'=>...
     *      'type'=>Lms_Item_Store_Relations::ONE/MANY
     * ))
     * @var array
     */
    private static $_relations;
    
    /**
     * Добавляет связь типа $type между сущностями $parentEntityName и 
     * $childEntityName по полям $parentKey, $foreignKey
     *
     * @param $parentEntityName
     * @param $childEntityName
     * @param $parentKey
     * @param $foreignKey
     * @param $type
     * @return bool
     */
    public static function add(
        $parentEntityName, $childEntityName, $parentKey, $foreignKey, $type
    )
    {
        if (isset(self::$_relations[$parentEntityName][$childEntityName])) {
            throw new Lms_Exception('Debug exception: relation already added');
        }
        self::$_relations[$parentEntityName][$childEntityName] = array(
            'parent_key' => $parentKey,
            'foreign_key' => $foreignKey,
            'type'=>$type
        );
        return true;
    }
    
    /**
     * Возвращает определение связи между
     * сущностями $parentEntityName и $childEntityName
     * @return array
     */
    public static function get($parentEntityName, $childEntityName)
    {
        if (!isset(self::$_relations[$parentEntityName][$childEntityName])) {
            return false;
        }
        return self::$_relations[$parentEntityName][$childEntityName];
    }
    
    /**
     * Возвращает определения всех связей сущности
     *
     * @param string $parentEntityName
     * @return array
     */
    
    public static function getAll($parentEntityName)
    {
        if (!isset(self::$_relations[$parentEntityName])) {
            return false;
        }
        return self::$_relations[$parentEntityName];
    }
}