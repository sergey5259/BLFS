#! /usr/bin/php
<?php

$CHAPTER       = 25;
$START_PACKAGE ='atk';
$STOP_PACKAGE  ='webkitgtk1';
$start         = false;

$freedesk = "http://xorg.freedesktop.org/releases/individual";
$sf       = 'sourceforge.net';

$book = array();
$book_index = 0;

$vers = array();

date_default_timezone_set( "GMT" );
$date = date( "Y-m-d (D) H:i:s" );

// Special cases
$exceptions = array();

$regex = array();
$regex[ 'agg'      ] = "/^.*agg-(\d[\d\.]+\d).tar.*$/";
$regex[ 'freeglut' ] = "/^.*Download freeglut-(\d[\d\.]+\d).tar.*$/";

//$current="goffice";

$url_fix = array (

   array( 'pkg'     => 'agg',
          'match'   => '^.*$', 
          'replace' => "http://www.antigrain.com/download/index.html" ),

   array( 'pkg'     => 'clutter-gst',
          'match'   => '^.*$', 
          'replace' => "http://ftp.gnome.org/pub/gnome/sources/clutter-gst/2.0" ),

   array( 'pkg'     => 'freeglut',
          'match'   => '^.*$', 
          'replace' => "http://$sf/projects/freeglut/files" ),

   array( 'pkg'     => 'hicolor-icon-theme',
          'match'   => '^.*$', 
          'replace' => "http://icon-theme.freedesktop.org/releases" ),

   array( 'pkg'     => 'imlib2',
          'match'   => '^.*$', 
          'replace' => "http://$sf/projects/enlightenment/files/imlib2-src" ),
);
//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
function find_max( $lines, $regex_match, $regex_replace )
{
  global $book_index;
  $a = array();
  foreach ( $lines as $line )
  {
     // Ensure we skip verbosity of NcFTP
     if ( ! preg_match( $regex_match,   $line ) ) continue; 
     if (   preg_match( "/NcFTP/",      $line ) ) continue;
     if (   preg_match( "/Connecting/", $line ) ) continue;
     if (   preg_match( "/Current/",    $line ) ) continue;

     // Isolate the version and put in an array
     $slice = preg_replace( $regex_replace, "$1", $line );

     // Numbers and whitespace
     if ( "x$slice" == "x$line" && ! preg_match( "/^\d[\d\.P-]*$/", $slice ) ) continue; 

     // Skip minor versions in the 90s (most of the time)
     list( $major, $minor, $micro, $rest ) = explode( ".", $slice . ".0.0.0.0" );
     if ( $micro >= 90 ) continue;

     array_push( $a, $slice );     
  }

  // SORT_NATURAL requires php-5.4.0 or later
  rsort( $a, SORT_NATURAL );  // Max version is at the top
  return ( isset( $a[0] ) ) ? $a[0] : 0;
}
//----------------------------------------------------------
function find_even_max( $lines, $regex_match, $regex_replace )
{
  $a = array();
  foreach ( $lines as $line )
  {
     if ( ! preg_match( $regex_match, $line ) ) continue; 
     
     // Isolate the version and put in an array
     $slice = preg_replace( $regex_replace, "$1", $line );
//echo "line=$line\n";
     if ( "x$slice" == "x$line" && ! preg_match( "/^[\d\.]+/", $slice ) ) continue; 
//echo "A\n";
     // Skip odd numbered minor versions
     list( $major, $minor, $rest ) = explode( ".", $slice . ".0" );
     if ( $minor % 2 == 1  ) continue;
//echo "B\n";
     if ( $minor     >= 90 ) continue;
//echo "C\n";
     array_push( $a, $slice );     
  }

  // SORT_NATURAL requires php-5.4.0 or later
  rsort( $a, SORT_NATURAL );  // Max version is at the top
  return ( isset( $a[0] ) ) ? $a[0] : 0;
}
//===========================================
function http_get_file( $url )
{
  exec( "curl -L -s -m30 $url", $dir );
  $s   = implode( "\n", $dir );
  $dir = strip_tags( $s );
  return explode( "\n", $dir );
}
//=====================================================
function max_parent( $dirpath, $prefix )
{
  // First, remove a directory
  $dirpath  = rtrim  ( $dirpath, "/" );    // Trim any trailing slash
  $position = strrpos( $dirpath, "/" );
  $dirpath  = substr ( $dirpath, 0, $position );

  $lines = http_get_file( $dirpath );

  $regex_match   = "#${prefix}[\d\.]+/#";
  $regex_replace = "#^(${prefix}[\d\.]+)/.*$#";
  $max           = find_max( $lines, $regex_match, $regex_replace );

  return "$dirpath/$max"; 
}
/////////////////////////////////////////////////////////////////
function get_packages( $package, $dirpath )
{
  global $exceptions;
  global $regex;
  global $book_index;
  global $url_fix;
  global $current;
  global $freedesk;

  if ( isset( $current ) && $book_index != "$current" ) return 0;

  // Fix up directory path
  foreach ( $url_fix as $u )
  {
     $replace = $u[ 'replace' ];
     $match   = $u[ 'match'   ];

     if ( isset( $u[ 'pkg' ] ) )
     {
        if ( $package == $u[ 'pkg' ] )
        {
           $dirpath = preg_replace( "/$match/", "$replace", $dirpath );
           break;
        }
     }
     else
     {
        if ( preg_match( "/$match/", $dirpath ) )
        {
           $dirpath = preg_replace( "/$match/", "$replace", $dirpath );
           break;
        }
     }
  }

  // Check for ftp
  if ( preg_match( "/^ftp/", $dirpath ) ) 
  { 
    // glib type packages
    if ( $book_index == "atk"          ||
         $book_index == "atkmm"        ||
         $book_index == "at-spi2-core" ||
         $book_index == "at-spi2-atk"  ||
         $book_index == "cogl"         ||
         $book_index == "clutter"      ||
         //$book_index == "clutter-gst"  ||
         $book_index == "clutter-gtk"  ||
         $book_index == "gdk-pixbuf"   ||
         $book_index == "gtk+1"        ||
         $book_index == "gtk-engines"  ||
         $book_index == "libglade"     ||
         $book_index == "pango"        ||
         $book_index == "pangomm"      ||
         $book_index == "gtkmm1"       ||
         $book_index == "goffice1"      )
    {
      // Parent listing
      $dirpath  = rtrim  ( $dirpath, "/" );    // Trim any trailing slash
      $position = strrpos( $dirpath, "/" );
      $dirpath  = substr ( $dirpath, 0, $position );
      exec( "echo 'ls -1;bye' | ncftp $dirpath", $dirlines );
      if ( $book_index == "gdk-pixbuf" )
        $dir      = find_max( $dirlines, '/^[\d\.]+$/', '/^([\d\.]+)$/' );
      else
        $dir      = find_even_max( $dirlines, '/^[\d\.]+$/', '/^([\d\.]+)$/' );
      $dirpath .= "/$dir/";
    }
 
    if ( $book_index == "goffice" ||
         $book_index == "gtk+"    || 
         $book_index == "gtkmm"    )
      $dirpath .= "/";

    if ( $book_index == "libnotify"     ||
         $book_index == "libxklavier"   ||
         $book_index == "pangox-compat"  )
    {
      $dirpath  = rtrim  ( $dirpath, "/" );    // Trim any trailing slash
      $position = strrpos( $dirpath, "/" );
      $dirpath  = substr ( $dirpath, 0, $position );
      exec( "echo 'ls -1;bye' | ncftp $dirpath", $dirlines );

      $dir      = find_max( $dirlines, '/^[\d\.]+$/', '/^([\d\.]+)$/' );
      $dirpath .= "/$dir/";
    }

    if ( $book_index == "firefox" )
    {
      // Customize http directories as needed
      $dirpath  = rtrim  ( $dirpath, "/" );    // Trim any trailing slash
      $position = strrpos( $dirpath, "/" );
      $dirpath  = substr ( $dirpath, 0, $position ); // Up 1
      $position = strrpos( $dirpath, "/" );
      $dirpath  = substr ( $dirpath, 0, $position ); // Up 2
    }

    // Get listing
    exec( "echo 'ls -1;bye' | ncftp $dirpath", $lines );
  }
  else // http
  {
    if ( $book_index == "qt-everywhere-opensource-src1" )
    {
      // Customize http directories as needed
      $dirpath  = rtrim  ( $dirpath, "/" );    // Trim any trailing slash
      $position = strrpos( $dirpath, "/" );
      $dirpath  = substr ( $dirpath, 0, $position ); // Up 1
      $position = strrpos( $dirpath, "/" );
      $dirpath  = substr ( $dirpath, 0, $position ); // Up 2
      $position = strrpos( $dirpath, "/" );
      $dirpath  = substr ( $dirpath, 0, $position ); // Up 3
      $dirlines = http_get_file( $dirpath );
      $dir      = find_max( $dirlines, '/^[\d\.]+.*$/', '/^([\d\.]+).*$/' );
      $dirpath .= "/$dir";
      $dirlines = http_get_file( "$dirpath" );
      $dir      = find_max( $dirlines, '/^[\d\.]+.*$/', '/^([\d\.]+).*$/' );
      $dirpath .= "/$dir/single";
    }

    if ( $book_index == "fltk" )
    {
      $dirpath  = rtrim  ( $dirpath, "/" );    // Trim any trailing slash
      $position = strrpos( $dirpath, "/" );
      $dirpath  = substr ( $dirpath, 0, $position ); // Up 1
//echo "dirpath=$dirpath\n";
    }

    $lines = http_get_file( $dirpath );

    if ( ! is_array( $lines ) ) return $lines;
  } // End fetch

//print_r($lines);
  if ( isset( $regex[ $package ] ) )
  {
     // Custom search for latest package name
     foreach ( $lines as $l )
     {
        if ( preg_match( '/^\h*$/', $l ) ) continue;
        $ver = preg_replace( $regex[ $package ], "$1", $l );
        if ( $ver == $l ) continue;

        return $ver;  // Return first match of regex
     }

     return 0;  // This is an error
  }

  if ( $book_index == "goffice1" )
    $package = "goffice";

  if ( $book_index == "gtk+"  ||
       $book_index == "gtk+1" )
    return find_max( $lines, '/gtk/', '/^.*gtk.-([\d\.]*\d)\.tar.*$/' );

  if ( $book_index == "gtkmm1" )
    $package = "gtkmm";

  if ( $book_index == "imlib2" )
    return find_max( $lines, '/\d\.[\d\.]+\d/', '/^.* (\d\.[\d\.]+\d).*$/' );

  if ( $book_index == "webkitgtk" )
    return find_even_max( $lines, '/webkitgtk-2.4/', '/^.*-(\d[\d\.]+\d).tar.*$/' );

  if ( $book_index == "webkitgtk1" )
    return find_even_max( $lines, '/webkitgtk/', '/^.*webkitgtk-(\d[\d\.]+\d).tar.*$/' );

  if ( $book_index == "cairo"    || 
       $book_index == "cairomm"  )
    return find_even_max( $lines, "/$package/", "/^$package-(\d[\d\.]+\d).tar.*$/" );

  if ( $book_index == "firefox" )
    return find_max( $lines, "/^\d/", "/^(\d{2}.\d+)$/" );

  if ( $book_index == "qt-everywhere-opensource-src1" )
    return find_max( $lines, "/src.*tar.xz/", "/^.*src-([\d\.]+).tar.*$/" );

  if ( $book_index == "fltk" )
    return find_max( $lines, "/1\./", "/^\s+(1\.[\d\.]+).*$/" );

  // Most packages are in the form $package-n.n.n
  // Occasionally there are dashes (e.g. 201-1)
  $max = find_max( $lines, "/$package/", "/^.*$package-([\d\.]*\d)\.tar.*$/" );
  return $max;
}
//********************************************************
Function get_pattern( $line )
{
   global $start;

   // Set up specific patter matches for extracting book versions
   $match = array();

   $match = array(
     array( 'pkg'   => 'at-spi', 
            'regex' => "/^.*at-spi2-.{3,4}-(\d[\d\.]+).*$/" ),
     
     array( 'pkg'   => 'imlib2', 
            'regex' => "/^.*imlib2-(\d[\d\.]+).*$/" ),
   );

   foreach( $match as $m )
   {
      $pkg = $m[ 'pkg' ];

      if ( preg_match( "/$pkg/", $line ) ) 
         return $m[ 'regex' ];
   }

   return "/\D*(\d.*\d)\D*$/";
}

