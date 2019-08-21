<?php

namespace App\Business\Helper;

class StringHelper
{
    /**
     * Generate random Alpha Numeric character
     * @param int $totalChar total all character to be generated
     * @return string random alnum
     */
    public static function generateRandomAlnum(int $totalChar = 8): string
    {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $totalChar; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }

        return implode($pass); //turn the array into a string
    }

    public static function randomizeStr(int $totalChar = 8, $lower = true, $upper = true, $digit = true): string
    {
        $alphabet = '';
        if ($lower) {
            $alphabet .= 'abcdefghijklmnopqrstuwxyz';
        }
        if ($upper) {
            $alphabet .= 'ABCDEFGHIJKLMNOPQRSTUWXYZ';
        }
        if ($digit) {
            $alphabet .= '0123456789';
        }

        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $totalChar; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }

        return implode($pass); //turn the array into a string
    }

    /**
     * Concatenate list of string in a given array
     * @param array $stringArray array of string to be concat
     * @return string concatenate string
     */
    public static function concat(array $stringArray): string
    {
        return implode(' ', $stringArray);
    }
}
