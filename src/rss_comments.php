<?php 
header("Content-type: text/xml");

define('SKIP_DEBUG_CONSOLE', true);

require_once dirname(__FILE__) . "/app/config.php";

Lms_Application::setRequest();
Lms_Application::prepareApi();
Lms_Debug::debug('Request URI: ' . $_SERVER['REQUEST_URI']);

$limit = Lms_Application::getConfig('rss','count')? : 40;
$title = Lms_Application::getConfig('rss','title')?: "Видео-каталог";
$title .= ": Последние отзывы";
?>
<?php echo '<?xml version="1.0" encoding="windows-1251"?>'; ?>

<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
    <channel>
        <title><?php echo $title ?></title>
        <link><?php echo Lms_Application::getConfig('siteurl'); ?></link>
        <description>Последние отзывы</description>
        <language>ru</language>
        <pubDate><?php echo date("r"); ?></pubDate>

        <?php

            try {
                $db = Lms_Db::get('main');
                $wheres = array();
                $wheres[] = "(ISNULL(to_user_id) OR to_user_id=0)";
                $wheres[] = " m.hidden=0 ";
                $sql = "SELECT m.movie_id, m.name, m.international_name, m.year, users.Login as user_name, c.created_at, c.`text`, c.comment_id "
                        . "FROM comments c INNER JOIN movies_comments USING(comment_id) INNER JOIN movies m USING(movie_id) INNER JOIN users ON(c.user_id=users.ID) "
                        . "WHERE (ISNULL(to_user_id) OR to_user_id=0) AND m.hidden=0 "
                        . "ORDER BY c.created_at DESC LIMIT ?d";

                $comments = $db->select(
                    $sql, $limit
                );
                foreach ($comments as $comment):
                    $text = Lms_Text::htmlizeText($comment['text']);
                    $title = $comment['name'];
                    $title .= $comment['international_name']? " / {$comment['international_name']}" :'';
                    $title .= " ({$comment['year']})";
                    $maxPreviewLength = Lms_Application::getConfig('rss', 'max_preview_length')?: 120;
                    $previewLength = $maxPreviewLength - strlen($title);
                    $previewText = trim(strip_tags($comment["text"]));
                    if (strlen($previewText)>$previewLength) {
                        $previewText = substr($previewText, 0, $previewLength) . "...";
                    } ?>
                    <item>
                        <title><?php echo $title . ($previewText? htmlspecialchars(" - $previewText") : "" ); ?></title>
                        <author><?php $comment['user_name']; ?></author>
                        <link><?php echo rtrim(Lms_Application::getConfig('siteurl'), '/') . "/#movie/id/{$comment['movie_id']}/page/comments"; ?></link>
                        <description><?php echo $comment['user_name'] . ": " . strip_tags($text); ?></description>
                        <content:encoded><![CDATA[
                            <div style='font-family: Tahoma, Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 8pt;'>
                                <b><?php echo $comment['user_name']; ?> комментирует <?php echo $title; ?>:</b><br>
                                <?php echo $text; ?>
                            </div>
                        ]]></content:encoded>
                        <pubDate><?php echo date("r", strtotime($comment['created_at']));?> </pubDate>
                        <guid><?php echo rtrim(Lms_Application::getConfig('siteurl'), '/') . "/#movie/id/{$comment['movie_id']}/page/comments/" . $comment['comment_id']; ?></guid>
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