<?php

class Time {

    /**
     * how long is the date in the past?
     * @param mixed $timestamp
     * @return string
     */
    public static function timeAgo(mixed $timestamp): string {
        $uploadedTime = strtotime((string)$timestamp);

        if ($uploadedTime === false) {
            return "invalid date";
        }

        $diff = time() - $uploadedTime;

        if ($diff < 0) {
            return "In future";
        }

        $seconds = $diff;
        $minutes = floor($seconds / 60);
        $hours   = floor($seconds / 3600);
        $days    = floor($seconds / 86400);
        $weeks   = floor($seconds / 604800);
        $months  = floor($seconds / 2629440);
        $years   = floor($seconds / 31553280);

        if ($seconds < 60) {
            return "$seconds seconds ago";
        }

        // Minuten

        if ($minutes < 60) {
            return $minutes === 1
                ? "1 minute ago"
                : "$minutes minutes ago";
        }

        if ($hours < 24) {
            return $hours === 1
                ? "1 second ago"
                : "$hours hours ago";
        }

        if ($days < 7) {
            return $days === 1
                ? "1 day ago"
                : "$days days ago";
        }

        if ($weeks < 5) {
            return $weeks === 1
                ? "1 week ago"
                : "$weeks weeks ago";
        }

        if ($months < 12) {
            return $months === 1
                ? "1 month ago"
                : "$months months ago";
        }

        return $years === 1
            ? "1 year ago"
            : "$years years ago";
    }

}