function get_current()
{
   global $vers;
   global $book;
   global $freedesk;
   global $START_PACKAGE;
   global $STOP_PACKAGE;
   global $start;

   $wget_file = "/home/bdubbs/public_html/blfs-book-xsl/wget-list";
   $xorg_dir  = "/home/bdubbs/BLFS/BOOK/x/installing";

   $contents = file_get_contents( $wget_file );
   $wget  = explode( "\n", $contents );

   foreach ( $wget as $line )
   {
      if ( $line == "" ) continue;

      $file = basename( $line );
      $url  = dirname ( $line );
      $file = preg_replace( "/\.tar\..z.*$/", "", $file ); // Remove .tar.?z*$
      $file = preg_replace( "/\.tar$/",       "", $file ); // Remove .tar$
      $file = preg_replace( "/\.gz$/",        "", $file ); // Remove .gz$
      $file = preg_replace( "/\.orig$/",      "", $file ); // Remove .orig$
      $file = preg_replace( "/\.src$/",       "", $file ); // Remove .src$
      $file = preg_replace( "/\.tgz$/",       "", $file ); // Remove .tgz$

      if ( preg_match( "/patch$/", $file         ) ) continue; // Skip patches

      $pattern = get_pattern( $line );
      
      $version = preg_replace( $pattern, "$1", $file );   // Isolate version
      $version = preg_replace( "/^-/", "", $version );    // Remove leading #-

      //if ( preg_match( "/^xf/", $file ))
      //   $basename = preg_replace( "/^(.+\-).*$/", "$1", $file );
      //else 
         $basename = strstr( $file, $version, true );
      
      $basename = rtrim( $basename, "-" );

      if ( $basename == $START_PACKAGE ) $start = true;
      if ( ! $start ) continue;


      $index = $basename;
      while ( isset( $book[ $index ] ) ) $index .= "1";
      
      $book[ $index ] = array( 'basename' => $basename,
                               'url'      => $url, 
                               'version'  => $version );

      if ( preg_match( "/$STOP_PACKAGE/", $line ) ) break;
   }
}

