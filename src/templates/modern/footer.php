<?php
    $statCount = $db->selectCell("SELECT count(*) FROM movies WHERE hidden=0");
    $statVolume = $db->selectCell("SELECT sum(`size`) FROM movies INNER JOIN movies_files USING(movie_id) INNER JOIN files USING(file_id) WHERE hidden=0");
    $statComments = $db->selectCell("SELECT count(*) FROM comments INNER JOIN movies_comments USING(comment_id) INNER JOIN movies USING(movie_id) WHERE hidden=0");
    $statRatings = $db->selectCell("SELECT count(*) FROM movies_users_ratings INNER JOIN movies USING(movie_id) WHERE hidden=0"); 
?>

<footer>
    <p>&copy; &#1054;&#1054;&#1054; &laquo;&#1051;&#1072;&#1085;&#1052;&#1077;&#1076;&#1080;&#1072;&#1057;&#1077;&#1088;&#1074;&#1080;&#1089;&raquo;, 2006 &ndash; <?php echo date('Y')?></p>
    <?php if (array_filter(Lms_Application::getConfig('support_links'))): ?>
        <p>Техническая поддержка:
        <?php foreach (Lms_Application::getConfig('support_links') as $key=>$menuItem):?>
            <?php if ($key>0) echo " | ";?>
            <a href="<?php echo htmlspecialchars($menuItem['url']);?>" target="_blank"><?php echo $menuItem['text'];?></a>
        <?php endforeach;?>
        </p>
    <?php endif; ?>
    <p>
        Статистика: кол-во: <?php echo $statCount; ?> (<?php echo round($statVolume/1024/1024/1024); ?> GiB), <a target='_blank' href='film_list.php'>полный список</a>
        | отзывов: <?php echo $statComments; ?> <a target='_blank' href='rss_comments.php'><img src="templates/<?php echo Lms_Application::getConfig('template');?>/img/rss-orange.gif" style="border:0;position:relative;top:2px;"></a>
        | оценок: <?php echo $statRatings; ?>
   </p>
</footer> 