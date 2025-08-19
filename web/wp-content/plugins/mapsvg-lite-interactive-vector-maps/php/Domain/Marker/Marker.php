<?php

namespace MapSVG;

class Marker extends File
{

    public $default;

    public function convertServerPathToUrl($path)
    {
        if (strpos($path, MAPSVG_PINS_DIR) !== false) {
            $expl = $this->getLastTwoFolders(MAPSVG_PINS_DIR, DIRECTORY_SEPARATOR);
            list(, $important_stuff) = explode($expl, $path);
            $rel = wp_parse_url(MAPSVG_PINS_URL);
            $important_stuff = ltrim(str_replace("\\", "/", $important_stuff), "/");
            return $rel["path"] . $important_stuff;
        } else {
            $upl = MAPSVG_UPLOADS_DIR . DIRECTORY_SEPARATOR . "markers";
            $expl = $this->getLastTwoFolders($upl, DIRECTORY_SEPARATOR);
            list(, $important_stuff) = explode($expl, $path);
            $important_stuff = ltrim(str_replace("\\", "/", $important_stuff), "/");
            $rel = wp_parse_url(MAPSVG_UPLOADS_URL . "markers/");
            return $rel["path"] . $important_stuff;
        }
    }

    public function convertUrlToServerPath($url)
    {
        $rel = wp_parse_url(MAPSVG_PINS_URL);
        if (strpos($url, $rel["path"]) !== false) {
            $expl = $this->getLastTwoFolders(MAPSVG_PINS_DIR, DIRECTORY_SEPARATOR);
            $url = str_replace("/", DIRECTORY_SEPARATOR, $url);
            list(, $important_stuff) = explode($expl, $url);
            return MAPSVG_MAPS_DIR . $important_stuff;
        } else {
            $upl = MAPSVG_UPLOADS_DIR . DIRECTORY_SEPARATOR . "markers";
            $expl = $this->getLastTwoFolders($upl, DIRECTORY_SEPARATOR);
            $url = str_replace("/", DIRECTORY_SEPARATOR, $url);
            list(, $important_stuff) = explode($expl, $url);
            return $upl . $important_stuff;
        }
    }
}