function html()
{
   global $CHAPTER;
   global $book;
   global $date;
   global $vers;

   $leftnav = file_get_contents( 'leftnav.html' );

   $f = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN'
                      'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
<head>
<title>BLFS Chapter $CHAPTER Package Currency Check - $date</title>
<link rel='stylesheet' href='currency.css' type='text/css' />
</head>
<body>
$leftnav
<h1>BLFS Chapter $CHAPTER Package Currency Check</h1>
<h2>As of $date GMT</h2>

<table>
<tr><th>BLFS Package</th> <th>BLFS Version</th> <th>Latest</th> <th>Flag</th></tr>\n";

   // Get the latest version of each package
   foreach ( $vers as $pkg => $v )
   {
      $v    = $book[ $pkg ][ 'version' ];  // book version
      $cv   = $vers[ $pkg ];               // web version
      $flag = ( "x$cv" != "x$v" ) ? "*" : "";

      if ( $v == "" ) $vers[ $pkg ] = "";
  
      $name = $pkg;
      if ( $pkg == "goffice"                       ) $name = 'goffice (gtk+2)';
      if ( $pkg == "goffice1"                      ) $name = 'goffice (gtk+3)';
      if ( $pkg == "gtk+"                          ) $name = 'gtk+2';
      if ( $pkg == "gtk+1"                         ) $name = 'gtk+3';
      if ( $pkg == "gtkmm"                         ) $name = 'gtkmm2';
      if ( $pkg == "gtkmm1"                        ) $name = 'gtkmm3';
      if ( $pkg == "qt-everywhere-opensource-src"  ) $name = 'qt4';
      if ( $pkg == "qt-everywhere-opensource-src1" ) $name = 'qt5';
      if ( $pkg == "webkitgtk"                     ) $name = 'webkitgtk-2.4';
      if ( $pkg == "webkitgtk1"                    ) $name = 'webkitgtk-2.6';


      $classtype = isset( $book[ $pkg ][ 'indent' ] ) ? "indent" : "";

      $f .= "<tr><td class='$classtype'>$name</td>";
      $f .= "<td>$v</td>";
      $f .= "<td>${vers[ $pkg ]}</td>";
      $f .= "<td class='center'>$flag</td></tr>\n";
   }

   $f .= "</table>
</body>
</html>\n";

   file_put_contents( "/home/bdubbs/public_html/chapter$CHAPTER.html", $f );
}

get_current();  // Get what is in the book

// Get latest version for each package 
foreach ( $book as $pkg => $data )
{
   $book_index = $pkg; 

   if ( $book_index == $START_PACKAGE ) $start = true;
   if ( ! $start ) continue;

   // Skip things we don't want
   //if ( preg_match( "/rpcnis-headers/", $pkg ) ) continue;

   $base = $data[ 'basename' ];
   $url  = $data[ 'url' ];
   $bver = $data[ 'version' ];

   echo "book index: $book_index  bver=$bver url=$url \n";

   $v = get_packages( $book_index, $url );

   $vers[ $book_index ] = $v;

   // Stop at the end of the chapter 
   if ( $book_index == $STOP_PACKAGE ) break; 
}

html();  // Write html output
?>