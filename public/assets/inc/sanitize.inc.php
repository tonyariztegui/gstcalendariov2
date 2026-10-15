<?php

///////////////////////////////////////
// Sanitization functions
///////////////////////////////////////
if (!defined('PARANOID')) {
    define("PARANOID", 1);
}
if (!defined('SQL')) {
    define("SQL", 2);
}
if (!defined('SYSTEM')) {
    define("SYSTEM", 4);
}
if (!defined('HTML')) {
    define("HTML", 8);
}
if (!defined('INT')) {
    define("INT", 16);
}
if (!defined('FLOAT')) {
    define("FLOAT", 32);
}
if (!defined('LDAP')) {
    define("LDAP", 64);
}
if (!defined('UTF8')) {
    define("UTF8", 128);
}

// internal function for utf8 decoding
// thanks to Jamie Pratt for noticing that PHP's function is a little 
// screwy
if (!function_exists('my_utf8_decode')) {
    function my_utf8_decode($string)
    {
      return strtr(
        $string,
        "???????",
        "SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy"
      );
    }
}

// paranoid sanitization -- only let the alphanumeric set through
if (!function_exists('sanitize_paranoid_string')) {
    function sanitize_paranoid_string($string, $min = '', $max = '')
    {
      $string = preg_replace("/[^a-zA-Z0-9]/", "", $string);
      $len = strlen($string);
      if ((($min != '') && ($len < $min)) || (($max != '') && ($len > $max))) {
        return FALSE;
      }
      return $string;
    }
}

// sanitize a string in prep for passing a single argument to system() (or similar)
if (!function_exists('sanitize_system_string')) {
    function sanitize_system_string($string, $min = '', $max = '')
    {
      $pattern = '/(;|\||`|>|<|&|^|"|' . "\n|\r|'" . '|{|}|[|]|\)|\()/i'; // no piping, passing possible environment variables ($),
      // seperate commands, nested execution, file redirection, 
      // background processing, special commands (backspace, etc.), quotes
      // newlines, or some other special characters
      $string = preg_replace($pattern, '', $string);
      $string = '"' . str_replace('$', '\\$', $string) . '"'; //make sure this is only interpretted as ONE argument
      $len = strlen($string);
      if ((($min != '') && ($len < $min)) || (($max != '') && ($len > $max))) {
        return FALSE;
      }
      return $string;
    }
}

// sanitize a string for SQL input (simple slash out quotes and slashes)
if (!function_exists('sanitize_sql_string')) {
    function sanitize_sql_string($string, $min = '', $max = '')
    {
      $pattern[0] = '/(\\\\)/';
      $pattern[1] = "/\"/";
      $pattern[2] = "/'/";
      $replacement[0] = '\\\\\\';
      $replacement[1] = '\"';
      $replacement[2] = "\\'";
        $len = strlen($string);
        if ((($min != '') && ($len < $min)) || (($max != '') && ($len > $max))) {
          return FALSE;
        }
        return preg_replace($pattern, $replacement, $string);
     
    }
}

// sanitize a string for SQL input (simple slash out quotes and slashes)
if (!function_exists('sanitize_ldap_string')) {
    function sanitize_ldap_string($string, $min = '', $max = '')
    {
      $pattern = '/(\)|\(|\||&)/';
      $len = strlen($string);
      if ((($min != '') && ($len < $min)) || (($max != '') && ($len > $max))) {
        return FALSE;
      }
      return preg_replace($pattern, '', $string);
    }
}


// sanitize a string for HTML (make sure nothing gets interpretted!)
if (!function_exists('sanitize_html_string')) {
    function sanitize_html_string($string)
    {
      $pattern[0] = '/\&/';
      $pattern[1] = '/</';
      $pattern[2] = "/>/";
      $pattern[3] = '/\n/';
      $pattern[4] = '/"/';
      $pattern[5] = "/'/";
      $pattern[6] = "/%/";
      $pattern[7] = '/\(/';
      $pattern[8] = '/\)/';
      $pattern[9] = '/\+/';
      $pattern[10] = '/-/';
      $replacement[0] = '&amp;';
      $replacement[1] = '&lt;';
      $replacement[2] = '&gt;';
      $replacement[3] = '<br />';
      $replacement[4] = '&quot;';
      $replacement[5] = '&#39;';
      $replacement[6] = '&#37;';
      $replacement[7] = '&#40;';
      $replacement[8] = '&#41;';
      $replacement[9] = '&#43;';
      $replacement[10] = '&#45;';
      return preg_replace($pattern, $replacement, $string);
    }
}

// make int int!
if (!function_exists('sanitize_int')) {
    function sanitize_int($integer, $min = '', $max = '')
    {
      $int = intval($integer);
      if ((($min != '') && ($int < $min)) || (($max != '') && ($int > $max))) {
        return FALSE;
      }
      return $int;
    }
}

// make float float!
if (!function_exists('sanitize_float')) {
    function sanitize_float($float, $min = '', $max = '')
    {
      $float = floatval($float);
      if ((($min != '') && ($float < $min)) || (($max != '') && ($float > $max))) {
        return FALSE;
      }
      return $float;
    }
}

// glue together all the other functions
if (!function_exists('sanitize')) {
    function sanitize($input, $flags, $min = '', $max = '')
    {
      if ($flags & UTF8) {
        $input = my_utf8_decode($input);
      }
      if ($flags & PARANOID) {
        $input = sanitize_paranoid_string($input, $min, $max);
      }
      if ($flags & INT) {
        $input = sanitize_int($input, $min, $max);
      }
      if ($flags & FLOAT) {
        $input = sanitize_float($input, $min, $max);
      }
      if ($flags & HTML) {
        $input = sanitize_html_string($input, $min, $max);
      }
      if ($flags & SQL) {
        $input = sanitize_sql_string($input, $min, $max);
      }
      if ($flags & LDAP) {
        $input = sanitize_ldap_string($input, $min, $max);
      }
      if ($flags & SYSTEM) {
        $input = sanitize_system_string($input, $min, $max);
      }
      return $input;
    }
}
