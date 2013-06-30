<?php 
require_once dirname(__FILE__) . "/app/config.php";

Lms_Application::setRequest();
Lms_Application::prepareApi();
Lms_Debug::debug('Request URI: ' . $_SERVER['REQUEST_URI']);

$url = "http://" . $_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>
<html>
<head>
<title>Фильмы в видео-каталоге</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<style>
BODY, INPUT, DIV, TABLE{
	font-family: Tahoma, Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 8pt;
}

A:HOVER {
	text-decoration : underline;
}


TABLE {
	border-top : 1px solid silver;
	border-left : 1px solid silver;
	border-right : 0px;
	border-bottom : 0px;
	border-collapse: collapse;
}
TABLE TD,TH{
	border-top : 0px;
	border-left : 0px;
	border-right : 1px solid silver;
	border-bottom : 1px solid silver;
}
TABLE TH{
	background : #F0F0F0;
}
</style>
</head>
<body>
<?php 

    $order = isset($_REQUEST["order"]) ? $_REQUEST["order"] : "movie_id";
    if (!in_array($order,array("movie_id", "name", "international_name", "year", "size"))) $order = "movie_id";
    $dir = isset($_REQUEST["dir"]) ? $_REQUEST["dir"] : "asc";
    if (!in_array($dir, array("asc","desc"))) $dir = "asc";

    $db = Lms_Db::get('main');

    $director = array();
    $cast = array();

    $rows = $db->select("SELECT movie_id, IF(LENGTH(p.name), p.name, p.international_name) AS `name`, r.name as `role` FROM participants LEFT JOIN roles r USING(role_id) LEFT JOIN persones p USING(person_id) WHERE r.name IN('режиссер','актер','актриса') ORDER BY participant_id");
    foreach ($rows as $row) {
        $movieId = $row['movie_id'];
        if ($row["role"]=="режиссер") {
            $director[$movieId] = $row["name"];
        }
        if (in_array($row["role"],array("актер", "актриса"))) {
            if (empty($cast[$movieId]) || count($cast[$movieId])<=5) {
                $cast[$movieId][] = $row["name"];
            }
        }
    }

    $genres = array();
    $rows = $db->select("SELECT movie_id, name FROM movies_genres LEFT JOIN genres USING(genre_id)");
    foreach ($rows as $row) {
        $movieId = $row['movie_id'];
        $genres[$movieId][] = $row["name"];
    }


    $countries = array();

    $rows = $db->select("SELECT movie_id, name FROM movies_countries LEFT JOIN countries USING(country_id)");
    foreach ($rows as $row) {
        $movieId = $row['movie_id'];
        $countries[$movieId][] = $row["name"];
    }

    $movies = $db->select("SELECT m.*, sum(files.`size`) as `size` FROM movies m LEFT JOIN movies_files USING(movie_id) LEFT JOIN files USING(file_id) WHERE hidden=0 GROUP BY m.movie_id ORDER BY $order $dir");
    echo "<table border='1'>"
            ."<tr>"
            ."<th nowrap>ID <a title='Сортировать по возрастанию' href='?order=id'>&#9650;</a> <a title='Сортировать по убыванию' href='?order=id&dir=desc'>&#9660;</a></th>"
            ."<th nowrap>Рус. <a title='Сортировать по возрастанию' href='?order=name'>&#9650;</a> <a title='Сортировать по убыванию' href='?order=name&dir=desc'>&#9660;</a></th>"
            ."<th nowrap>Англ. <a title='Сортировать по возрастанию' href='?order=originalname'>&#9650;</a> <a title='Сортировать по убыванию' href='?order=originalname&dir=desc'>&#9660;</a></th>"
            ."<th nowrap>Год <a title='Сортировать по возрастанию' href='?order=year'>&#9650;</a> <a title='Сортировать по убыванию' href='?order=year&dir=desc'>&#9660;</a></th>"
            ."<th nowrap>Жанр</th>"
            ."<th nowrap>Страна</th>"
            ."<th nowrap>Режиссер</th>"
            ."<th nowrap>В ролях</th>"
            ."<th nowrap>Размер <a title='Сортировать по возрастанию' href='?order=size'>&#9650;</a> <a title='Сортировать по убыванию' href='?order=size&dir=desc'>&#9660;</a></th>"
            ."</tr>";
    foreach ($movies as $movie) {
        $movieId = $movie['movie_id'];
        echo "<tr>"
        ."<td><a href='{$url}/#/movie/id/$movieId'>$movieId</a></td>"
        ."<td>".$movie["name"]."</td>"
        ."<td>".$movie["international_name"]."</td>"
        ."<td>".$movie["year"]."</td>";

        $mygenres = isset($genres[$movieId]) ? implode("&nbsp;/ ", $genres[$movieId]) : "&nbsp;";
        $mycountries = isset($countries[$movieId]) ? implode("&nbsp;/ ", $countries[$movieId]) : "&nbsp;";

        echo "<td>$mygenres</td>"
        ."<td>$mycountries</td>";
        $mydirector = isset($director[$movieId])? $director[$movieId] : "&nbsp;";
        $myactors = isset($cast[$movieId])? implode(", ", $cast[$movieId]) : "&nbsp;";
        echo "<td>$mydirector</td>";
        echo "<td>$myactors</td>";
        echo "<td>" . round($movie["size"]/1024/1024) . " MiB</td>";
        echo "</tr>";
    }
    echo "</table><br><br>";
?>
</body>
</html>
