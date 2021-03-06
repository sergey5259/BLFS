#! /usr/bin/php
<?php

include 'blfs-include.php';

$CHAPTER       = '10';
$CHAPTERS      = 'Chapter 10';
$START_PACKAGE = 'aalib';
$STOP_PACKAGE  = 'qpdf';

$renames = array();
$ignores = array();

//$current="gegl";   // For debugging

$regex = array();
$regex[ 'aalib'         ] = "/^.*Download aalib-([\d\.]+rc\d).tar.*$/";
$regex[ 'libexif'       ] = "/^.*Download libexif-(\d[\d\.]+\d).*$/";
//$regex[ 'graphite2'     ] = "/^.*Download graphite-(\d[\d\.]+\d).tgz.*$/";
$regex[ 'libmng'        ] = "/^.*Download libmng-(\d[\d\.]+\d).tar.*$/";
$regex[ 'libpng'        ] = "/^.*Download libpng-(\d[\d\.]+\d).tar.*$/";
$regex[ 'LibRaw'        ] = "/^.*LibRaw-(\d[\d\.]+\d).tar.*$/";
$regex[ 'openjpeg1'     ] = "/^.*Download openjpeg-([\d\.]+\d).*$/";
$regex[ 'poppler'       ] = "/^.*poppler-([\d\.]+\d).tar.*$/";
$regex[ 'popplerdata'   ] = "/^.*poppler-data([\d\.]+\d).tar.*$/";
$regex[ 'qpdf'          ] = "/^.*Download qpdf-([\d\.]+\d).tar.*$/";

$sf = 'sourceforge.net';

$url_fix = array (

 array( //'pkg'     => 'gnome',
        'match'   => '^ftp:\/\/ftp.gnome', 
        'replace' => "http://ftp.gnome" ),

 array( 'pkg'     => 'aalib',
        'match'   => '^.*$', 
        'replace' => "http://$sf/projects/aa-project/files" ),

 array( 'pkg'     => 'freetype',
        'match'   => '^.*$', 
        'replace' => "http://sourceforge.net/projects/freetype/files/freetype2" ),

 array( 'pkg'     => 'freetype-doc',
        'match'   => '^.*$', 
        'replace' => "http://$sf/projects/freetype/files/freetype-docs" ),

 array( 'pkg'     => 'giflib',
        'match'   => '^.*$', 
        'replace' => "http://$sf/projects/giflib/files/giflib-5.x" ),

 array( 'pkg'     => 'imlib2',
        'match'   => '^.*$', 
        'replace' => "http://$sf/projects/enlightenment/files/imlib2-src" ),

 array( 'pkg'     => 'jasper',
        'match'   => '^.*$', 
        'replace' => "http://pkgs.fedoraproject.org/repo/pkgs/jasper" ),

 array( 'pkg'     => 'lcms',
        'match'   => '^.*$', 
        'replace' => "http://$sf/projects/lcms/files/lcms" ),

 array( 'pkg'     => 'lcms2',
        'match'   => '^.*$', 
        'replace' => "http://$sf/projects/lcms/files/lcms" ),

 array( 'pkg'     => 'libexif',
        'match'   => '^.*$', 
        'replace' => "http://$sf/projects/libexif/files/libexif" ),

 array( 'pkg'     => 'libjpeg-turbo',
        'match'   => '^.*$', 
        'replace' => "http://$sf/projects/libjpeg-turbo/files" ),

 array( 'pkg'     => 'graphite2',
        'match'   => '^.*$', 
        'replace' => "https://sourceforge.net/projects/silgraphite/files/graphite2/" ),

 array( 'pkg'     => 'libmng',
        'match'   => '^.*$', 
        'replace' => "http://$sf/projects/libmng/files" ),

 array( 'pkg'     => 'libpng',
        'match'   => '^.*$', 
        'replace' => "http://$sf/projects/libpng/files" ),

 array( 'pkg'     => 'libwebp',
        'match'   => '^.*$', 
        'replace' => "http://downloads.webmproject.org/releases/webp/index.html" ),

 array( 'pkg'     => 'qpdf',
        'match'   => '^.*$', 
        'replace' => "http://$sf/projects/qpdf/files" ),

 array( 'pkg'     => 'LibRaw',
        'match'   => '^.*$', 
        'replace' => "http://www.libraw.org/download" ),

 array( 'pkg'     => 'exiv2',
        'match'   => '^.*$', 
        'replace' => "http://pkgs.fedoraproject.org/repo/pkgs/exiv2" ),

 array( 'pkg'     => 'opencv',
        'match'   => '^.*$', 
        'replace' => "http://sourceforge.net/projects/opencvlibrary/files/opencv-unix" ),

 array( 'pkg'     => 'openjpeg',
        'match'   => '^.*$', 
        'replace' => "http://sourceforge.net/projects/openjpeg.mirror/files" ),

 array( 'pkg'     => 'openjpeg1',
        'match'   => '^.*$', 
        'replace' => "http://sourceforge.net/projects/openjpeg.mirror/files" ),

 array( 'pkg'     => 'ijs',
        'match'   => '^.*$', 
        'replace' => "https://www.openprinting.org/download/ijs/download/" ),

);

