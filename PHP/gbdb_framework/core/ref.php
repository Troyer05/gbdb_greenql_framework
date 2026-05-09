<?php

class Ref {
    /**
     * redirects to somewhere
     * @param string $url
     * @return never
     */
    public static function to(string $url): void {
        echo '<meta http-equiv="refresh" content="0; URL=' . $url . '">';

        exit;
    }

    /**
     * reloads actual file
     * @return never
     */
    public static function this_file(): void {
        echo '<meta http-equiv="refresh" content="0; URL=' . Vars::this_file() . '">';

        exit;
    }

}
