<?php

if (! function_exists('vnd')) {
    /** Định dạng tiền: 45000 -> "45.000đ" */
    function vnd($amount): string
    {
        return number_format((float) $amount, 0, ',', '.').'đ';
    }
}

if (! function_exists('stars')) {
    /** Điểm 4.4 -> "★★★★☆" */
    function stars($rating): string
    {
        $full = (int) round((float) $rating);

        return str_repeat('★', $full).str_repeat('☆', 5 - $full);
    }
}
