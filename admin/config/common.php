<?php

/* * ******************************************
  File Name    : Common.php
  Description    : File for common functions
  Created By    : Jabahar Mohapatra
  Created On    : 25-Dec-2017
 * ****************************************** */

/**
 * Tests for file writability
 *
 *
 * @link    https://bugs.php.net/bug.php?id=54709
 * @param    string
 * @return    bool
 */
// https://github.com/andegna/calender#basic-usage-hammer
// http://keith-wood.name/calendars.html



use Illuminate\Support\Facades\Route;



if (!isset($_SESSION['BE']['LN'])) {
    $_SESSION['BE']['LN'] = 'en';
}

if (isset($_POST['en-lang'])) {
    $_SESSION['BE']['LN'] = 'en';
}

if (isset($_POST['am-lang'])) {
    $_SESSION['BE']['LN'] = 'am';
}

if (!function_exists('is_really_writable')) {

    /**
     * Tests for file writability
     *
     * is_writable() returns TRUE on Windows servers when you really can't write to
     * the file, based on the read-only attribute. is_writable() is also unreliable
     * on Unix servers if safe_mode is on.
     *
     * @link    https://bugs.php.net/bug.php?id=54709
     * @param    string
     * @return    bool
     */
    function is_really_writable($file) {
        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR === '/' && (is_php('5.4') or ! ini_get('safe_mode'))) {
            return is_writable($file);
        }

        /* For Windows servers and safe_mode "on" installations we'll actually
         * write a file then read it. Bah...
         */
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }

            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($file) or ( $fp = @fopen($file, 'ab')) === false) {
            return false;
        }

        fclose($fp);
        return true;
    }

}

if (!function_exists('is_php')) {

    /**
     * Determines if the current version of PHP is equal to or greater than the supplied value
     *
     * @param    string
     * @return    bool    TRUE if the current version is $version or higher
     */
    function is_php($version) {
        static $_is_php;
        $version = (string) $version;

        if (!isset($_is_php[$version])) {
            $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
        }

        return $_is_php[$version];
    }

}

if (!function_exists('remove_invisible_characters')) {

    /**
     * Remove Invisible Characters
     *
     * This prevents sandwiching null characters
     * between ascii characters, like Java\0script.
     *
     * @param    string
     * @param    bool
     * @return   string
     */
    function remove_invisible_characters($str, $url_encoded = true) {
        $non_displayables = array();

        // every control character except newline (dec 10),
        // carriage return (dec 13) and horizontal tab (dec 09)
        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcef]/i'; // url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/i'; // url encoded 16-31
            $non_displayables[] = '/%7f/i'; // url encoded 127
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127

        do {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        } while ($count);

        return $str;
    }

}

// ------------------------------------------------------------------------

if (!function_exists('html_escape')) {

    /**
     * Returns HTML escaped variable.
     *
     * @param    mixed    $var        The input string or array of strings to be escaped.
     * @param    bool    $double_encode    $double_encode set to FALSE prevents escaping twice.
     * @return    mixed            The escaped string or array of strings as a result.
     */
    function html_escape($var, $double_encode = true) {
        if (empty($var)) {
            return $var;
        }

        if (is_array($var)) {
            foreach (array_keys($var) as $key) {
                $var[$key] = html_escape($var[$key], $double_encode);
            }

            return $var;
        }

        return htmlspecialchars($var, ENT_QUOTES, config_item('charset'), $double_encode);
    }

}

// ------------------------------------------------------------------------

if (!function_exists('_stringify_attributes')) {

    /**
     * Stringify attributes for use in HTML tags.
     *
     * Helper function used to convert a string, array, or object
     * of attributes to a string.
     *
     * @param    mixed    string, array, object
     * @param    bool
     * @return    string
     */
    function _stringify_attributes($attributes, $js = false) {
        $atts = null;

        if (empty($attributes)) {
            return $atts;
        }

        if (is_string($attributes)) {
            return ' ' . $attributes;
        }

        $attributes = (array) $attributes;

        foreach ($attributes as $key => $val) {
            $atts .= ($js) ? $key . '=' . $val . ',' : ' ' . $key . '="' . $val . '"';
        }

        return rtrim($atts, ',');
    }

}

// ------------------------------------------------------------------------

if (!function_exists('function_usable')) {

    /**
     * Function usable
     *
     * Executes a function_exists() check, and if the Suhosin PHP
     * extension is loaded - checks whether the function that is
     * checked might be disabled in there as well.
     *
     * This is useful as function_exists() will return FALSE for
     * functions disabled via the *disable_functions* php.ini
     * setting, but not for *suhosin.executor.func.blacklist* and
     * *suhosin.executor.disable_eval*. These settings will just
     * terminate script execution if a disabled function is executed.
     *
     * The above described behavior turned out to be a bug in Suhosin,
     * but even though a fix was committed for 0.9.34 on 2012-02-12,
     * that version is yet to be released. This function will therefore
     * be just temporary, but would probably be kept for a few years.
     *
     * @link    http://www.hardened-php.net/suhosin/
     * @param    string    $function_name    Function to check for
     * @return    bool    TRUE if the function exists and is safe to call,
     *            FALSE otherwise.
     */
    function function_usable($function_name) {
        static $_suhosin_func_blacklist;

        if (function_exists($function_name)) {
            if (!isset($_suhosin_func_blacklist)) {
                $_suhosin_func_blacklist = extension_loaded('suhosin') ? explode(',', trim(ini_get('suhosin.executor.func.blacklist'))) : array();
            }

            return !in_array($function_name, $_suhosin_func_blacklist, true);
        }

        return false;
    }

}

if (!function_exists('log_message')) {

    /**
     * Error Logging Interface
     *
     * We use this as a simple mechanism to access the logging
     * class and send messages to be logged.
     *
     * @param    string    the error level: 'error', 'debug' or 'info'
     * @param    string    the error message
     * @return    void
     */
    function log_message($level, $message) {
        static $_log;

        if ($_log === null) {
            // references cannot be directly assigned to static variables, so we use an array
            $_log[0] = &load_class('Log', 'core');
        }

        $_log[0]->write_log($level, $message);
    }

}

//Function to return posted form field values from the Laravel request object
function post_vals() {

    $post_vals = [];

    foreach ($_POST as $field_name => $val) {

        $field_val = request($field_name);
        $post_vals[$field_name] = $field_val; //is_array($field_val)?$field_val:$field_val;
    }

    return $post_vals;
}

function renderSecTabs($no = 1) {

    echo '<ul class="nav nav-tabs">';

    $strPlSlug = $GLOBALS['strPlSlug'];
    $strGlSlug = $GLOBALS['strSlSlug'];

    $app = $this->app;

    $arrSls = getSls($strPlSlug);

    foreach ($arrSls as $arrSl) {

        $key = $arrSl['slug'];
        $name = $arrSl['name'];

        $strTabUrl = $key == 'index' ? ADMIN_URL . $app->currentController : ADMIN_URL . $app->currentController . '/' . $key;
        $strActiveClass = $app->currentAction == str_replace('-', '', $key) ? 'active' : '';

        echo '<li class="' . $strActiveClass . '"> <a href="' . $strTabUrl . '"> ' . $name . ' </a> </li>';
    }

    echo ' </ul>';

    //vd($arrSls);
}

function arrval($arr, $key, $dflt = '') {

    return isset($arr[$key]) ? $arr[$key] : $dflt;
}

function messages() {

    $errors = arrval($GLOBALS, 'validation_errors', []);
    $db_success = arrval($GLOBALS, 'db_success', []);
    $db_error = arrval($GLOBALS, 'db_error', []);

    if (!empty($errors)) {

        echo '<div class="col-lg-12">
       <div class="alert alert-danger">
           <ul style="list-style:none;padding:0;">';
        foreach ($errors as $error) {

            echo "<li><i class='fa fa-times'></i> $error </li>";
        }
        echo '</ul>
       </div>
   </div>';
    }

    if (!empty($db_success)) {

        echo '<div class="col-lg-12">
       <div class="alert alert-success">
           <ul style="list-style:none;padding:0;">';
        foreach ($db_success as $msg) {

            echo "<li><i class='fa fa-check'></i> $msg </li>";
        }
        echo '</ul>
       </div>
   </div>';
    }

    if (!empty($db_error)) {

        echo '<div class="col-lg-12">
        <div class="alert alert-danger">
            <ul style="list-style:none;padding:0;">';
        foreach ($db_error as $msg) {

            echo "<li><i class='fa fa-times'></i> $msg </li>";
        }
        echo '</ul>
        </div>
    </div>';
    }

    if (Session::has('message')) {

        echo '<div class="col-lg-12"><div class="alert alert-success">
                <ul style="list-style:none;padding:0;"><li><i class="fa fa-check"></i>' . Session::get('message') . '</li></ul>
            </div></div>';
    }
    
    if (Session::has('errmessage')) {

        echo '<div class="col-lg-12"><div class="alert alert-danger">
                <ul style="list-style:none;padding:0;"><li><i class="fa fa-times"></i>' . Session::get('errmessage') . '</li></ul>
            </div></div>';
    }
}

function ShowPaging($intTotalRec, $intCurrPage, $intPgSize, $isPaging = 1,$pagingFunction = 'DoPaging') {
    if ($intTotalRec == 0) {
        return false;
    }

    $intPagecount = ceil($intTotalRec / $intPgSize); // Total no of pages

    if ($intCurrPage > $intPagecount) {
        $intCurrPage = $intPagecount;
    }

    $intMaxPage = $intCurrPage + 10;
    $intPrevPgno = $intCurrPage - 1;
    $intRecPrev = ($intCurrPage - 2) * $intPgSize;
    $intNextPgno = $intCurrPage + 1;
    $intRecNext = $intCurrPage * $intPgSize;

    $strPages = '';

    // set max page number to show ===============
    if ($intMaxPage > $intPagecount) {
        $intMaxPage = $intPagecount;
    }

    // First Page Link ====================================
    if ($intCurrPage > 1) {
        $strPages .= "<li class='page-item prev'><a class='page-link' onclick='".$pagingFunction."(1,0)' href='#' title='First'><i class='fa fa-angle-double-left'></i></a></li>";
    }

    // set previous page link ========================
    if ($intPrevPgno > 0) {
        $strPages .= "<li class='page-item prev'><a class='page-link' onclick='".$pagingFunction."(" . $intPrevPgno . "," . $intRecPrev . ")' href='#' title='Previous'><i class='fa fa-angle-left'></i></a></li>";
    }

    // Create page number links =======================
    $intStartPg = 1;
    $intEndPg = 10;
    if ($intCurrPage <= 10) {
        $intStartPg = 1;
        $intEndPg = 10;
    } else {
        //$intStartPg=floor($intCurrPage/10)*$intPgSize;echo $intStartPg;
        //$intEndPg=ceil($intCurrPage/10)*$intPgSize;
        $intStartPg = $intCurrPage - 4;
        $intEndPg = $intCurrPage + 5;
    }
    if ($intEndPg > $intPagecount) {
        $intEndPg = $intPagecount;
    }

    for ($intCtr = $intStartPg; $intCtr <= $intEndPg; $intCtr++) {

        if ($intCtr >= 1) {
            $intRec = $intPgSize * ($intCtr - 1);
        }
        if ($intCurrPage == $intCtr) {
            $strPages .= "<li class='page-item active'><a class='page-link' href='javascript:void(0)'>" . $intCtr . "</a></li>";
        } else {
            $strPages .= "<li class='page-item '><a class='page-link' onclick='".$pagingFunction."(" . $intCtr . "," . $intRec . ")' href='#' title='" . $intCtr . "'>" . $intCtr . "</a></li>";
        }
    }
    // set next page link ========================
    if ($intCurrPage < $intPagecount) {
        $strPages .= "<li class='page-item next'><a class='page-link' onclick='".$pagingFunction."(" . $intNextPgno . "," . $intRecNext . ")' href='#' title='Next'><i class='fa fa-angle-right'></i></a></li>";
    }

    // Last Page Link ====================================
    $intLastPageRec = ($intPagecount - 1) * $intPgSize;
    if ($intCurrPage < $intPagecount) {
        $strPages .= "<li class='page-item '><a class='page-link' onclick='".$pagingFunction."(" . $intPagecount . "," . $intLastPageRec . ")' href='#' title='Last'>&raquo;</a></li>";
    }

    //================================================
    $intStartRec = ($intCurrPage - 1) * $intPgSize + 1;
    $intEndRec = $intRecNext;
    if ($intEndRec > $intTotalRec) {
        $intEndRec = $intTotalRec;
    }

    $strShowing = ($isPaging == 1) ? "Showing " . $intStartRec . "&nbsp;to&nbsp;" . $intEndRec . " of " . $intTotalRec . " records" : "Showing " . $intStartRec . "&nbsp;to&nbsp;" . $intTotalRec . " of " . $intTotalRec . " records";


    if ($isPaging == 1) {
        $strShowAll = ' / <a href="#" onClick="AlternatePaging();">Show All</a>';
    } else {
        $strShowAll = ' / <a href="#" onClick="AlternatePaging();">Show Paginated</a>';
    }

    if ($intPagecount > 1 && $pagingFunction == 'DoPaging') {
        $ArrPaging[0] = $strShowing . $strShowAll;
    } else {
        $ArrPaging[0] = $strShowing;
    }

    $ArrPaging[1] = ($intPagecount > 1 && $isPaging) ? $strPages : '';
    return $ArrPaging;
}

