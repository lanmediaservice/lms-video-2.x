<?php

class Lms_Playlist_Modules_Xspf implements Lms_Playlist_Modules_Interface {
   
    public static function generatePlaylist($files)
    {
        header("Content-type: video/xspf"); 
        header('Content-Disposition: attachment; filename="playlist.xspf"'); 
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<playlist version=\"1\" xmlns=\"http://xspf.org/ns/0/\" xmlns:vlc=\"http://www.videolan.org/vlc/playlist/ns/0/\">\n<trackList>";
        foreach ($files as $file) {
            $path = $file['path'];
            if ($path[0]=='\\') {
                $path = "file:" . str_replace("\\", "/", $path);
            }
            $path = rawurlencode($path);
            $path = str_replace("%2F", "/", $path);
            $path = str_replace("%3A", ":", $path);
            $path = htmlspecialchars(Lms_Translate::translate('CP1251', 'UTF-8', $path));
            $name = htmlspecialchars(Lms_Translate::translate('CP1251', 'UTF-8', $file['name']));
            echo "\n<track>\n    <title>$name</title>\n    <location>$path</location>\n</track>\n";
        }
        echo "\n</trackList>\n</playlist>";
    }
}

