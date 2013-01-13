<?php 
header("Content-type: text/xml");

define('SKIP_DEBUG_CONSOLE', true);

require_once dirname(__FILE__) . "/app/config.php";

Lms_Application::setRequest();
Lms_Application::prepareApi();
Lms_Debug::debug('Request URI: ' . $_SERVER['REQUEST_URI']);

$limit = Lms_Application::getConfig('rss','count')? : 40;
$title = Lms_Application::getConfig('rss','title')?: "Видео-каталог";

$url = "http://" . $_SERVER['HTTP_HOST'];
?>
<?php echo '<?xml version="1.0" encoding="windows-1251"?>'; ?>

<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title><?php echo $title ?></title>
        <link><?php echo Lms_Application::getConfig('siteurl'); ?></link>
        <description>Последние поступления</description>
        <language>ru</language>
        <pubDate><?php echo date("r"); ?></pubDate>

        <?php

            try {
                
                $result = Lms_Api_Server_Video::getCatalog(array('size'=>$limit));
                $response = $result->getResponse();
                $movies = $response['movies'];
                
                $moviesIds = array();
                foreach ($movies as $movie) {
                    $moviesIds[] = $movie['movie_id'];
                }
                
                $db = Lms_Db::get('main');
                $descriptions = $db->selectCol('SELECT movie_id AS ARRAY_KEY, description FROM movies WHERE movie_id IN(?a)', $moviesIds);
                
                foreach ($movies as $movie):
                    $cover = ((!preg_match("{^http://}", $movie['cover']))? $url : '') . $movie['cover'];
                    $title = $movie['name'];
                    $title .= $movie['international_name']? " / {$movie['international_name']}" :'';
                    $title .= " ({$movie['year']})"; ?>
                    <item>
                        <title><?php echo $title; ?></title>
                        <link><?php echo rtrim(Lms_Application::getConfig('siteurl'), '/') . "/#movie/id/{$movie['movie_id']}"; ?></link>
                        <description><?php echo !empty($movie['genres'])? implode(" / ", $movie['genres']) : ''; ?> (<?php echo implode(" / ", $movie['countries']?: array()); ?>)</description>
                        <content:encoded><![CDATA[
                            <table style='font-family: Tahoma, Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 8pt;'>
                                <tr>
                                    <td valign='top'>
                                        <img src="<?php echo $cover;?>">
                                    </td>
                                    <td valign='top'>
                                        <b>Жанр:</b> <?php echo !empty($movie['genres'])? implode(" / ", $movie['genres']) : ''; ?> <br/>
                                        <?php if (isset($movie['countries'])):?>
                                            <b>Страна:</b> <?php echo implode(" / ", $movie['countries']); ?> <br/>
                                        <?php endif; ?>
                                        <?php if (isset($movie['directors'])):?>
                                            <b>Режиссер:</b> <?php echo implode(", ", array_slice($movie['directors'], 0, 2)); ?> <br/>
                                        <?php endif; ?>
                                        <?php if (isset($movie['cast'])):?>
                                            <b>В ролях:</b> <?php echo implode(", ", array_slice($movie['cast'], 0, 4)); ?> <br/>
                                        <?php endif; ?>
                                        <b>Описание:</b> <?php echo $descriptions[$movie['movie_id']];?><br/><br/>
                                        <?php if (!empty($movie['rating_imdb_value'])): ?>
                                            Рейтинг IMDB: <?php echo $movie['rating_imdb_value'];?><br/>
                                        <?php endif; ?>
                                        <?php if (!empty($movie['rating_kinopoisk_value'])): ?>
                                            Рейтинг KinoPoisk.RU: <?php echo $movie['rating_kinopoisk_value'];?><br/>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        ]]></content:encoded>
                        <pubDate><?php echo date("r", strtotime($movie['updated_at']));?> </pubDate>
                        <guid><?php echo rtrim(Lms_Application::getConfig('siteurl'), '/') . "/#movie/id/{$movie['movie_id']}/updated/" . strtotime($movie['updated_at']); ?></guid>
                    </item>
                <?php endforeach;
            } catch (Exception $e) {
                Lms_Debug::err($e->getMessage());
            }
        ?>
    </channel>
</rss>
<?php 
    Lms_Application::close();
?>