function ShowPagingWeb($intTotalRec, $intCurrPage, $intPgSize, $isPaging = 1,$pagingFunction = 'DoPaging',$showNumbers = 0) {
    if ($intTotalRec == 0) {
        return false;
    }

    $intPagecount = ceil($intTotalRec / $intPgSize); // Total no of pages

    if ($intCurrPage > $intPagecount) {
        $intCurrPage = $intPagecount;
    }

    $intMaxPage = $intCurrPage + 10;
    $intPrevPgno = $intCurrPage - 1;
    $intRecPrev = ($intCurrPage - 2) * $intPgSize;
    $intNextPgno = $intCurrPage + 1;
    $intRecNext = $intCurrPage * $intPgSize;

    $strPages = '';

    // set max page number to show ===============
    if ($intMaxPage > $intPagecount) {
        $intMaxPage = $intPagecount;
    }

    // First Page Link ====================================
    if ($intCurrPage > 1) {
        $strPages .= "<li class='prev page-item'><a class='page-link' onclick='".$pagingFunction."(1,0)' href='javascript:void(0)' title='First'><i class='icon-chevrons-left1'></i></a></li>";
    }

    // set previous page link ========================
    if ($intPrevPgno > 0) {
        $strPages .= "<li class='prev page-item'><a class='page-link' onclick='".$pagingFunction."(" . $intPrevPgno . "," . $intRecPrev . ")' href='javascript:void(0)' title='Previous'><i class='icon-chevron-left1'></i></a></li>";
    }

    // Create page number links =======================
    $intStartPg = 1;
    $intEndPg = 10;
    if ($intCurrPage <= 10) {
        $intStartPg = 1;
        $intEndPg = 10;
    } else {
        //$intStartPg=floor($intCurrPage/10)*$intPgSize;echo $intStartPg;
        //$intEndPg=ceil($intCurrPage/10)*$intPgSize;
        $intStartPg = $intCurrPage - 4;
        $intEndPg = $intCurrPage + 5;
    }
    if ($intEndPg > $intPagecount) {
        $intEndPg = $intPagecount;
    }

    for ($intCtr = $intStartPg; $intCtr <= $intEndPg; $intCtr++) {

        if ($intCtr >= 1) {
            $intRec = $intPgSize * ($intCtr - 1);
        }
        if ($intCurrPage == $intCtr) {
            $strPages .= "<li class='active page-item'><a class='page-link' href='javascript:void(0)'>" . $intCtr . "</a></li>";
        } else {
            $strPages .= "<li class='page-item'><a class='page-link' onclick='".$pagingFunction."(" . $intCtr . "," . $intRec . ")' href='javascript:void(0)' title='" . $intCtr . "'>" . $intCtr . "</a></li>";
        }
    }
    // set next page link ========================
    if ($intCurrPage < $intPagecount) {
        $strPages .= "<li class='next page-item'><a class='page-link' onclick='".$pagingFunction."(" . $intNextPgno . "," . $intRecNext . ")' href='javascript:void(0)' title='Next'><i class='icon-chevron-right1'></i></a></li>";
    }

    // Last Page Link ====================================
    $intLastPageRec = ($intPagecount - 1) * $intPgSize;
    if ($intCurrPage < $intPagecount) {
        $strPages .= "<li class='page-item'><a class='page-link' onclick='".$pagingFunction."(" . $intPagecount . "," . $intLastPageRec . ")' href='javascript:void(0)' title='Last'><i class='icon-chevrons-right1'></i></a></li>";
    }

    //================================================
    $intStartRec = ($intCurrPage - 1) * $intPgSize + 1;
    $intEndRec = $intRecNext;
    if ($intEndRec > $intTotalRec) {
        $intEndRec = $intTotalRec;
    }
    $strShowing = '';
    
    if($showNumbers == 1){
        $strShowing = ($isPaging == 1) ? "Showing " . $intStartRec . "&nbsp;to&nbsp;" . $intEndRec . " of " . $intTotalRec . " records" : "Showing " . $intStartRec . "&nbsp;to&nbsp;" . $intTotalRec . " of " . $intTotalRec . " records";
    }

    if ($isPaging == 1) {
        $strShowAll = ' <li class="page-item"><a class="page-link" href="javascript:void(0)" onClick="AlternatePagingWeb();">All</a></li>';
    } else {
        $strShowAll = ' <li class="page-item"><a class="page-link" href="javascript:void(0)" onClick="AlternatePagingWeb(1);">Show Pages</a></li>';
    }

    if ($intPagecount > 1) {
        $ArrPaging[0] = $strShowing . $strShowAll;
    } else {
        $ArrPaging[0] = $strShowing;
    }
    
    $strPages = $strShowAll.$strPages;

    $ArrPaging[1] = ($intPagecount > 1 && $isPaging) ? $strPages : (($intTotalRec > $intPgSize) ? $strShowAll : '');
    return $ArrPaging;
}


// Language changing funtion for Admin interface 
if (!function_exists('eh')) {

    function eh($string, $echo = true, $ln = 'en') {

        $l10n_arr = array(
            'am' => array(
                'Dashboard' => 'ዳሽቦርድ',
                'Approval Config.' => 'የማ�?�ደቅ �?ቅር',
                'Manage Master' => 'ማስተዳደር',
                'CMS' => 'ሲኤ�?ኤስ',
                'Manage Pages' => 'ገጾችን ያቀናብሩ',
                'Lease Management' => 'የመሬት አስተዳደር',
                'Construction Management' => 'የ�?ንባታ አስተዳደር',
                'Maintenance Management' => 'የጥገና አስተዳደር',
                'Rental Management' => 'የኪራይ አስተዳደር',
                'Grievance Redressal' => 'ቅሬታ ቀ�?ስ',
                'Settings' => 'ቅንብሮች',
                'Commercial' => 'ን�?ድ',
                'English' => 'እን�?ሊ�?ኛ',
                'Amharic' => 'አማርኛ',
                'View' => 'ይመ�?ከቱ',
                'Add' => 'አክ�?',
                'Select' => 'ይ�?ረጡ',
                //grievance 
                //category
                'Name' => 'ስ�?',
                'Description' => 'መ�?ለጫ',
                'Maximum' => 'ከ�??ተኛ',
                'Language' => 'ቋንቋ',
                'characters Remaining' => '�?�?�?�ዎች ይቀራሉ',
                //Subcategory
                'Category' => '�?ድብ',
                'Complaints' => 'ቅሬታዎች',
            ),
        );

        if (isset($_SESSION['BE']['LN'])) {
            $ln = $_SESSION['BE']['LN'];

            if ($ln == 'am' && isset($l10n_arr[$ln][$string])) {
                $string = $l10n_arr[$ln][$string];
            }
        }
        if ($echo) {
            echo $string;
        } else {
            return $string;
        }
    }

}

//Function for encrypting params to be sent in the URL
function encparam($str) {
    return str_replace('=', '', encrypt($str));
}

// Language changing funtion for Website user interface 
if (!function_exists('ehf')) {

    function ehf($string, $echo = true, $ln = 'en') {
        $string = strtolower($string);
        $content = file_get_contents(url('/') . '/language.json');
        $arr_data = json_decode($content, true);
        $l10n_arr = array(
                    'AM' => $arr_data,
                // array(
                //     'Construction Applications' => 'የ�?ንባታ ማመ�?ከቻዎች',
                //     'Regulations'               => 'ደንቦች',
                //     'Regulation Details'        => 'የመቆጣጠሪያ �?ር�?ሮች',
                //     'Form'                      => 'ቅጽ',
                //     'Form Details'              => 'የቅጽ �?ር�?ሮች',
                //     'Events'                    => 'ክስተቶች',
                //     'Event Details'             => 'የክስተት �?ር�?ሮች',
                //     'Calendars'                 => 'የቀን መ�?ጠሪያዎች',
                //     'Blog'                      => 'ጦማር',
                //     'Blog Details'              => 'የብሎ�? �?ር�?ሮች',
                //     'Calendar'                  => 'የቀን መ�?ጠሪያ',
                //     'Enquiry'                   => 'ጥያቄ',
                //     'FAQ'                       => 'በየጥ',
                //     'Answer'                    => 'መ�?ስ ይስጡ',
                //     'Forum'                     => 'መድረክ',
                //     'Gallery'                   => 'ማዕከለ ስዕላት',
                //     'Audio Gallery'             =>'ኦዲዮ ጋለሪ',
                //     'Video Gallery'             => 'የቪዲዮ ማዕከ�?',
                //     'Image'                     =>'�?ስ�?',
                //     'Video'                     => 'ቪድዮ',
                //     'Audio'                     => 'ኦዲዮ',
                //     'Forum Topics'              => '�?�ረሞች ርእሶች',
                //     'Forum topic Details'       => 'የ�?ይይቱ ርእስ �?ር�?ሮች',
                //     'About'                     => 'ስለ',
                //     'Apply for Rental'          =>'ለኪራይ ማመ�?ከት',
                //     'Select'                    =>'ይ�?ረጡ',
                //     'Select Item'               =>'ንጥ�? ይ�?ረጡ',
                //     'Rent Applications'         =>'የኪራይ ማመ�?ከቻዎች',
                //     'English'                   => 'እን�?ሊ�?ኛ',
                //     'Amharic'                   => 'አማርኛ',
                //     'Welcome'                   => 'እንኳን ደህና መጣህ',
                //     'Logout'                    => '�?ጣ',
                //     // User DashBoard binding Added by : Ashok kumar samal :: ON: 18-06-2018
                //     'My'                        => 'የእኔ',
                //     'Complaints'                => 'ቅሬታዎች',  
                //     'Applied'                   => 'ተተ�?ብሯ�?',
                //     'Resolved'                  => 'ተ�?�ቷ�?',                          
                //     'Pending'                   => 'ተተ�?ብሯ�?',
                //     'Status'                    => '�?ኔታ',
                //     'Raised'                    => 'ተ�?ስቷ�?',
                //     'Rental'                    => 'ኪራይ',
                //     'Construction'              => '�?ንባታ',
                //     'On'                        => 'በርቷ�?',
                //     'View'                      => 'ይመ�?ከቱ',
                //     'All'                       => '�?ሉ�?',
                //     'View All'                  => '�?ሉን�? ይመ�?ከቱ',
                //     'My Enquiries'              => 'የኔ ጥያቄዎች',
                //     'My Bookings'               => 'የእኔ መጽ�?�??ት',
                //     'Bill Payments'             => 'ቢ�? ክ�??ያ',
                // ),
        );
        $selLanguage = session('language');

        if ($selLanguage == 'AM' && isset($l10n_arr[$selLanguage][$string])) {
            $string = $l10n_arr[$selLanguage][$string];
        } else {
            $string = ucwords($string);
        }

        if ($echo) {
            echo $string;
        } else {
            return $string;
        }
    }

}



