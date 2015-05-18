#! /usr/bin/php
<?php

include 'blfs-include.php';

$CHAPTER       = '26';
$START_PACKAGE ='fluxbox';
$STOP_PACKAGE  ='sawfish';

$renames = array();
$renames[ 'sawfish_' ] = 'sawfish';

$ignores = array();

$regex = array();
$regex[ 'fluxbox' ] = "/^.*current version of Fluxbox is (\d[\d\.]+\d).*$/";
$regex[ 'icewm'   ] = "/^.*Download icewm-(\d[\d\.]+\d).*$/";

//$current="sawfish_";

$url_fix = array (

   array( 'pkg'     => 'fluxbox',
          'match'   => '^.*$', 
          'replace' => "http://www.fluxbox.org/download" ),

   array( 'pkg'     => 'icewm',
          'match'   => '^.*$', 
          'replace' => "http://sourceforge.net/projects/icewm/files" ),

   array( 'pkg'     => 'openbox',
          'match'   => '^.*$', 
          'replace' => "http://pkgs.fedoraproject.org/repo/pkgs/openbox" ),

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
    // Get listing
    $lines = http_get_file( "$dirpath/" );
  }
  else // http
  {
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

  // Most packages are in the form $package-n.n.n
  // Occasionally there are dashes (e.g. 201-1)

  $max = find_max( $lines, "/$package/", "/^.*${package}-?([\d\.]*\d)\.tar.*$/" );
  return $max;
}

Function get_pattern( $line )
{
   global $start;

   // Set up specific patter matches for extracting book versions
   $match = array();

   $match = array(
     //array( 'pkg'   => 'at-spi', 
     //       'regex' => "/^.*at-spi2-.{3,4}-(\d[\d\.]+).*$/" ),
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

// Get latest version for each package 
foreach ( $book as $pkg => $data )
{
   $book_index = $pkg; 

   $base = $data[ 'basename' ];
   $url  = $data[ 'url' ];
   $bver = $data[ 'version' ];

   echo "book index: $book_index $bver $url \n";

   $v = get_packages( $book_index, $url );

   $vers[ $book_index ] = $v;
}

html();  // Write html output
?>