function get_packages( $package, $dirpath )
{
  global $regex;
  global $book_index;
  global $url_fix;
  global $current;

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
    if ( $book_index == "libart_lgpl" )
    {
       // Get the max directory and adjust the directory path
      $dirpath  = rtrim  ( $dirpath, "/" );    // Trim any trailing slash
      $position = strrpos( $dirpath, "/" );
      $dirpath  = substr ( $dirpath, 0, $position );
      $lines = http_get_file( $dirpath );
      $dir = find_max( $lines, "/\d[\d\.]+/", "/(\d[\d\.]+)/" );
      $dirpath .= "/$dir/";
    }

    // Get listing
    //if ( $book_index == "tiff" ) { $dirpath .= "/"; }
    $lines = http_get_file( "$dirpath/" );
  }
  else // http
  {
    // glib type packages
    if ( $book_index == "librsvg" )
    {
      // Parent listing
      $dirpath  = rtrim  ( $dirpath, "/" );    // Trim any trailing slash
      $position = strrpos( $dirpath, "/" );
      $dirpath  = substr ( $dirpath, 0, $position ); // Up one
      $lines = http_get_file( $dirpath );
      $dir      = find_even_max( $lines, '/^\s+[\d\.]+\//', '/^\s+([\d\.]+)\/.*$/' );
      $dirpath .= "/$dir/";
    }

    // babl and similar
    if ( $book_index == "babl"  ||
         $book_index == "gegl"  )
    {
       // Get the max directory and adjust the directory path
      $dirpath  = rtrim  ( $dirpath, "/" );    // Trim any trailing slash
      $position = strrpos( $dirpath, "/" );
      $dirpath  = substr ( $dirpath, 0, $position );
      $lines = http_get_file( $dirpath );
      $dir = find_max( $lines, "/\d[\d\.]+\//", "/^.*(\d[\d\.]+)\/.*$/" );
      $dirpath .= "/$dir/";
    }

    // Customize http directories as needed
    $lines = http_get_file( $dirpath );
    if ( ! is_array( $lines ) ) return $lines;
  } // End fetch

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

  if ( $book_index == "freetype"     ||
       $book_index == "freetype-doc" )
  {
    $dir   = find_max( $lines, '/\d\./', '/^\s*([\d\.]+)\s*$/' );
    $lines = http_get_file( "$dirpath/$dir" );
  }

  if ( $book_index == "graphite2" )
  {
    return find_max( $lines, '/graphite2-/', '/^.*graphite2-(\d\.[\d\.]+)\.tgz.*$/' );
  }

  if ( $book_index == "libjpeg-turbo" )
  {
    return find_max( $lines, '/\d\./', '/^\s*([\d\.]+)\s*$/' );
  }

  if ( $book_index == "jasper" )
  {
    return find_max( $lines, '/jasper-/', '/^.*jasper-(\d\.[\d\.]+)\.zip.*$/' );
  }

  if ( $book_index == "openjpeg" )
  {
    return find_max( $lines, '/^\s*1[\d\.]/', '/^.*(1\.[\d\.]+).*$/' );
  }

  // imlib
  if ( $book_index == "imlib2" )
  {
    $dir   = find_max( $lines, '/^\s*[\d\.]+\s*$/', '/^\s*([\d\.]+)\s*$/' );
    $lines = http_get_file( "$dirpath/$dir" );
    return find_max( $lines, "/imlib/", "/^.*$package-([\d\.]*\d).tar.*$/" );
  }

  // lcms (actually lcms 1.xx)
  if ( $book_index == "lcms" )
  {
    return find_max( $lines, '/1\.[\d\.]+/', '/^\s*(1[\d\.]+)\s*$/' );
  }

  if ( $book_index == "opencv" )
  {
    return find_max( $lines, '/\d\.[\d\.]+/', '/^\s*(\d\.[\d\.]+)\s*$/' );
  }

  // lcms2
  if ( $book_index == "lcms2" )
  {
    return find_max( $lines, '/2\.[\d\.]+/', '/^\s*(2[\d\.]+)\s*$/' );
  }

  if ( $package == "graphite2" ) $package = "graphite";

  // Most packages are in the form $package-n.n.n
  // Occasionally there are dashes (e.g. 201-1)
  $max = find_max( $lines, "/$package/", "/^.*$package-([\d\.]*\d).tar.*$/", TRUE );
  return $max;
}

Function get_pattern( $line )
{
   // Set up specific patter matches for extracting book versions
   $match = array();

   $match = array(
      array( 'pkg'   => 'libatomic_ops', 
             'regex' => "/\D*(\d.*\d[a-z]{0,1})\D*$/" ),

      array( 'pkg'   => 'lcms2', 
             'regex' => "/\D*lcms2-([\d\.]+)\D*$/" ),

      array( 'pkg'   => 'exiv2', 
             'regex' => "/\D*exiv2-([\d\.]+)\D*$/" ),

      array( 'pkg'   => 'imlib2', 
             'regex' => "/\D*imlib2-([\d\.]+)\D*$/" ),

      array( 'pkg'   => 'graphite2', 
             'regex' => "/\D*graphite2-([\d\.]+)\D*$/" ),
   );

   foreach( $match as $m )
   {
      $pkg = $m[ 'pkg' ];
      if ( preg_match( "/$pkg/", $line ) ) 
         return $m[ 'regex' ];
   }

   return "/\D*(\d.*\d)\D*$/";
}

get_current();  // Get what is in the book

$start = false;

// Get latest version for each package 
foreach ( $book as $pkg => $data )
{
   $book_index = $pkg; 

   $base = $data[ 'basename' ];
   $url  = $data[ 'url' ];
   $bver = $data[ 'version' ];

   echo "book index: $book_index $bver $url\n";

   $v = get_packages( $book_index, $url );

   $vers[ $book_index ] = $v;
}

html();  // Write html output
?>