$dl = array('sqls' => array(), 'vds' => array(), 'perf' => array());

function dbgl($typ, $msg) {
    if (debug_enabled()) {
        $GLOBALS['dl'][$typ][] = $msg;
    }
}

function debug_enabled() {
    return (ENVIRONMENT == 'dev' || ENVIRONMENT == 'local' || FORCED_DEBUG);
}



/* * ****Start block to get Menu details ****** */

// function main_menu() {

//     $objMenu = new MenuModel();
//     $viewVarsArr = array();
//     $arrConditions = array('and' => ['cnds' => ['tinMenuType' => ['eq' => 1]]], ['cnds' => ['intParentId' => ['eq' => 0]]]);
//     $viewVarsArr['globalResult'] = $objMenu->getWhereAdv($arrConditions, true, 0, 0, 'VP');
//     $primaryResult = array();
//     // print_r($viewVarsArr['globalResult']['result']);exit;
//     foreach ($viewVarsArr['globalResult']['result'] as $rowGlobal) {
//         $pageGLId = $rowGlobal['intPageId'];
//         $arrSubConditions = array('and' => ['cnds' => ['tinMenuType' => ['eq' => 1]]], ['cnds' => ['intParentId' => ['eq' => $pageGLId]]]);
//         array_push($primaryResult, $objMenu->getWhereAdv($arrSubConditions, true, 0, 0, 'VP'));
//     }
//     $viewVarsArr['primaryResult'] = $primaryResult;
//     //dd( $viewVarsArr);
//     return $viewVarsArr;
// }

// function top_menu() {

//     $objMenu = new MenuModel();
//     $topMenuArr = array();
//     $arrConditions = array('and' => ['cnds' => ['tinMenuType' => ['eq' => 5]]], ['cnds' => ['intParentId' => ['eq' => 0]]]);
//     $topMenuArr['topMenuResult'] = $objMenu->getWhereAdv($arrConditions, true, 0, 0, 'VP');

//     return $topMenuArr;
// }

// function bottom_menu() {

//     $objMenu = new MenuModel();
//     $topMenuArr = array();
//     $arrConditions = array('and' => ['cnds' => ['tinMenuType' => ['eq' => 3]]], ['cnds' => ['intParentId' => ['eq' => 0]]]);
//     $topMenuArr['topMenuResult'] = $objMenu->getWhereAdv($arrConditions, true, 0, 0, 'VP');

//     return $topMenuArr;
// }

/* * ******End Block********** */

function marray_search($array, $key, $value) {
    //marray_search($GLOBALS['arrLinks'], 'slug', $controller);

    $results = array();

    if (is_array($array)) {
        //$array[$key].'=='.$value;exit;
        if (isset($array[$key]) && $array[$key] == $value) {
            $results[] = $array;
        }

        foreach ($array as $subarray) {
            $results = array_merge($results, marray_search($subarray, $key, $value));
        }
        //echo "<pre>";print_r($results);exit;
    }

    return $results;
}

function link_frm_slug($admin_nav_links, $controller_slug) {

    $link = []; // = 0;

    foreach ($admin_nav_links as $links) {

        if ($links['slug'] == $controller_slug && $links['type'] == 'PL') {
            // $gl_id = $links['id'];
            $link = $links;
            break;
        }
    }

    return $link;
}

function get_tabs($parent_id, $admin_nav_links) {

    $tabs = [];

    foreach ($admin_nav_links as $link) {

        if ($link['pid'] == $parent_id && $link['type'] == 'TB') {
            $tabs[] = $link;
        }
    }

    usort($tabs, function ($a, $b) {
        return $a['ord'] <=> $b['ord'];
    });

    return $tabs;
}

// function array_comp($a, $b, $key='ord') {
//     if ($a[ $key] == $b[ $key]) return 0;
//     return ($a[ $key] < $b[ $key]) ? -1 : 1;
// }

function user_has_access($controller, $action) {

    $allowed_list = ['dashboard'];
    //print_r($perms_array);exit;
    if (in_array($controller, $allowed_list)) {
        return true;
    }

    $right_name = $controller . '_' . $action . '_right';
//    echo $right_name;exit; //serviceCategory_index_right
    $perms_array = session('admin_session.userPermission');
    $permittedLinks_array = session('admin_session.permittedLinks');
//echo "<pre>";print_r($perms_array['Gallerycategory_index_right']);exit;
    if (!isset($perms_array[$right_name])) {
        return true;
    }

    return ($perms_array[$right_name] > 0) ? true : false;
}

function user_has_right($right_name, $right = 'access') {

    $perms_array = session('admin_session.userPermission');

    if (!isset($perms_array[$right_name])) {
        return true;
    }

    if ($right == 'access') {
        return ($perms_array[$right_name] > 0) ? true : false;
    }
}

function route_access_allowed($controller, $action) {
    //echo $controller, $action;exit;
    $allowed_list = ['dashboard','changepassword'];
    if (in_array($controller, $allowed_list)) {
        return true;
    }
    $perms_array = session('admin_session.userPermission');
    $adminPrivilage =  session('admin_session.admin_privilege');
    $childLinks = \array_column($perms_array, 'childLinks');
    $matchKey = 0;
    $rights   = 0;
    foreach ($childLinks as $value) {
       
        foreach ($value as $values) {
             //echo "<pre>";print_r($values);echo "===".$controller;
            //$values['vchSlugName']."===";
            if ($values['vchSlugName'] == $controller) {
                $matchKey++;
                $rights = $values['intRights'];
            }

            if ($matchKey > 0)
                break;
        }
    }
    $access = 0;
    //echo session('admin_session.admin_privilege');exit;
    if($adminPrivilage==1) {
        $access =1;
    }else {
        if($rights==4) { // for All Admin (Manage) rights
            $access =1;
        }
        else if($action=='add')
        {
            if($rights==2 || $rights==3 || $rights==4)
            {
                $access =1;
            }else {
                $access =0;
            }

        }
        else if($action=='index')
        {
            if($rights==1 || $rights==2 || $rights==3 || $rights==4)
            {
                $access =1;
            }else {
                $access =0;
            }

        }
        else if($action=='delete')
        {
            if($rights==4)
            {
                $access =1;
            }else {
                $access =0;
            }

        }
        else if($action=='edit')
        {
            if($rights==3 || $rights==4)
            {
                $access =1;
            }else {
                $access =0;
            }

        }
    }
   
    if ($matchKey > 0 && $access==1) {
        return true;
    } else {
        return false;
    }

    exit;



    //echo "<pre>";print_r($childLinks);exit;
//    $arrControllerLink = marray_search($GLOBALS['arrLinks'], 'slug', $controller)[0];
//    //echo $arrControllerLink;exit;
//    $parent_link_id = $arrControllerLink['pid'];
//    $parent_slug    = $GLOBALS['arrLinkData'][$parent_link_id]['slug'];
//
//    $right_name = $parent_slug . '_' . $arrControllerLink['slug'] . '_right';
//
//    if (!user_has_right($right_name)) {
//
//        return false;
//
//    } elseif (!user_has_access($controller, $action)) {
//
//        return false;
//
//    } else {
//
//        return true;
//    }
}

function renderTabs($arrTabTxtRplc = []) {

    $routes = Request::path();

    global $controller_prefixes;

    $link_parts = explode('/', $routes);

    if (in_array($link_parts[0], $controller_prefixes)) {
        $controller = $link_parts[1];
        $action = isset($link_parts[2]) ? $link_parts[2] : 'index';
    } else {
        $controller = $link_parts[0];
        $action = isset($link_parts[1]) ? $link_parts[1] : 'index';
    }

    $admin_nav_links = get_admin_nav_links();

    $pl = link_frm_slug($admin_nav_links, $controller);
    $pl_id = $pl['id'];

    $tabs = get_tabs($pl_id, $admin_nav_links);


    echo '<ul class="nav nav-tabs">';

    foreach ($tabs as $tab) {

        $right = $controller . '_' . $tab['slug'] . '_right';

        if (!user_has_access($controller, $tab['slug'])) {
            continue;
        }

        $active = '';
        if ($tab['slug'] == $action) {
            $active = 'active';
        }



        $action_slug = ($tab['slug'] == 'index') ? '' : '/' . $tab['slug'];

        $tab_url = ADMIN_URL . $controller . $action_slug;

        $strTabName = $tab['name'];
        if (!empty($arrTabTxtRplc) && isset($arrTabTxtRplc[$tab['name']])) {

            $strTabName = $arrTabTxtRplc[$tab['name']];
        }

        if ($tab['slug'] == 'add' && $action == 'edit') {
            $active = 'active';
            $tab_url = 'javascript:void(0)';
        }

        echo '<li class="' . $active . '"> <a href="' . $tab_url . '"> ' . eh($strTabName, false) . ' </a> </li>';
    }

    echo '</ul>';
}

function getSls($strPlSlug) {

    $arrLinks = get_admin_nav_links();

    $arrSls = [];

    foreach ($arrLinks as $arrGls) {
        $arrPls = $arrGls['pls'];

        foreach ($arrPls as $arrPl) {

            if ($arrPl['slug'] == $strPlSlug) {

                if (!empty($arrPl['sls'])) {
                    $arrSls = $arrPl['sls'];
                    break;
                }
            }
        }
    }

    return $arrSls;
}

function getGlRightName($strPlSlug) {

    $arrLinks = get_admin_nav_links();

    $strGlrightName = '';

    foreach ($arrLinks as $arrGls) {
        $arrPls = $arrGls['pls'];

        foreach ($arrPls as $arrPl) {

            if ($arrPl['slug'] == $strPlSlug) {

                //if(!empty($arrPl['sls'])){
                $strGlrightName = $arrGls['slug'] . '_right';
                break 2;
                //}
            }
        }
    }

    return $strGlrightName;
}

if (!function_exists('vd')) {

    function vd($args) {

        if (debug_enabled()) {
            echo '<pre  style="background: #000;    color: #52f952;    font-weight: bold;    border: 0;">';
            foreach (func_get_args() as $var) {
                var_dump($var);
            }

            echo '</pre>';

            $bt = debug_backtrace();
            $caller = array_shift($bt);

            dbgl('vds', 'vd called from: ' . $caller['file'] . ' at ' . $caller['line']);
        }
    }

}

function vdbgl($var) {
    dbgl('vds', '<pre>' . var_export($var, true) . '</pre>');

    $bt = debug_backtrace();
    $caller = array_shift($bt);

    dbgl('vds', 'vdbgl called from: ' . $caller['file'] . ' at ' . $caller['line']);
}

function pageTabs($app) {
    echo '<ul class="nav nav-tabs">';

    $pageTabs = $app->url_controller->tabs;

    foreach ($pageTabs as $key => $name) {

        $strTabUrl = $key == 'index' ? ADMIN_URL . $app->currentController : ADMIN_URL . $app->currentController . '/' . $key;
        $strActiveClass = $app->currentAction == str_replace('-', '', $key) ? 'active' : '';

        echo '<li class="' . $strActiveClass . '"> <a href="' . $strTabUrl . '"> ' . $name . ' </a> </li>';
    }

    echo ' </ul>';
}

