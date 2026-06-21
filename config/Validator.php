<?php

class Validator
{
    private static $errors = [];

    public static function reset()
    {
        self::$errors = [];
    }

    public static function getErrors()
    {
        return self::$errors;
    }

    public static function hasErrors()
    {
        return !empty(self::$errors);
    }

    public static function required($value, $fieldName)
    {
        if (empty($value)) {
            self::$errors[$fieldName] = "{$fieldName} is required";
            return false;
        }
        return true;
    }

    public static function minLength($value, $min, $fieldName)
    {
        if (empty($value)) {
            return true;
        }
        if (strlen($value) < $min) {
            self::$errors[$fieldName] = "{$fieldName} must be at least {$min} characters";
            return false;
        }
        return true;
    }

    public static function maxLength($value, $max, $fieldName)
    {
        if (empty($value)) {
            return true;
        }
        if (strlen($value) > $max) {
            self::$errors[$fieldName] = "{$fieldName} must not exceed {$max} characters";
            return false;
        }
        return true;
    }

    public static function integer($value, $fieldName = 'value')
    {
        if (empty($value)) {
            return true;
        }
        if (!is_int($value) && !ctype_digit((string)$value)) {
            self::$errors[$fieldName] = "{$fieldName} must be an integer";
            return false;
        }
        return true;
    }

    public static function date($value, $fieldName = 'date')
    {
        if (empty($value)) {
            return true;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value) {
            self::$errors[$fieldName] = "{$fieldName} must be valid date (YYYY-MM-DD)";
            return false;
        }
        return true;
    }

    public static function inArray($value, $allowedValues, $fieldName)
    {
        if (empty($value)) {
            return true;
        }
        if (!in_array($value, $allowedValues)) {
            self::$errors[$fieldName] = "{$fieldName} contains invalid value";
            return false;
        }
        return true;
    }

    public static function academicLevel($value, $fieldName = 'level')
    {
        return self::inArray($value, [1, 2, 3, 4, 5], $fieldName);
    }

    public static function attendanceStatus($value, $fieldName = 'status')
    {
        return self::inArray($value, ['present', 'absent', 'late', 'excused'], $fieldName);
    }
}
