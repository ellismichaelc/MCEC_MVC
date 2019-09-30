<?php

spl_autoload_register(function ($class) {
    $path = __DIR__;

    if(file_exists($path . '/' . $class . '.php')) {
        include $path . '/' . $class . '.php';
    }

    if(file_exists($class . '.php')) {
        include $class . '.php';
    }

    if(file_exists('lib/' . $class . '.php')) {
        include 'lib/' . $class . '.php';
    }
});


function formatBytes($size, $precision = 2)
{
    $base = log($size, 1024);
    $suffixes = array('', 'K', 'M', 'G', 'T');

    return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
}

function formatSeconds($datetime, $full = false, $cap = false, $largest = false, $full_print = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'y',
        'm' => 'm',
        'w' => 'w',
        'd' => 'd',
        'h' => 'h',
        'i' => 'm',
        's' => 's',
    );

    if($full_print) {
        $string = array(
            'y' => ' Years',
            'm' => ' Months',
            'w' => ' Weeks',
            'd' => ' Days',
            'h' => ' Hours',
            'i' => ' Minutes',
            's' => ' Seconds',
        );
    }

    if($cap && is_numeric($cap)) $string = array_slice($string, $cap);

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . '' . $v . ($diff->$k > 1 ? '' : '');
        } else {
            unset($string[$k]);
        }
    }


    if (!$full) $string = array_slice($string, 0, 1);
    if ($largest) $string = array_slice($string, -1, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function formatDays($time) {
    $now = time();
    $old = strtotime($time);
    $diff = $now - $old;

    $days = round($diff / 60 / 60 / 24 - .5);
    $months = round((($diff / 60 / 60 / 24 / 365) * 12) - .5);
    $years = round($diff / 60 / 60 / 24 / 365 - .5);

    if($years > 0) {
        $months = $months - round($years * 12 - .5);
        if($months == 0) return ($years) . " years";
        if($months == 12 || $months == 0) return ($years+1) . " years";
        return "{$years} years, {$months} months";
    } elseif($months > 0) {
        $days = round(($diff / 60 / 60 / 24) % 30);
        return "{$months} months, {$days} days";
    } else {
        return "{$days} days";
    }
}
// TODO: Maybe register some logger stuff here?