function getAllCountries(){
    $countries = [
        "Afghanistan",
        "Albania",
        "Algeria",
        "American Samoa",
        "Andorra",
        "Angola",
        "Anguilla",
        "Antarctica",
        "Antigua and Barbuda",
        "Argentina",
        "Armenia",
        "Aruba",
        "Australia",
        "Austria",
        "Azerbaijan",
        "Bahamas",
        "Bahrain",
        "Bangladesh",
        "Barbados",
        "Belarus",
        "Belgium",
        "Belize",
        "Benin",
        "Bermuda",
        "Bhutan",
        "Bolivia",
        "Bosnia and Herzegovina",
        "Botswana",
        "Bouvet Island",
        "Brazil",
        "British Indian Ocean Territory",
        "Brunei Darussalam",
        "Bulgaria",
        "Burkina Faso",
        "Burundi",
        "Cambodia",
        "Cameroon",
        "Canada",
        "Cape Verde",
        "Cayman Islands",
        "Central African Republic",
        "Chad",
        "Chile",
        "China",
        "Christmas Island",
        "Cocos (Keeling) Islands",
        "Colombia",
        "Comoros",
        "Congo",
        "Congo, the Democratic Republic of the",
        "Cook Islands",
        "Costa Rica",
        "Cote D'Ivoire",
        "Croatia",
        "Cuba",
        "Cyprus",
        "Czech Republic",
        "Denmark",
        "Djibouti",
        "Dominica",
        "Dominican Republic",
        "Ecuador",
        "Egypt",
        "El Salvador",
        "Equatorial Guinea",
        "Eritrea",
        "Estonia",
        "Ethiopia",
        "Falkland Islands (Malvinas)",
        "Faroe Islands",
        "Fiji",
        "Finland",
        "France",
        "French Guiana",
        "French Polynesia",
        "French Southern Territories",
        "Gabon",
        "Gambia",
        "Georgia",
        "Germany",
        "Ghana",
        "Gibraltar",
        "Greece",
        "Greenland",
        "Grenada",
        "Guadeloupe",
        "Guam",
        "Guatemala",
        "Guinea",
        "Guinea-Bissau",
        "Guyana",
        "Haiti",
        "Heard Island and Mcdonald Islands",
        "Holy See (Vatican City State)",
        "Honduras",
        "Hong Kong",
        "Hungary",
        "Iceland",
        "India",
        "Indonesia",
        "Iran, Islamic Republic of",
        "Iraq",
        "Ireland",
        "Israel",
        "Italy",
        "Jamaica",
        "Japan",
        "Jordan",
        "Kazakhstan",
        "Kenya",
        "Kiribati",
        "Korea, Democratic People's Republic of",
        "Korea, Republic of",
        "Kuwait",
        "Kyrgyzstan",
        "Lao People's Democratic Republic",
        "Latvia",
        "Lebanon",
        "Lesotho",
        "Liberia",
        "Libyan Arab Jamahiriya",
        "Liechtenstein",
        "Lithuania",
        "Luxembourg",
        "Macao",
        "Macedonia, the Former Yugoslav Republic of",
        "Madagascar",
        "Malawi",
        "Malaysia",
        "Maldives",
        "Mali",
        "Malta",
        "Marshall Islands",
        "Martinique",
        "Mauritania",
        "Mauritius",
        "Mayotte",
        "Mexico",
        "Micronesia, Federated States of",
        "Moldova, Republic of",
        "Monaco",
        "Mongolia",
        "Montserrat",
        "Morocco",
        "Mozambique",
        "Myanmar",
        "Namibia",
        "Nauru",
        "Nepal",
        "Netherlands",
        "Netherlands Antilles",
        "New Caledonia",
        "New Zealand",
        "Nicaragua",
        "Niger",
        "Nigeria",
        "Niue",
        "Norfolk Island",
        "Northern Mariana Islands",
        "Norway",
        "Oman",
        "Pakistan",
        "Palau",
        "Palestinian Territory, Occupied",
        "Panama",
        "Papua New Guinea",
        "Paraguay",
        "Peru",
        "Philippines",
        "Pitcairn",
        "Poland",
        "Portugal",
        "Puerto Rico",
        "Qatar",
        "Reunion",
        "Romania",
        "Russian Federation",
        "Rwanda",
        "Saint Helena",
        "Saint Kitts and Nevis",
        "Saint Lucia",
        "Saint Pierre and Miquelon",
        "Saint Vincent and the Grenadines",
        "Samoa",
        "San Marino",
        "Sao Tome and Principe",
        "Saudi Arabia",
        "Senegal",
        "Serbia and Montenegro",
        "Seychelles",
        "Sierra Leone",
        "Singapore",
        "Slovakia",
        "Slovenia",
        "Solomon Islands",
        "Somalia",
        "South Africa",
        "South Georgia and the South Sandwich Islands",
        "Spain",
        "Sri Lanka",
        "Sudan",
        "Suriname",
        "Svalbard and Jan Mayen",
        "Swaziland",
        "Sweden",
        "Switzerland",
        "Syrian Arab Republic",
        "Taiwan, Province of China",
        "Tajikistan",
        "Tanzania, United Republic of",
        "Thailand",
        "Timor-Leste",
        "Togo",
        "Tokelau",
        "Tonga",
        "Trinidad and Tobago",
        "Tunisia",
        "Turkey",
        "Turkmenistan",
        "Turks and Caicos Islands",
        "Tuvalu",
        "Uganda",
        "Ukraine",
        "United Arab Emirates",
        "United Kingdom",
        "United States",
        "United States Minor Outlying Islands",
        "Uruguay",
        "Uzbekistan",
        "Vanuatu",
        "Venezuela",
        "Viet Nam",
        "Virgin Islands, British",
        "Virgin Islands, U.s.",
        "Wallis and Futuna",
        "Western Sahara",
        "Yemen",
        "Zambia",
        "Zimbabwe"
       ];
    return $countries;
}

if (!function_exists('load_class')) {

    /**
     * Class registry
     *
     * This function acts as a singleton. If the requested class does not
     * exist it is instantiated and set to a static variable. If it has
     * previously been instantiated the variable is returned.
     *
     * @param    string    the class name being requested
     * @param    string    the directory where the class should be found
     * @param    mixed    an optional argument to pass to the class constructor
     * @return    object
     */
    function &load_class($class, $directory = 'libraries', $param = null) {
        static $_classes = array();

        // Does the class exist? If so, we're done...
        if (isset($_classes[$class])) {
            return $_classes[$class];
        }

        $name = false;

        // Look for the class first in the local application/libraries folder
        // then in the native system/libraries folder
        foreach (array(APPPATH, BASEPATH) as $path) {
            if (file_exists($path . $directory . '/' . $class . '.php')) {
                $name = 'CI_' . $class;

                if (class_exists($name, false) === false) {
                    require_once $path . $directory . '/' . $class . '.php';
                }

                break;
            }
        }

        // Is the request a class extension? If so we load it too
        if (file_exists(CORE_PATH . $class . '.php')) {
            $name = $class;

            if (class_exists($name, false) === false) {
                require_once CORE_PATH . $name . '.php';
            }
        }

        // Did we find the class?
        if ($name === false) {
            // Note: We use exit() rather than show_error() in order to avoid a
            // self-referencing loop with the Exceptions class
            set_status_header(503);
            echo 'Unable to locate the specified class: ' . $class . '.php';
            exit(5); // EXIT_UNK_CLASS
        }

        // Keep track of what we just loaded
        is_loaded($class);

        $_classes[$class] = isset($param) ? new $name($param) : new $name();
        return $_classes[$class];
    }

}

function get_class_name($objClass) {

    $class_parts = explode('\\', get_class($objClass));
    return $class_name = strtolower($class_parts[count($class_parts) - 1]);
}

function get_export_view_name($objClass) {

    return str_replace('controller', '_grid', get_class_name($objClass));
}

// --------------------------------------------------------------------

if (!function_exists('is_loaded')) {

    /**
     * Keeps track of which libraries have been loaded. This function is
     * called by the load_class() function above
     *
     * @param    string
     * @return    array
     */
    function &is_loaded($class = '') {
        static $_is_loaded = array();

        if ($class !== '') {
            $_is_loaded[strtolower($class)] = $class;
        }

        return $_is_loaded;
    }

}

// ------------------------------------------------------------------------

if (!function_exists('get_config')) {

    /**
     * Loads the main config.php file
     *
     * This function lets us grab the config file even if the Config class
     * hasn't been instantiated yet
     *
     * @param    array
     * @return    array
     */
    function &get_config(array $replace = array()) {
        static $config;

        if (!empty($GLOBALS['config'])) {
            $config = $GLOBALS['config'];
        }

        if (empty($config)) {
            $file_path = APPPATH . 'config/config.php';
            $found = false;

            if (file_exists($file_path)) {
                $found = true;
                require_once $file_path;
            }

            // Is the config file in the environment folder?
            if (file_exists($file_path = APPPATH . 'config/' . ENVIRONMENT . '/config.php')) {
                require $file_path;
            } elseif (!$found) {
                set_status_header(503);
                echo 'The configuration file does not exist.';
                exit(3); // EXIT_CONFIG
            }

            // Does the $config array exist in the file?
            if (!isset($config) or ! is_array($config)) {
                set_status_header(503);
                echo 'Your config file does not appear to be formatted correctly.';
                exit(3); // EXIT_CONFIG
            }
        }

        // Are any values being dynamically added or replaced?
        foreach ($replace as $key => $val) {
            $config[$key] = $val;
        }

        return $config;
    }

}

// ------------------------------------------------------------------------

if (!function_exists('config_item')) {

    /**
     * Returns the specified config item
     *
     * @param    string
     * @return    mixed
     */
    function config_item($item) {
        static $_config;

        if (empty($_config)) {
            // references cannot be directly assigned to static variables, so we use an array
            $_config[0] = &get_config();
        }

        return isset($_config[0][$item]) ? $_config[0][$item] : null;
    }

}

function wardWrap($ward, $minNum) {
    $returnWard = $ward;
    if (strlen($ward) > $minNum) {
        $remainText = substr($ward, 0, $minNum);
        $string = $remainText;
        $string = explode(' ', $string);
        array_pop($string);
        $string = implode(' ', $string);

        $returnWard = $string . ' ...';
    }

    return $returnWard;
}

function remove_duplicate_exists($filesArray) {
    $docArray = array();
    $lastUploadDoc = array();
    $ct = 0;
    foreach ($filesArray as $dlList) {
        if ($dlList['tinType'] == 2) {

            if (!in_array($dlList['vchTitle'], $docArray)) {
                $lastUploadDoc[$ct]['intAppDocId'] = $dlList['intAppDocId'];
                $lastUploadDoc[$ct]['intDocId'] = $dlList['intDocId'];
                $lastUploadDoc[$ct]['vchStoreDoc'] = $dlList['vchStoreDoc'];
                $lastUploadDoc[$ct]['vchOrigDoc'] = $dlList['vchOrigDoc'];
                $lastUploadDoc[$ct]['vchDocuments'] = $dlList['vchDocuments'];
                $lastUploadDoc[$ct]['vchTitle'] = $dlList['vchTitle'];
                $lastUploadDoc[$ct]['tinType'] = $dlList['tinType'];
                $docArray[] = $dlList['vchTitle'];
            }
        } else {
            if (!in_array($dlList['intDocId'], $docArray)) {
                $lastUploadDoc[$ct]['intAppDocId'] = $dlList['intAppDocId'];
                $lastUploadDoc[$ct]['intDocId'] = $dlList['intDocId'];
                $lastUploadDoc[$ct]['vchStoreDoc'] = $dlList['vchStoreDoc'];
                $lastUploadDoc[$ct]['vchOrigDoc'] = $dlList['vchOrigDoc'];
                $lastUploadDoc[$ct]['vchDocuments'] = $dlList['vchDocuments'];
                $lastUploadDoc[$ct]['vchTitle'] = $dlList['vchTitle'];
                $lastUploadDoc[$ct]['tinType'] = $dlList['tinType'];
                $docArray[] = $dlList['intDocId'];
            }
        }

        $ct++;
    }

    return $lastUploadDoc;
}

