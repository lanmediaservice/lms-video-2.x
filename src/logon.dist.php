<?php
/**
 * 
 * (C) 2006 Ilya Spesivtsev, iljasp@tut.by
 *
 * Авторизация/регистрация
 *
 * @author Ilya Spesivtsev
 * @version 1.05
 */

$db = Lms_Db::get('main');
$IP = Lms_Ip::getIp();

$email = "";
$errors = "";

if (isset($_GET['register']) || isset($_POST['register'])) {
    Lms_Application::clearAuthData();
    $errors = array();
    $login = '';
    $email = '';
    if (isset($_POST['register'])) {
        if (isset($_POST['login']) && isset($_POST['pass']) && isset($_POST['pass2'])){
            $login = $_POST['login'];
            $pass = $_POST['pass'];
            $pass2 = $_POST['pass2'];
            $email = $_POST['email'];
            if (strlen($login)<3) {
                $errors[] = "Ошибка. Логин содержит менее 3 символов";
            }
            if (strlen($login)>16) {
                $errors[] = "Ошибка. Логин содержит более 16 символов";
            }
            if (!preg_match('{^[a-zA-Z0-9][a-zA-Z0-9]*[a-zA-Z0-9]$}', $login)) {
                $errors[] = "Ошибка. Логин должен состоять только из латинских букв или цифр";
            }
            if (strlen($pass)<3) {
                $errors[] = "Ошибка. Пароль содержит менее 3 символов";
            }
            if (strlen($pass)>16) {
                $errors[] = "Ошибка. Пароль содержит более 16 символов";
            }
            if (!preg_match('{^[a-zA-Z0-9][a-zA-Z0-9]*[a-zA-Z0-9]$}',$pass)) {
                $errors[] = "Ошибка. Пароль должен состоять только из латинских букв или цифр";
            }
            if ($pass2!=$pass) {
                $errors[] = "Ошибка. Пароли не совпадают";
            }
            if (!preg_match('{^\S+@\S+\.\S+$}i', $email)) {
                $errors[] = 'Проверьте правильность ввода email';
            }        
            if (!$errors){
                if (!Lms_Item_User::loginIsFree($login)) {
                    $errors[] = "Ошибка. Выбранный Вами логин уже занят";
                }
            }
            if (!$errors && Lms_Application::getConfig("register_timeout")){
                if (Lms_Item_User::testLimit($IP, Lms_Application::getConfig("register_timeout"))) {
                    $errors[] = "Ошибка. Для вашего IP: $IP уже существует зарегистрированный пользователь. <a href='?exit=1'>Вход</a>";
                }
            }
            if (!$errors){
                try { 
                    $passmd5 = md5($pass);
                    $newUser = Lms_Item::create('User');
                    $newUser->setLogin($login)
                            ->setPassword($passmd5)
                            ->setEmail($email)
                            ->setUserGroup(Lms_Item_User::count()? Lms_Item_User::USER_GROUP_USER : Lms_Item_User::USER_GROUP_ADMIN)
                            ->setBalans(1)
                            ->setEnabled(1)
                            ->setPreferences('')
                            ->save();

                    Lms_User::reset();
                    Lms_Application::setAuthData($login, $pass, !empty($_POST['remember']));
                    Lms_User::authenticate();
                } catch (Exception $e) {
                    Lms_Debug::crit($e->getMessage());
                    Lms_Debug::crit($e->getTraceAsString()); 
                    $errors[] = 'Ошибка: ' . $e->getMessage();
                }   
            }
        }
    }
    
    $user = Lms_User::getUser();
    if (!$user->getId() || $user->getGroup()=='guest'): ?>
        <html>
            <head>
                <title>Видео-каталог</title>
                <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
                <style type="text/css">
                    * {font-size:100.01%; font-family: Arial;}
                    body {font-size:0.8em}
                    form {margin:0}
                    table table td {padding:0.25em}
                </style>
            </head>
            <body onload="document.getElementById('myform').action = window.location.protocol + '//' + window.location.host + window.location.pathname">
                <table border="0" width="100%" height="100%">
                    <tr>
                        <td align="center">
                            <?php if ($errors): ?> 
                                <div style='width:40em; text-align:left; border: 1px solid silver; background: #FFAAAA; padding:15px;'><?php echo implode('<br>', $errors);?></div><br>
                            <?php endif?>
                            <div style="width:40em; text-align:left; border: 1px solid silver; background: #F5F5F5; padding:15px;">
                                <span style="font-size:150%; font-weight:bold; color:black;">Регистрация</span><br>
                                <span style="font-size:85%; color:gray">для новых пользователей</span><br><br>
                                <form action="?" method="post" id="myform">
                                    <input type="hidden" name="register" value="1">
                                    <table border="0" width="100%">
                                        <tbody>
                                            <tr>
                                                <td>Логин:</td>
                                                <td><input name="login" type="text" value="<?php echo $login;?>"></td>
                                                <td><span style="font-size:85%; color:gray">(логин должен быть от 3 до 16 латинских букв или цифр)</span></td>
                                            </tr>

                                            <tr>
                                                <td>Пароль:</td>
                                                <td><input name="pass" type="password"></td>
                                                <td rowspan="2"><span style="font-size:85%; color:gray">(пароль должен быть от 3 до 16 латинских букв или цифр)</span></td>
                                            </tr>

                                            <tr>
                                                <td>Повторите пароль:</td>
                                                <td><input name="pass2" type="password"></td>
                                            </tr>
                                            <tr>
                                                <td>Email:</td>
                                                <td><input name="email" type="text" value="<?php echo $email;?>"></td>
                                                <td rowspan="3"><span style="font-size:85%; color:gray"></span></td>
                                            </tr>
                                            <tr>
                                                <td colspan="3"><input id="remember" type="checkbox" value="1" name="remember"><label for="remember">Автоматически входить</label></td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" align="center"><input type="submit" value="OK"></td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" align="left">
                                                    <span style="font-size:xx-small">
                                                        Внимание! Все видеоматериалы (avi-файлы) представлены исключительно для ознакомления, без целей коммерческого использования. Права в отношении фильмов принадлежат их законным правообладателям. Любое коммерческое использование без разрешения законных правообладателей запрещено. После ознакомительного просмотра приобретите лицензионный DVD-диск или видеокассету с понравившимся фильмом.
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <a href="?exit=1">Вход</a>
                                </form>
                            </div>
                        </td>
                    </tr>
                </table>
            </body>
        </html>
        <?php exit;?>
    <?php
    endif;
} else {
    $exit = isset($_GET['exit']) ? $_GET['exit'] : 0;
    if (isset($_POST['login']) && isset($_POST['pass'])){
        Lms_Application::setAuthData($_POST['login'], $_POST['pass'], !empty($_POST['remember']));
        Lms_User::authenticate();
        $user = Lms_User::getUser();
        if (!$user->getId() || $user->getGroup()=='guest') {
            $errors = 'Неверное имя пользователя или пароль';
            Lms_Application::clearAuthData();
        }
    }
    $user = Lms_User::getUser();
    if ($exit || !$user->getId()): ?>
        <html>
            <head>
                <title>Видео-каталог</title>
                <meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
                <style type="text/css">
                    * {font-size:100.01%; font-family: Arial;}
                    body {font-size:0.8em}
                    form {margin:0}
                    table table td {padding:0.25em}
                </style>
            </head>
            <body onload="document.getElementById('myform').action = window.location.protocol + '//' + window.location.host + window.location.pathname">
                <table border="0" width="100%" height="100%">
                    <tr>
                        <td align="center">
                            <?php if ($errors): ?> 
                                <div style='width:40em; text-align:left; border: 1px solid silver; background: #FFAAAA; padding:15px;'><?php echo $errors;?></div><br>
                            <?php endif?>
                            <div style="width:23em; text-align:left; border: 1px solid silver; background: #F5F5F5; padding:15px;">
                                <span style="font-size:150%; font-weight:bold; color:black;">Вход</span><br>
                                <span style="font-size:85%; color:gray">для зарегистрированных пользователей</span>
                                <form action="?" method="post" id="myform">
                                    <input type="hidden" name="logon" value="1">
                                    <table border="0" width="100%">
                                        <tbody>
                                            <tr><td>Имя пользователя:</td><td><input name="login" value="<?php echo isset($_POST['login'])? $_POST['login'] : '';?>"></td></tr>
                                            <tr><td>Пароль:</td><td><input name="pass" type="password"></td></tr>
                                            <tr><td colspan="2"><input id="remember" type="checkbox" value="1" name="remember">
                                                <label for="remember">Автоматически входить</label></td></tr>
                                            <tr><td colspan="2" align="center"><input type="submit" value="OK"></td></tr>
                                        </tbody>
                                    </table>
                                    <a href="?register=1">Регистрация</a>
                                </form>
                            </div>
                        </td>
                    </tr>
                </table>
            </body>
        </html>
        <?php exit;?>
    <?php endif;
}


$user = Lms_User::getUser();
if (!$user->getId()) {
    die('упс');
}
        