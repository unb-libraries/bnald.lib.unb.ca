<?php

/**
 * @file
 * Contains bnald_core.legislation.chapter_sort.
 */

define('REGEX_ROMAN', '/^([MDCLXVI])M*(C[MD]|D?C{0,3})(X[CL]|L?X{0,3})(I[XV]|V?I{0,3})$/');
define('ROMAN_ARABIC_MAP', [
  "M" => 1000,
  "D" => 500,
  "C" => 100,
  "L" => 50,
  "X" => 10,
  "V" => 5,
  "I" => 1,
]);

/**
 * Turn an alphanumerical string into a naturally sortable string.
 *
 * @param string $string
 *   The string to transform.
 * @param int $length
 *   The number of digits.
 *
 * @return string
 *   String with all (arabic or roman) numeric values replaced by fixed length
 *   arabic numbers.
 */
function to_natural_sort_key($string, $length = 3) {
  $string = str_replace(' ', '-', $string);
  $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
  $chunks = explode('-', $string);
  foreach ($chunks as $key => $chunk) {
    if (is_numeric_arabic($chunk)) {
      $chunks[$key] = arabic_to_fixed_length_arabic($chunk, $length);
    }
    elseif (is_numeric_roman($chunk)) {
      $chunks[$key] = roman_to_fixed_length_arabic($chunk, $length);
    }
  }
  return implode(' ', array_map('strtolower', $chunks));
}

/**
 * Checks whether the given string represents a valid arabic number.
 *
 * @param string $string
 *   The string to check.
 *
 * @return bool
 *   True if the given string represents a valid arabic number. False otherwise.
 */
function is_numeric_arabic($string) {
  return is_numeric($string);
}

/**
 * Checks whether the given string represents a valid roman number.
 *
 * @param string $string
 *   The string to check.
 *
 * @return bool
 *   True if the given string represents a valid roman number. False otherwise.
 */
function is_numeric_roman($string) {
  return preg_match(REGEX_ROMAN, strtoupper($string));
}

/**
 * Convert given arabic number into desired fixed length arabic number.
 *
 * @param int $arabic
 *   The arabic number to convert.
 * @param int $length
 *   The desired length of the number, after the conversion.
 *
 * @return string
 *   An arabic number with leading zeros.
 */
function arabic_to_fixed_length_arabic($arabic, $length) {
  return str_pad($arabic, $length, '0', STR_PAD_LEFT);
}

/**
 * Convert given roman number into desired fixed length arabic number.
 *
 * @param string $roman
 *   String representing a valid roman numeric value.
 * @param int $length
 *   The desired length of the number, after the conversion.
 *
 * @return string
 *   An arabic number with leading zeros.
 */
function roman_to_fixed_length_arabic($roman, $length) {
  $arabic = roman_to_arabic($roman);
  return str_pad(strval($arabic), $length, '0', STR_PAD_LEFT);
}

/**
 * Convert a string representing a roman numeric value into an arabic number.
 *
 * @param string $roman
 *   A string representing a valid roman numeric value.
 *
 * @return int
 *   The numeric arabic value equivalent to the roman string representation.
 */
function roman_to_arabic($roman) {
  $roman = str_split(strtoupper($roman));
  $arabic = 0;
  foreach ($roman as $index => $char) {
    $arabic += ROMAN_ARABIC_MAP[$char];
    if ($index > 0 && ROMAN_ARABIC_MAP[$roman[$index - 1]] < ROMAN_ARABIC_MAP[$roman[$index]]) {
      $arabic -= ROMAN_ARABIC_MAP[$roman[$index - 1]] * 2;
    }
  }
  return $arabic;
}