function check_file_exists($files, $doc) {
    if (count($files) > 0) {
        $docs = false;
        foreach ($files as $list) {
            if ($list['intDocId'] == $doc) {
                $docs = $list['vchStoreDoc'] . "#" . $list['vchOrigDoc'] . "#" . $list['intAppDocId'];
            }
        }
        return $docs;
    } else {
        return false;
    }
}

/* -------Get Country List------ */

// function countryList() {
//     $cnd = array('and' => array(1 => ['eq' => 1]));
//     $objLand = new LeaseApplicationModel();

//     $res = $objLand->getWhereAdv($cnd, false, '', '', 'CT');
//     if ($res['errors'] == "") {
//         return $res['result'];
//     } else {
//         return false;
//     }
// }

// function regionList($country) {
//     $cnd = [];
//     if ($country == "") {
//         $cnd = array('and' => array(1 => ['eq' => 1]));
//     } else {
//         $cnd = array('and' => ['intCountryId' => ['eq' => $country]]);
//     }
//     $objLand = new LeaseApplicationModel();

//     $res = $objLand->getWhereAdv($cnd, false, '', '', 'GR');
//     if ($res['errors'] == "") {
//         return $res['result'];
//     } else {
//         return false;
//     }
// }








function addhttp($url) {
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "http://" . $url;
    }
    return $url;
}


// function to get the finacial year list from a given set of limits By Ashok Kumar samal :: ON: 29-04-2018
function getFinancialYear($lowerLimit, $higherlimit, $selval, $order = 0, $setValue = 0) {
    // $setValue=0 // 0 for default single value; >0 for Paired value 
    //  $order = 0 // 0 for default Ascending; >0 for Desc from highr value to lower value

    $options = '<option value="0"> -- Select -- </option>'; // set as Default
    if ($order > 0) {
        for ($i = $higherlimit; $i >= $lowerLimit; $i--) {
            $optVal = ($setValue > 0) ? $i . "-" . ($i - 1) : $i;
            $showVal = $i . "-" . ($i - 1);
            $selected = ($selval == $optVal) ? 'selected' : '';
            $options .= '<option value="' . $optVal . '" ' . $selected . '>' . $showVal . '</option>';
        }
    } else {
        for ($i = $lowerLimit; $i <= $higherlimit; $i++) {
            $optVal = ($setValue > 0) ? $i . "-" . ($i + 1) : $i;
            $showVal = $i . "-" . ($i + 1);
            $selected = ($selval == $optVal) ? 'selected' : '';
            $options .= '<option value="' . $optVal . '" ' . $selected . '>' . $showVal . '</option>';
        }
    }
    return $options;
}

// end Function
//============ Function to view in money format By ## By Ashok Kumar Samal ## 06-05-2018=========
function custom_money_format($n, $d = 2) {
    $n = str_replace(",", "", $n);
    $n = number_format((double) $n, $d, '.', '');
    $n = strrev($n);

    if ($d)
        $d++;
    $d += 3;

    if (strlen($n) > $d)
        $n = substr($n, 0, $d) . ',' . implode(',', str_split(substr($n, $d), 2));
    return strrev($n);
}

// Ordinal number suffix ## By Md. Shahnawaz Atique ## 02-07-2020
function ordinal($number) {
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number%100) <= 13))
        return $number. 'th';
    else
        return $number. $ends[$number % 10];
}

function getDateDifference($firstDate, $secondDate) {
    $first_date = new \DateTime($firstDate);
    $second_date = new \DateTime($secondDate);
    $difference = $first_date->diff($second_date);
    return format_interval($difference);
}

function format_interval($interval) {
    $result = "";
    if ($interval->y) {
        $result .= $interval->format("%y years ");
    }
    if ($interval->m) {
        $result .= $interval->format("%m months ");
    }
    if ($interval->d) {
        $result .= $interval->format("%d days ");
    }
    if ($interval->h) {
        $result .= $interval->format("%h hours ");
    }
    if ($interval->i) {
        $result .= $interval->format("%i minutes ");
    }
    if ($interval->s) {
        $result .= $interval->format("%s seconds ");
    }
    return $result;
}

function getDateDifference_hours($firstDate, $secondDate) {
    $first_date = new \DateTime($firstDate);
    $second_date = new \DateTime($secondDate);
    $difference = $first_date->diff($second_date);
    return format_interval_hours($difference);
}

function format_interval_hours($interval) {
    $result = "";
    $hour2 = 0;
    $hour1 = $interval->h + ($interval->days * 24);

    if ($interval->i > 0) {
        $hour2 = abs($interval->i / 60);
    }
    $result = $hour1 + $hour2;
    //$hour = abs($timestamp2 - $timestamp1)/(60*60) . " hour(s)";
    return $result;
}



// function To maintain uniform date format throughout project By: Ashok Kumar Samal :: On: 8-06-2018
function getFormattedDate($dbDate) {
    $formattedDate = date('d-m-Y h:i A', strtotime($dbDate));
    return $formattedDate;
}

// function To maintain uniform date format throughout project By: Ashok Kumar Samal :: On: 8-06-2018
function getDateFormatted($dbDate) {
    $formattedDate = date('d-m-Y', strtotime($dbDate));
    return $formattedDate;
}

// function To get the last ANd IRST date  By: Ashok Kumar Samal :: On: 21-06-2018
function getDateOfMonth($type = 'f', $pdate = "") {
    $date = (empty($pdate)) ? date('Y-m-d') : $pdate;
    if (strtolower($type) == 'l') {
        $last_date_find = strtotime(date("Y-m-d", strtotime($date)) . ", last day of this month");
        $last_date = date("Y-m-d", $last_date_find);
        return $last_date;
    } elseif (strtolower($type) == 'f') {
        $first_date_find = strtotime(date("Y-m-d", strtotime($date)) . ", first day of this month");
        $first_date = date("Y-m-d", $first_date_find);
        return $first_date;
    } else {
        return date("Y-m-d");
    }
}



//Function Desclaration Tiem Ago - By: Niranjan Kumar Pandit - On: 31-07-2018
function date_time_ago($input_date, $date_format = 'M d, Y h:i A', $time_format = 'h:i A') {
    $formated_date = "";
    if (!empty($input_date)) {

        $current_date_time = time();
        $orignal_date_time = strtotime($input_date);
        $date_time_difference = $current_date_time - $orignal_date_time;


        $full_days = floor($date_time_difference / (60 * 60 * 24));
        $full_hours = floor(($date_time_difference - ($full_days * 60 * 60 * 24)) / (60 * 60));
        $full_minutes = floor(($date_time_difference - ($full_days * 60 * 60 * 24) - ($full_hours * 60 * 60)) / 60);
        $full_seconds = floor(($date_time_difference - ($full_days * 60 * 60 * 24) - ($full_hours * 60 * 60) - ($full_minutes * 60)));

        if ($full_days > 2) {
            $time = date($date_format, $orignal_date_time);
        } elseif ($full_days == 2) {
            $time = '2 days ago';
        } elseif (date("j", $current_date_time) > date("j", $orignal_date_time)) {
            $time = 'Yesterday at ' . date($time_format, $orignal_date_time);
        } elseif ($full_hours > 0) {
            $time = $full_hours . ' hours ago';
            if ($full_hours == 1) {
                $time = $full_hours . ' hour ago';
            }
        } elseif ($full_minutes > 0) {
            $time = $full_minutes . ' mins ago';
            if ($full_minutes == 1) {
                $time = $full_minutes . ' min ago';
            }
        } else {
            $time = 'Just now';
        }
        $formated_date = $time;
    }
    return $formated_date;
}

// Get Roundup Mothly from two date - By: Niranjan Kumar Pandit - On: 30-07-2018
function MonthRoundup($dtmFrom, $dtmTill) {
    $ts1 = strtotime($dtmFrom);
    $ts2 = strtotime($dtmTill);

    $year1 = date('Y', $ts1);
    $year2 = date('Y', $ts2);

    $month1 = date('m', $ts1);
    $month2 = date('m', $ts2);

    $diff = (($year2 - $year1) * 12) + ($month2 - $month1);

    return $diff;
}

///  Get Roundup Yearly From two date - By: Niranjan Kumar Pandit - On: 31-07-2018

function yearsDifference($endDate, $beginDate) {
    $date_parts1 = explode("-", $beginDate);
    $date_parts2 = explode("-", $endDate);
    return $date_parts2[0] - $date_parts1[0];
}

/* * *****************function to generate permitted menus, BY : T Ketaki Debadarshini , ON: 28-March-2018****************** */

function convertLinkToArray($results, $idField = 'id', $parentIdField = 'parent', $childrenField = 'children', $arrPermissions = array(), $chkPermission = 1) {
    $hierarchy = array(); // -- Stores the final data
    $itemReferences = array(); // -- temporary array, storing references to all items in a single-dimention
    foreach ($results as $item) {

        $id = $item[$idField];
        $parentId = $item[$parentIdField];

        if (isset($itemReferences[$parentId])) { // parent exists
            if ($item['tinRights'] == 1) {
                if ((isset($arrPermissions[$item[$idField]]) && $arrPermissions[$item[$idField]] > '0') || ($chkPermission == 0)) {

                    $item['intRights'] = (isset($arrPermissions[$item[$idField]])) ? $arrPermissions[$item[$idField]] : 0;
                    $itemReferences[$parentId][$childrenField][$id] = $item; // assign item to parent
                    $itemReferences[$id] = & $itemReferences[$parentId][$childrenField][$id]; // reference parent's item in single-dimentional array           
                }
            } else {

                $itemReferences[$parentId][$childrenField][$id] = $item; // assign item to parent
                $itemReferences[$id] = & $itemReferences[$parentId][$childrenField][$id]; // reference parent's item in single-dimentional array           
            }
        } elseif (!$parentId || !isset($hierarchy[$parentId])) { // -- parent Id empty or does not exist. Add it to the root
            if ($item['tinRights'] == 1) {
                if ((isset($arrPermissions[$item[$idField]]) && $arrPermissions[$item[$idField]] > '0') || ($chkPermission == 0)) {

                    $item['intRights'] = (isset($arrPermissions[$item[$idField]])) ? $arrPermissions[$item[$idField]] : 0;

                    $hierarchy[$id] = $item;
                    $itemReferences[$id] = & $hierarchy[$id];
                }
            } else {

                $hierarchy[$id] = $item;
                $itemReferences[$id] = & $hierarchy[$id];
            }
        }
    }
    unset($results, $item, $id, $parentId);
    // -- Run through the root one more time. If any child got added before it's parent, fix it.
    foreach ($hierarchy as $id => &$item) {
        $parentId = $item[$parentIdField];
        if (isset($itemReferences[$parentId])) { // -- parent DOES exist
            $itemReferences[$parentId][$childrenField][$id] = $item; // -- assign it to the parent's list of children
            unset($hierarchy[$id]); // -- remove it from the root of the hierarchy
        }
    }
    // added BY aSHOK kUMAR sAMAL :: oN: 18-04-2014
    foreach ($hierarchy as $idKey => $idVal) {


        if (!array_key_exists('childLinks', $idVal) && $idVal['tinRights'] != 1) {
            unset($hierarchy[$idKey]);
        }
    }

    //print_r($hierarchy);exit;

    unset($itemReferences, $id, $item, $parentId);
    return $hierarchy;
}

