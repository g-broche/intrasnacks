<?php

namespace core\lib;

final class Utils
{
    private const maxSizeName = 255;
    private const maxSizeMail = 255;
    private const maxSizePassword = 255;

    private const regex = [
        "noun" => "/^[\wÀ-ÖØ-öø-ÿ]+( [\wÀ-ÖØ-öø-ÿ]+)*$/u",
        "email" => "/^[\w\-\.]+@([\w-]+\.)+[\w-]{2,4}$/",
        "password" => "/^.+$/",
        "phone" => "/^\d{10}$/",
        "monetaryBase" => "/^(-?(\d*(.\d{2})?)|(.\d{2})?)$/"
    ];

    private function __construct()
    {
    }

    static function isStringEmail(string $string, int $minLength = 10, int $maxLength = self::maxSizeMail): bool
    {
        return (($minLength <= strlen($string) && strlen($string) <= $maxLength) && preg_match(self::regex["email"], $string));
    }

    static function isStringName(string $string, int $minLength = 1, int $maxLength = self::maxSizeName): bool
    {
        return (($minLength <= strlen($string) && strlen($string) <= $maxLength) && preg_match(self::regex["noun"], $string));
    }

    static function isStringPassword(string $string, int $minLength = 4, int $maxLength = self::maxSizePassword): bool
    {
        return (($minLength <= strlen($string) && strlen($string) <= $maxLength) && preg_match(self::regex["password"], $string));
    }
    static function isStringPhone(string $string, int $minLength = 10, int $maxLength = 10): bool
    {
        return (($minLength <= strlen($string) && strlen($string) <= $maxLength) && preg_match(self::regex["phone"], $string));
    }

    static function passwordVerify(string $inputedPass, string $hashedPass): bool
    {
        return password_verify($inputedPass, $hashedPass);
    }

    static function isNumberValid($value, $min = -INF, $max = INF): bool
    {
        if (is_numeric($value)) {
            $is_int = settype($value, 'int');
            if (!$is_int) {
                settype($value, 'float');
            }
            return ($min <= $value && $value <= $max);
        } else {
            return false;
        }
    }
    static function isNumberValidInt($value, $min = -INF, $max = INF): bool
    {
        if (is_numeric($value) && settype($value, 'int')) {
            return ($min <= $value && $value <= $max);
        } else {
            return false;
        }
    }
}
