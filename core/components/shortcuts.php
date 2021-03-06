<?php 
# @*************************************************************************@
# @ @author Mansur Altamirov (Mansur_TL)									@
# @ @author_url 1: https://www.instagram.com/mansur_tl                      @
# @ @author_url 2: http://codecanyon.net/user/mansur_tl                     @
# @ @author_email: highexpresstore@gmail.com                                @
# @*************************************************************************@
# @ ColibriSM - The Ultimate Modern Social Media Sharing Platform           @
# @ Copyright (c) 21.03.2020 ColibriSM. All rights reserved.                @
# @*************************************************************************@

function fetch_or_get(&$var, $alt = null) {
    if (empty($var) != true) {
        return $var;
    }
    else {
        return $alt;
    }
}

function cl_strf() { 
	return call_user_func_array("sprintf", func_get_args());
}

function not_num(&$var) {
    return (empty($var) || is_numeric($var) != true || $var < 1) ? true : false;
}

function not_empty(&$var) {
    return (empty($var) != true);
}

function json($array = array(), $seril = null) {
    if ($seril) {
        return json_encode($array,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    }
    else {
        return json_decode($array,true);
    }
}

function len($string = '') {
    return mb_strlen($string);
}

function len_between($string = '',$s = 0, $e = 0) {
    return ((mb_strlen($string) >= $s) && (mb_strlen($string) <= $e));
}

function is_posnum(&$var) {
    return (not_empty($var) && is_numeric($var) == true && $var >= 1) ? true : false;
}

function is_url($url = null) {
    return filter_var($url, FILTER_VALIDATE_URL);
}


function pre($op = null, $exit = false) {
    echo "<pre>";
    print_r($op);
    echo "</pre>";

    if ($exit) {
        exit();
    }
}