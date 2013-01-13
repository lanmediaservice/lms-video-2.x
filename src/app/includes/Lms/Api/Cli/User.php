<?php

class Lms_Api_Cli_User {

    public static function add()
    {
        $opts = new Zend_Console_Getopt(
            array(
                'help|h'    => 'показать справку',
                'user-name|u=s'    => 'имя пользователя (логин)',
                'password|p=s'    => 'пароль, параметр --password-hash будет проигнорирован',
                'password-hash=s'    => 'MD5-хеш пароля',
                'email|e=s'    => 'email',
                'ip=s'    => 'IP вычисленный при регистрации',
                'user-group|g=i'    => 'группа пользователя (1 - пользователь, 2 - модератор, 3 - администратор), по-умолчанию 1',
                'register-at|r=s'    => 'дата регистрации в формате yyyy-mm-dd hh:mm:ss',
                'enabled=i'    => 'флаг активности (по-умолчанию 1)',
            )
        );
        $opts->parse();
        
        if ($opts->getOption('h')) {
            Lms_Api_Cli::showUsageAndExit($opts, 0);
        }

        if ($value = $opts->getOption('p')) {
            $passmd5 = md5($value);
        } else {
            $passmd5 = $opts->getOption('password-hash');
        }
        
        $userName = $opts->getOption('u');
        if (!Lms_Item_User::loginIsFree($userName)) {
            throw new Zend_Console_Getopt_Exception(
                "Пользователь '$userName' уже существует",
                $opts->getUsageMessage());
        }
        $newUser = Lms_Item::create('User');
        $newUser->setLogin($userName)
                ->setPassword($passmd5)
                ->setEmail($opts->getOption('e'))
                ->setUserGroup($opts->getOption('g')?: Lms_Item_User::USER_GROUP_USER)
                ->setBalans(1)
                ->setIp($opts->getOption('ip'))
                ->setsetRegisterDate($opts->getOption('r'))
                ->setEnabled($opts->getOption('enabled')?: 1)
                ->setPreferences('')
                ->save();

        return $newUser->getId();
    }
}
