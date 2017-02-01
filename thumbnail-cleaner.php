<?php
   /**
    * Plugin Name:  Thumbnail Cleaner
    * Plugin URI:   http://www.koljanolte.com/wordpress/plugins/thumbnail-cleaner/
    * Description:  Cleans up your WordPress installation by removing and regenerating thumbnails.
    * Version:      1.4.1
    * Author:       Kolja Nolte
    * Author URI:   http://www.koljanolte.com
    * License:      GPLv2 or later
    * License URI:  http://www.gnu.org/licenses/gpl-2.0.html
    */

   /**
    * Stop script when the file is called directly.
    */
   if(!function_exists("add_action")) {
      return false;
   }

   define("THUMBNAIL_CLEANER_VERSION", "1.4.1");

   /**
    * Includes all files from the "includes" directory.
    */
   $include_directories = array(
      "admin",
      "classes",
      "includes"
   );

   foreach((array)$include_directories as $include_directory) {
      $directory_path = plugin_dir_path(__FILE__) . "/$include_directory";

      if(!is_dir($directory_path)) {
         continue;
      }

      $include_files = glob("$directory_path/*.php");
      foreach((array)$include_files as $include_file) {
         if(!is_file($include_file)) {
            continue;
         }

         include_once($include_file);
      }
   }

   /**
    * Loads translation file.
    */
   function thumbnail_cleaner_translations() {
      load_plugin_textdomain("thumbnail_cleaner", false, dirname(plugin_basename(__FILE__)) . "/languages");
   }

   add_action("plugins_loaded", "thumbnail_cleaner_translations");