/** This Function Use to Send SMS on Mobile :: By : Niranjan Kumar Pandit :: On : 26-09-2018 * */
function sendSMS($mobile, $message) {
    try {
        //$route="T";
        $username = SMSUSER;
        $api_password = SMSPASS;
        $sender = SMSSENDER;
        $mobile = '249' . $mobile;
        $message = urlencode($message);
        // $parameters="http://196.202.140.108/bulksms/webacc.aspx?user=".$username."&pwd=".$api_password."&smstext=".$message."&Sender=".$sender."&Nums=".$mobile;
        // $parameters="http://197.254.204.7/NICBulkSMS/webacc.aspx?user=".SMSUSER."&pwd=".$api_password."&smstext=".$message."&Sender=".$sender."&Nums=".$mobile;
        $parameters = "http://10.0.14.93/NICBulkSMS/webacc.aspx?user=" . SMSUSER . "&pwd=" . $api_password . "&smstext=" . $message . "&Sender=" . $sender . "&Nums=" . $mobile;
        // echo $parameters;exit; 
//http://197.254.204.7/NICBulkSMS/webacc.aspx?user=nictest&pwd=nic@123&smstext=TestSMS&Sender=DataSoft@CSMPVTLTD1&Nums=249913564121
        //echo $parameters;exit;
        $response = fopen($parameters, "r");
        $RqResponse = stream_get_contents($response);
        fpassthru($response);
        fclose($response);
        //echo $RqResponse;exit;
        if (ctype_digit($RqResponse))
            $messageId = $RqResponse;
        else
            $errorMsg = $RqResponse;
    } catch (Exception $e) {
        writeException($e);
    }
}

/** This Function is Use to Write Exception :: By : Niranjan Kumar Pandit :: On : 06-09-2018 * */
function writeException($e) {
    $msg = $e->getMessage();
    $trace = $e->getTrace();
    $result .= 'Class: ' . $trace[0]['class'] . ', ';
    $result .= 'Function: ' . $trace[0]['function'] . ',';
    $result .= 'Line: ' . $trace[0]['line'] . ', ';
    $result .= 'File: ' . $trace[0]['file'];
    //echo 'Caught exception: <pre>'.print_r($e->getMessage())."</pre>\n";//exit;
    // WRITE TEXTFILE START///
    $filename = 'errorLog' . date('d-m-Y') . '.txt';
    $myfile = fopen(PUBLIC_PATH . 'errorLog/' . $filename, "a") or die("Unable to open file!");
    $txt = "Error Occured On :" . date('d-m-Y H:i:s') . " \r\n";
    fwrite($myfile, "\r\n" . $txt);
    $txt = "==============================================\r\n";
    fwrite($myfile, $txt);
    $txt = "Error Message: " . $msg . " \r\n";
    fwrite($myfile, "\r\n" . $txt);
    $txt = $result . "\r\n";
    fwrite($myfile, $txt);
    fclose($myfile);
    // WRITE TEXTFILE END///  
}


    //========= Check Special Character ==============
    function isSpclChar($strToCheck) {
        $arrySplChar = explode(',', SPLCHRS);
        $errFlag = 0;
        for ($i = 0; $i < count($arrySplChar); $i++) {
            $intPos = substr_count($strToCheck, trim($arrySplChar[$i]));
            if ($intPos > 0)
                $errFlag++;
        }
        return $errFlag;
    }

function wrapWord($ward, $minNum) {
    $returnWard = $ward;
    if (strlen($ward) > $minNum) {
        $remainText = substr($ward, 0, $minNum);
        $string = $remainText;
        $string = explode(' ', $string);
        array_pop($string);
        $string = implode(' ', $string);
        $returnWard = $string . ' ...';
    }
    return $returnWard;
}

/** This Function is Used to Find Academic Year :: By : Md. Shahnawaz Atique :: On : 09-Nov-2019 **/
function academicYear($selYear = "", $startYear = START_ACAD_YEAR, $fromChk = 0) {
    $selYear = ($selYear != "") ? $selYear : "";//currAcademicYear();
    $currentMonth=date("m"); 
    if($currentMonth >= 4){
        $currYear = date("Y");
    }else{
        $currYear = date("Y") - 1;
    }
    $selOption = '';
    if($fromChk != 1) {
        $selOption = '<option value="0">--Select--</option>';
    }
    for ($i = $startYear; $i <= $currYear; $i++) {
        $nextYear = $startYear.'-01-01';
        $year = $startYear.'-'.((date("y", strtotime($nextYear)))+1);
        if($year==$selYear){ $select='selected'; }else{ $select=''; }
        $arrParam['academicYear'] = $year;
        $strParam = encparam(json_encode($arrParam));
        $selOption .= '<option data-year="'.$strParam.'" value="'.$year.'" '.$select.'>'.$year.'</option>';
        $startYear++;
    }
    return $selOption;
}

function currAcademicYear() {
    $currentYear=date("Y");
    $currentMonth=date("m"); 
    if($currentMonth >= 4) {
        $nextYear = $currentYear.'-01-01';
        $currAcademicYear = ($currentYear).'-'.(date("y", strtotime($nextYear))+1);
    }
    else {
        $currAcademicYear = ($currentYear-1).'-'.(date("y"));
    }
    return $currAcademicYear;
}

function make_comparer() {
    // Normalize criteria up front so that the comparer finds everything tidy
    $criteria = func_get_args();
    foreach ($criteria as $index => $criterion) {
        $criteria[$index] = is_array($criterion)
            ? array_pad($criterion, 3, null)
            : array($criterion, SORT_ASC, null);
    }

    return function($first, $second) use ($criteria) {
        foreach ($criteria as $criterion) {
            // How will we compare this round?
            list($column, $sortOrder, $projection) = $criterion;
            $sortOrder = $sortOrder === SORT_DESC ? -1 : 1;

            // If a projection was defined project the values now
            if ($projection) {
                $lhs = call_user_func($projection, $first[$column]);
                $rhs = call_user_func($projection, $second[$column]);
            }
            else {
                $lhs = $first[$column];
                $rhs = $second[$column];
            }

            // Do the actual comparison; do not return if equal
            if ($lhs < $rhs) {
                return -1 * $sortOrder;
            }
            else if ($lhs > $rhs) {
                return 1 * $sortOrder;
            }
        }

        return 0; // tiebreakers exhausted, so $first == $second
    };
}

/** Convert Price to Crores or Lakhs or Thousands :: By : Md. Shahnawaz Atique :: On : 14-Feb-2020 **/
function convertCurrency($number = ''){
    // Convert Price to Crores or Lakhs or Thousands
    $length = strlen(round($number));
    $currency = '';
    // echo $length; exit;
    if($length < 4){
        $ext = "";
        $currency = $number;
    }
    elseif($length == 4 || $length == 5){
        // Thousand
        $number = $number / 1000;
        $number = round($number,2);
        $ext = "K";
        $currency = $number;
    }elseif($length == 6 || $length == 7){
        // Lakhs
        $number = $number / 100000;
        $number = round($number,2);
        $ext = "Lac";
        $currency = $number;
    }elseif($length>=8){
        // Crores
        $number = $number / 10000000;
        $number = round($number,2);
        $ext = "Cr.";
        $currency = $number;
    }
    return $currency.$ext;
}

/** Replace String :: By : Md. Shahnawaz Atique :: On : 25-Feb-2020 **/
function replaceMessage($arrayFind, $arrayReplace, $stringToBeReplace){
    return str_replace($arrayFind, $arrayReplace, $stringToBeReplace);
}

//  function getdetaills($id)
// {
//     //dd($id);
//     $totalRecord=DB::table('t_requested_ifsc_code')
//     ->select('vch_Ifsc_Code','vch_District_Name','vch_Bank_Name','vch_Branch_Name','dtm_Created_On','int_Status')
//     ->get();
//     //return $totalRecord;
//     //dd($totalRecord);
// }
/** Fetch Course Details for Institute :: By : Md. Shahnawaz Atique :: On : 28-May-2020 **/
function tagcourseClass($Id) {
    $tagcourses = DB::table('t_institute_course_info AS a')
                        ->select(DB::raw('a.*, c.vch_Course_Name, s.vch_Stream_Name'))
                        ->leftJoin('m_course_class AS c', 'a.int_Course_Id', '=', 'c.int_Course_Id')
                        ->leftJoin('m_course_stream AS s', 'a.int_Stream_Id', '=', 's.int_Stream_Id')
                        ->where('a.int_Institute_Id', $Id)
                        ->where('a.bit_Deleted_Flag', '=', 0)
                        ->get();
    return $tagcourses;
}

/** Range Course Details for Institute :: By : Md. Shahnawaz Atique :: On : 29-May-2020 **/
function rangeCourse($course) {
    $ret_courses = $course;
    if($course == "I, II, III, IV, IX, V, VI, VII, VIII, X") {
        $ret_courses = "I - X";
    }
    else if($course == "I, II, III, IV, V, VI, VII, VIII, IX, X") {
        $ret_courses = "I - X";
    }
    else if($course == "+2, I, II, III, IV, IX, V, VI, VII, VIII, X") {
        $ret_courses = "I to +2";
    }
    return $ret_courses;
}

function getDashboardSchemesStatistics($deptId, $acadYear,$openScheme) {

    $getSchemewiseAppDataQuery = DB::table('m_scheme as b')
                        ->select(DB::raw('b.int_scheme_id, b.int_Dept_Id, b.vch_Scheme_Name, COUNT(IF(((a.int_App_Status = 3 && a.int_Proposal_Status = 2) || a.int_App_Status = 13 || a.int_App_Status = 14 || a.int_App_Status = 15), 1, null)) as total, COUNT(IF((a.int_App_Status = 13), 1, null)) as rejected, COUNT(IF((a.int_App_Status = 14), 1, null)) as approved, COUNT(IF((a.int_App_Status = 15), 1, null)) as reverted, COUNT(IF((a.int_Disbursed_Status = 3), 1, null)) as disbursed'))
                        ->where('b.int_Dept_Id', '=', $deptId)
                        ->where('b.int_Parent_Value_Id', '=', 0)
                        ->where('b.bit_Deleted_Flag', '=', 0)
                        ->groupBy('b.int_Dept_Id', 'b.int_scheme_id');
                        

    if($acadYear == currAcademicYear()){
        $arrRes = DB::table('m_scheme')
        ->where([
            ['int_Dept_Id', '=', $deptId],
            ['int_Parent_Value_Id', '=', 0],
            ['bit_Deleted_Flag', '=', 0],
        ])->orderby('vch_Scheme_Name', 'asc')->pluck('int_Scheme_Id');
      
        $getSchemewiseAppDataQuery->leftJoin('t_application as a', function ($query) use ($acadYear, $deptId) {
            $query->on('a.int_Scheme_Id', '=', 'b.int_scheme_id')
                    ->on('a.int_Dept_Id', '=', 'b.int_Dept_Id')
                    ->where('a.bit_Deleted_Flag', '=', 0)
                     
                    ->where('a.vch_Academic_Year', '=', $acadYear)
                    ->where('a.int_Dept_Id', '=', $deptId);
                    
        });
       
        }
        
    else{
        $acad = str_replace("-", "_", $acadYear);
        
        $getSchemewiseAppDataQuery->leftJoin('t_application_'.$acad.' as a', function ($query) use ($acadYear, $deptId) {
            $query->on('a.int_Scheme_Id', '=', 'b.int_scheme_id')
                    ->on('a.int_Dept_Id', '=', 'b.int_Dept_Id')
                    ->where('a.bit_Deleted_Flag', '=', 0)
                    ->where('a.vch_Academic_Year', '=', $acadYear)
                    ->where('a.int_Dept_Id', '=', $deptId);
        });
    }
    if($openScheme>0)
    {
        $getSchemewiseAppDataQuery->join('t_payment_date as c','b.int_scheme_id','=','c.int_Scheme_Id')
        ->where('c.bit_Deleted_Flag','=',0)
        ->whereRaw("? between c.dte_From_Date and c.dte_To_Date", [date('Y-m-d ')]);  
    }

    $getSchemewiseAppData = $getSchemewiseAppDataQuery->get()->toArray();
    
    if($getSchemewiseAppData)
        {
            foreach($getSchemewiseAppData as $k => $ar)
            {

                $childData = DB::table('m_scheme as b')
                            ->selectRaw('b.int_scheme_id, b.int_Dept_Id, b.vch_Scheme_Name, COUNT(IF(((a.int_App_Status = 3 && a.int_Proposal_Status = 2) || a.int_App_Status = 13 || a.int_App_Status = 14 || a.int_App_Status = 15), 1, null)) as total, COUNT(IF((a.int_App_Status = 13), 1, null)) as rejected, COUNT(IF((a.int_App_Status = 14), 1, null)) as approved, COUNT(IF((a.int_App_Status = 15), 1, null)) as reverted, COUNT(IF((a.int_Disbursed_Status = 3), 1, null)) as disbursed')
                            ->leftJoin('t_application as a', function ($query) use ($acadYear, $deptId) {
                                $query->on('a.int_Scheme_Id', '=', 'b.int_scheme_id')
                                        ->on('a.int_Dept_Id', '=', 'b.int_Dept_Id')
                                        ->where('a.bit_Deleted_Flag', '=', 0)
                                         
                                        ->where('a.vch_Academic_Year', '=', $acadYear)
                                        ->where('a.int_Dept_Id', '=', $deptId);
                                        
                            })
                        ->where([
                            ['b.int_Dept_Id', '=', $deptId],
                            ['b.int_Parent_Value_Id', '=', $ar->int_scheme_id],
                            ['b.bit_Deleted_Flag', '=', 0],
                        ])->orderby('vch_Scheme_Name', 'asc')->groupBy('b.int_Dept_Id', 'b.int_scheme_id')->get()->toArray();
                if(count($childData) > 0){
                    for($i = 0; $i < count($childData); $i++){
                        $childData[$i]->vch_Scheme_Name = $getSchemewiseAppData[$k]->vch_Scheme_Name.' '.$childData[$i]->vch_Scheme_Name;
                        array_push($getSchemewiseAppData, $childData[$i]);
                    }
                    unset($getSchemewiseAppData[$k]);
                }
            }
        }

    return $getSchemewiseAppData;
}

