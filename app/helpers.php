<?php

use Carbon\Carbon;

if (!function_exists('__hardcoded')) {
    function __hardcoded($value) {
        return $value;
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime ($value): ?string {
        return $value ? Carbon::parse($value)->format('d.m.Y H:i:s') : null;
    }
}

if (!function_exists('format_date')) {
    function format_date ($value): ?string {
        return $value ? Carbon::parse($value)->format('d.m.Y') : null;
    }
}

if (!function_exists('mask_phone_old')) {
    function mask_phone_old ($phone) : string {
        $splitPhone = str_split($phone);
        $output = "+7 ";
        foreach ($splitPhone as $key => $item) {
            if ($key === 8 || $key == 5) {
                $output .= " ";
            }
            if ($key === 10) {
                $output .= "-";
            }
            $output .= $item;
        }
        return $output;
    }
}

if (!function_exists('prepare_search_string')) {
    function prepare_search_string($string)
    {
        return "%" . strtolower(str_replace(' ', '%', $string)) . "%";
    }
}

if (!function_exists('unmask_phone')) {
    function unmask_phone ($phone) {
        return str_replace(['(', ')', '-', ' '], '', $phone);
    }
}

if (!function_exists('mask_phone')) {
    function mask_phone ($phone) : string {
        $splitPhone = str_split($phone);
        $output = "";
        foreach ($splitPhone as $key => $item) {
            if ($key === 5) {
                $output .= ")";
            }

            if (in_array($key, [2, 5, 8])) {
                $output .= " ";
            }

            if ($key === 10) {
                $output .= '-';
            }

            if ($key === 2) {
                $output .= "(";
            }
            $output .= $item;
        }
        return $output;
    }
}

if (!function_exists('get_dates_range')) {
    function get_dates_range ($_start, $_finish) {
        $start = Carbon::parse($_start);
        $finish = Carbon::parse($_finish);
        $dates = [
            $start->format('Y-m-d')
        ];
        while (!$start->eq($finish)) {
            $dates[] = $start->addDay()->format('Y-m-d');
        }
        return $dates;
    }
}

if (!function_exists('calculate_percentage')) {
    function calculate_percentage ($needle, $from) {
        if ($from === 0) {
            return 0;
        }
        return ($needle * 100) / $from;
    }
}