function getDashboardSchemesStatisticsDwo($deptId, $acadYear) {
    $district = session()->get('admin_session.district_id');
    $getSchemewiseAppDataQuery = DB::table('m_scheme as b')
                        ->select(DB::raw('b.int_scheme_id, b.int_Dept_Id, b.vch_Scheme_Name, COUNT(IF((a.int_App_Status = 2 || a.int_App_Status = 3 || a.int_App_Status = 13 || a.int_App_Status = 14 || a.int_App_Status = 15) && (c.int_Institute_Dist_Id = '.$district.'), 1, null)) as total, COUNT(IF((a.int_App_Status = 4) && (c.int_Institute_Dist_Id = '.$district.'), 1, null)) as rejected, COUNT(IF(((a.int_App_Status = 3 && a.int_Proposal_Status = 2)  || a.int_App_Status = 13 || a.int_App_Status = 14 || a.int_App_Status = 15) && (c.int_Institute_Dist_Id = '.$district.'), 1, null)) as approved, COUNT(IF((a.int_App_Status = 5) && (c.int_Institute_Dist_Id = '.$district.'), 1, null)) as reverted, COUNT(IF((a.int_Disbursed_Status = 3) && (c.int_Institute_Dist_Id = '.$district.'), 1, null)) as disbursed'));
                        if($acadYear == currAcademicYear()){
                            $getSchemewiseAppDataQuery->leftJoin('t_application as a', function ($query) use ($acadYear, $deptId) {
                                $query->on('a.int_Scheme_Id', '=', 'b.int_scheme_id')
                                        ->on('a.int_Dept_Id', '=', 'b.int_Dept_Id')
                                        ->where('a.bit_Deleted_Flag', '=', 0)
                                        ->where('a.vch_Academic_Year', '=', $acadYear)
                                        ->where('a.int_Dept_Id', '=', $deptId);
                            });
                        }else{
                            $acad = str_replace("-", "_", $acadYear);
                            $getSchemewiseAppDataQuery->leftJoin('t_application_'.$acad.' as a', function ($query) use ($acadYear, $deptId) {
                                $query->on('a.int_Scheme_Id', '=', 'b.int_scheme_id')
                                        ->on('a.int_Dept_Id', '=', 'b.int_Dept_Id')
                                        ->where('a.bit_Deleted_Flag', '=', 0)
                                        ->where('a.vch_Academic_Year', '=', $acadYear)
                                        ->where('a.int_Dept_Id', '=', $deptId);
                            });
                        }       
                        // ->leftJoin('t_application as a', function ($query) use ($acadYear, $deptId) {
                        //     $query->on('a.int_Scheme_Id', '=', 'b.int_scheme_id')
                        //             ->on('a.int_Dept_Id', '=', 'b.int_Dept_Id')
                        //             ->where('a.bit_Deleted_Flag', '=', 0)
                        //             ->where('a.vch_Academic_Year', '=', $acadYear)
                        //             ->where('a.int_Dept_Id', '=', $deptId);
                        // })
        $getSchemewiseAppDataQuery = $getSchemewiseAppDataQuery->leftJoin('m_institute as c', function ($query) use($district) {
                            $query->on('a.int_Inst_Id', '=', 'c.int_Institute_Id')
                            // ->where('c.int_Institute_Dist_Id', $district)
                            ->where('c.bit_Deleted_Flag', '=', 0); 
                        })
                        // ->rightJoin('t_application as b', 'a.int_Scheme_Id', '=', 'b.int_scheme_id')
                        ->where('b.int_Dept_Id', '=', $deptId)
                        // ->where('c.int_Institute_Dist_Id', $district)
                        ->where('b.bit_Deleted_Flag', '=', 0)
                        ->groupBy('b.int_Dept_Id', 'b.int_scheme_id');

                 
    $getSchemewiseAppData = $getSchemewiseAppDataQuery->get();
    return $getSchemewiseAppData;
}

function checkUID($uid, $name){
    $curl = curl_init();
    $params=array(
        "uid"=> $uid,
        "uidType"=> "A",
        "consent"=> "Y",
        "subAuaCode"=> "PSTOD12214",
        "isPI"=> "y",
        "isBio"=> "n",
        "isOTP"=> "n",
        "name"=> $name
    );
    /**
     * Production URL : http://164.100.141.79/authekycprodv25/api/authenticate
     * Production SubAUA Code : PSTOD12214
     * Pre Prod URL : http://164.100.141.79/prev25/api/authenticate
     * Pre Prod SubAUA Code : SSTOD12214
    **/
    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://164.100.141.79/authekycprodv25/api/authenticate",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "cache-control: no-cache"
        )
      ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      //echo "cURL Error #:" . $err;
        return $err;
    } else {
        return $response;
        //echo $response;
        //$response = json_decode($response);
        //echo $response->status." : ".$response->errMsg;
    }

}

//Generate Otp for Aadhar
function generateUIDOtp($uid){
    $curl = curl_init();
    $params=array(
        "uid"=> $uid,
        "uidType"=> "A",
        "subAuaCode"=> "0002590000"
    );

    /**
     * Production URL : http://164.100.141.79/authekycprodv25/api/generateOTP
     * Production SubAUA Code : PSTOD12214
     * Pre Prod URL : http://164.100.141.79/prev25/api/generateOTP
     * Pre Prod SubAUA Code : STGOCAC001
    **/
    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://164.100.141.79/authekycprodv25/api/generateOTP",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "cache-control: no-cache"
        )
      ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
        return $err;
    } else {
        return $response;
    }
    
}


//Validate Otp for Aadhar
function verifyUIDOtp($uid, $txnId, $txtOtp){
    $curl = curl_init();
    $params=array(
        "uid"=> $uid,
        "uidType"=> "A",
        "consent"=> "Y",
        "subAuaCode"=> "0002590000",
        "txn"=> $txnId,
        "isBio"=> "n",
        "isOTP"=> "y",
        "bioType"=> "",
        "rdInfo"=> "",
        "rdData"=> "",
        "otpValue"=> $txtOtp
    );

    // $params=array(
    //     "uid"=> $uid,
    //     "uidType"=> "A",
    //     "consent"=> "Y",
    //     "subAuaCode"=> "0002590000",
    //     "txn"=> $txnId,
    //     "isBio"=> "n",
    //     "isOTP"=> "y",
    //     "isPI" => "n",
    //     "bioType"=> "",
    //     "rdInfo"=> "",
    //     "rdData"=> "",
    //     "otpValue"=> $txtOtp
    // );
    
    /**
     * Production URL : http://164.100.141.79/authekycprodv25/api/kyc
     * Production SubAUA Code : PSTOD12214
     * Pre Prod URL : http://164.100.141.79/prev25/api/kyc
     * Pre Prod SubAUA Code : STGOCAC001
    **/
    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://164.100.141.79/authekycprodv25/api/kyc",
        // CURLOPT_URL => "http://164.100.141.79/authekycprodv25/api/authenticate",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "cache-control: no-cache"
        )
      ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
        return $err;
    } else {
        return $response;
    }
    
}

//get wadh

function getWadh(){
    $curl = curl_init();
    $params=array(
        "uid"=> "530009966277",
        "uidType"=> "A",
        "consent"=> "Y",
        "subAuaCode"=> "PSTOD12214",
        "txn"=> "",
        "isBio"=> "y",
        "isOTP"=> "n",
        "bioType"=> "FMR",
        "rdInfo"=> "",
        "rdData"=> "",
        "otpValue"=> ""
    );

    /**
     * Production URL : http://164.100.141.79/authekycprodv25/api/kyc
     * Production SubAUA Code : PSTOD12214
     * Pre Prod URL : http://164.100.141.79/prev25/api/kyc
     * Pre Prod SubAUA Code : STGOCAC001
    **/
    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://164.100.141.79/authekycprodv25/api/wadh",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "cache-control: no-cache"
        )
      ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
        return $err;
    } else {
        return $response;
    }
    
}

//Validate biometric for Aadhar
function verifyUIDBiometric($uid, $rdInfo, $rdData){
    $curl = curl_init();
    $params=array(
        "uid"=> $uid,
        "uidType"=> "A",
        "consent"=> "Y",
        "subAuaCode"=> "PSTOD12214",
        "txn"=> "",
        // "isPI"=> "n",
        "isBio"=> "y",
        "isOTP"=> "n",
        "bioType"=> "FMR",
        // "name"=> "",
        "rdInfo"=> $rdInfo,
        "rdData"=> $rdData,
        "otpValue"=> ""
        // "uid"=> $uid,
        // "uidType"=> "A",
        // "consent"=> "Y",
        // "subAuaCode"=> "PSTOD12214",
        // "txn"=> "",
        // "isBio"=> "y",
        // "isOTP"=> "n",
        // "bioType"=> "FMR",
        // "rdInfo"=> $rdInfo,
        // "rdData"=> $rdData,
        // "otpValue"=> ""
    );

    /**
     * Production URL : http://164.100.141.79/authekycprodv25/api/kyc
     * Production SubAUA Code : PSTOD12214
     * Pre Prod URL : http://164.100.141.79/prev25/api/kyc
     * Pre Prod SubAUA Code : STGOCAC001
    **/
    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://164.100.141.79/authekycprodv25/api/kyc",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "cache-control: no-cache"
        )
      ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
        return $err;
    } else {
        return $response;
    }
    
}

function checkBeneficiaryID($bid){
    $curl = curl_init();
    $params=array(
        "strBeneficiaryId"=> $bid,
        "strSecurityKey"=> KALIA_SECRET_KEY
    );

    curl_setopt_array($curl, array(
        CURLOPT_URL => KALIA_SERVICE_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "cache-control: no-cache"
        )
      ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      //echo "cURL Error #:" . $err;
        return $err;
    } else {
        return $response;
        //echo $response;
        //$response = json_decode($response);
        //echo $response->status." : ".$response->errMsg;
    }

}

//check ration card No API BY :: Md Shahnawaz On:: 05-Feb-2021
function checkRationCardNo($rationCardNo, $beneficiaryAadhaarNo){
    $curl = curl_init();
    $url = "http://10.1.1.70/rcms_service/KaliaService.asmx/GetRationCardStatus?RationCardNumber=".$rationCardNo."&AadhaarNumber=".$beneficiaryAadhaarNo."&SecurityKey=88ADD0B9-38DD-4E60-9762-5466F0AC3229";
    // $url = "http://192.168.201.226/KaliaService/KaliaService.asmx/GetRationCardStatus?RationCardNumber=".$rationCardNo."&AadhaarNumber=".$beneficiaryAadhaarNo."&SecurityKey=88ADD0B9-38DD-4E60-9762-5466F0AC3229";

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET"
      ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      //echo "cURL Error #:" . $err;
        return $err;
    } else {
        return $response;
        //echo $response;
        //$response = json_decode($response);
        //echo $response->status." : ".$response->errMsg;
    }

}

// get beneficiary id of labour :: 16-10-2020 By Abhijit Sahoo
function getLabourBeneficiaryID($bid){
    $curl = curl_init();
    // $params=array(
    //     "strBeneficiaryId"=> $bid,
    //     "strSecurityKey"=> KALIA_SECRET_KEY
    // );

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://bocboard.labdirodisha.gov.in/Building/rest/services/getBeneficiaryNo?aadhaar='.$bid,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        // CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "cache-control: no-cache"
        )
      ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      //echo "cURL Error #:" . $err;
        return $err;
    } else {
        return $response;
        //echo $response;
        //$response = json_decode($response);
        //echo $response->status." : ".$response->errMsg;
    }

}

// get labour data from beneficiary id :: 16-10-2020 By Abhijit Sahoo
function checkLabourBeneficiaryID($bid){
    $curl = curl_init();
    // $params=array(
    //     "strBeneficiaryId"=> $bid,
    //     "strSecurityKey"=> KALIA_SECRET_KEY
    // );

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://bocboard.labdirodisha.gov.in/Building/rest/services/eScholarship?beneficiaryNo='.$bid,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        // CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "cache-control: no-cache"
        )
      ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      //echo "cURL Error #:" . $err;
        return $err;
    } else {
        return $response;
        //echo $response;
        //$response = json_decode($response);
        //echo $response->status." : ".$response->errMsg;
    }

}

function getMarksSams($boardId, $rollNo, $passingYear){
    $curl = curl_init();
    if($boardId == 2 || $boardId == 38) {
        $url = "http://164.100.141.100/GetMarkservice/Getmarkservice.asmx/GetMatricResult?YearofPassing=".$passingYear."&RollNo=".$rollNo;
    } else {
        $url = "http://164.100.141.100/GetMarkservice/Getmarkservice.asmx/GetPlus2Result?YearofPassing=".$passingYear."&RollNo=".$rollNo;
    }

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      //echo "cURL Error #:" . $err;
        return $err;
    } else {
        return $response;
        //echo $response;
        //$response = json_decode($response);
        //echo $response->status." : ".$response->errMsg;
    }

}


function getEdistrictData($barcode, $value){
    $curl = curl_init();
    
    
    $url = "http://164.100.141.100/CasteCertificateService/CasteCertificateService.asmx/Certificatestatus?Barcode=".$barcode."&values=".$value;


    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      //echo "cURL Error #:" . $err;
        return $err;
    } else {
        return $response;
        //echo $response;
        //$response = json_decode($response);
        //echo $response->status." : ".$response->errMsg;
    }

}

function generatePrematricToken(){
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://emisosepa.odisha.gov.in:2222/api/auth/oauth/token",
        // CURLOPT_URL => "http://103.205.67.243:2222/api/auth/oauth/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => array('username' => '21-4','password' => 'Osepa@123','grant_type' => 'password'),
        CURLOPT_HTTPHEADER => array(
            "Authorization: Basic bWhyZF9zaGFhbGE6c2hhYWxha29zaEA0MzIx"
        ),
    ));
    $response = curl_exec($curl);
    return $response;
}

function getPrematricStudents($udise_code, $page_no = 0, $limit = 10, $gender = 0, $category = 0, $class = 0, $religion = 0, $acad_year = '2020-21', $access_token){
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "http://emisosepa.odisha.gov.in:2222/api/student/student_profile/getStudentDetails",
        // CURLOPT_URL => "http://103.205.67.243:2222/api/student/student_profile/getStudentDetails",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS =>"{ \r\n    \"schoolId\":$udise_code,\r\n    \"pageNumber\":$page_no,\r\n    \"limit\":$limit,\r\n    \"gender\":$gender,\r\n    \"category\":$category, \r\n    \"classs\":$class,\r\n    \"religion\":$religion,\r\n    \"academicYear\":\"$acad_year\"\r\n}",
        CURLOPT_HTTPHEADER => array(
          "Authorization: Bearer $access_token",
          "Content-Type: application/json"
        ),
    ));
    $response = curl_exec($curl);
    return $response;
}

function applicationStatus($appStatus, $disburseStatus, $proposalStatus = 1, $revertTo = 0, $reopenTo = 0, $verifiedBy = 0){
    $status = '--';

    if($disburseStatus == 3){
        $status = 'Disbursed by Department';
    }elseif($disburseStatus == 2){
        $status = 'Disbursement Processed';
    }elseif($appStatus == 1 || $appStatus == 9){
        $status = 'Pending at Institute';
    }elseif($appStatus == 2 || ($appStatus == 3 && $proposalStatus == 1)){
        $status = 'Pending at District Authority';
    // }elseif($appStatus == 3  && $proposalStatus == 2 && $verifiedBy == 6){
    //     $status = 'Updated by Department';
    // }elseif($appStatus == 3  && $proposalStatus == 2 && $verifiedBy == 18){
    //     $status = 'Updated by Sanctioned Authority';
    }elseif($appStatus == 3  && $proposalStatus == 2){
        $status = 'Sanctioned by District Authority';
    }elseif($appStatus == 4){
        $status = 'Rejected by District Authority';
    }elseif($appStatus == 5 && $revertTo == 3){
        $status = 'Reverted to Student';
    }elseif($appStatus == 5 && ($revertTo == 0 || $revertTo == 2)){
        $status = 'Reverted to Institute';
    }elseif($appStatus == 6){
        $status = 'Inactive';
    }elseif($appStatus == 10){
        $status = 'Rejected by Institute';
    }elseif($appStatus == 11){
        $status = 'Reverted by Institute';
    }elseif($appStatus == 12){
        $status = 'Re-applied by Student';
    }elseif($appStatus == 13){
        $status = 'Rejected by Department';
    }elseif($appStatus == 14 && $verifiedBy == 18){
        $status = 'Approved by Sanctioned Authority';
    }elseif($appStatus == 14 && $verifiedBy == 19){
        $status = 'Approved by Approval Authority';
    }elseif($appStatus == 14){
        $status = 'Approved by Department';
    }elseif($appStatus == 15 && $revertTo == 3){
        $status = 'Reverted to Student';
    }elseif($appStatus == 15 && ($revertTo == 0 || $revertTo == 2)){
        $status = 'Reverted to Institute';
    }elseif($appStatus == 15 && $revertTo == 1){
        $status = 'Reverted to District Authority';
    }elseif($appStatus == 16 && ($reopenTo == 0 || $reopenTo == 1)){
        $status = 'Re-opened to Student';
    }elseif($appStatus == 16 && $revertTo == 2){
        $status = 'Re-opened to Institute';
    }elseif($appStatus == 16 && $revertTo == 3){
        $status = 'Re-opened to District Authority';
    }elseif($appStatus == 16){
        $status = 'Re-opened by Department';
    }

    return $status;
}

function array_to_csv_download($array, $filename = "export.csv", $delimiter=",") {
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'";');

    // open the "output" stream
    // see http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
    $f = fopen('php://output', 'w');

    foreach ($array as $line) {
        fputcsv($f, $line, $delimiter);
    }
}   


function generateDigilockerToken($code){

    $curl = curl_init();

    $url = env('WEB_URL');

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.digitallocker.gov.in/public/oauth2/1/token",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
    //   CURLOPT_POSTFIELDS => "{\r\n    \"code\": \"$code\",\r\n    \"grant_type\": \"authorization_code\",\r\n    \"client_id\": \"C553794A\",\r\n    \"client_secret\": \"ed1eb6c3c78819cc4c7b\",\r\n    \"redirect_uri\": \"$url/digilocker-uri\"\r\n}", //live
      CURLOPT_POSTFIELDS => "{\r\n    \"code\": \"$code\",\r\n    \"grant_type\": \"authorization_code\",\r\n    \"client_id\": \"3278E8B7\",\r\n    \"client_secret\": \"ed073aef1a6fa37847b7\",\r\n    \"redirect_uri\": \"$url/digilocker-uri\"\r\n}", //staging
    //   CURLOPT_POSTFIELDS => "{\r\n    \"code\": \"$code\",\r\n    \"grant_type\": \"authorization_code\",\r\n    \"client_id\": \"AA82AEA3\",\r\n    \"client_secret\": \"f0e9a9d38a223aa1c382\",\r\n    \"redirect_uri\": \"$url/digilocker-uri\"\r\n}", //local
    //   CURLOPT_POSTFIELDS => "{\r\n    \"code\": \"$code\",\r\n    \"grant_type\": \"authorization_code\",\r\n    \"client_id\": \"8DEF912F\",\r\n    \"client_secret\": \"1b2b5d7a522f5f83f49f\",\r\n    \"redirect_uri\": \"$url/digilocker-uri\"\r\n}", //testing
     
      CURLOPT_HTTPHEADER => array(
          "Content-Type: application/json"
        ),
    ));
    
 
    $response = curl_exec($curl);
    $err = curl_error($curl);
    
    curl_close($curl);
    return $response;
    
}

function getIssuedDocuments($token){

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.digitallocker.gov.in/public/oauth2/2/files/issued",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer $token"
      ),
    ));
    
    $response = curl_exec($curl);
    
    curl_close($curl);
    return $response;
    
}

function getDocument($token, $uri, $docType, $studentUniqueNo){

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.digitallocker.gov.in/public/oauth2/1/file/$uri",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer $token"
      ),
    ));
    
    $response = curl_exec($curl);
    
    curl_close($curl);

    $filename = $studentUniqueNo.'_'.$docType.'.pdf';

    $myfile = fopen('storage/app/uploadDocuments/digilocker/'.$filename, "w");
    fwrite($myfile, $response);
    fclose($myfile);
    
    return $filename;
    
}


function getUpoadedDocuments($token){

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.digitallocker.gov.in/public/oauth2/1/files/",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer $token"
      ),
    ));
    
    $response = curl_exec($curl);
    
    curl_close($curl);
    return $response;
    
}



function eMedhabrutiData($barcode){
    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => "http://164.100.141.100/e-Medhabruti_Service/Studentinfomation.asmx/GetStudentInformation",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => "barcode=$barcode",
    CURLOPT_HTTPHEADER => array(
        "Content-Type: application/x-www-form-urlencoded"
    ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}


function labourSync(){
    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => 'http://164.100.140.27/Building/rest/services/eScholarshipStatus',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}