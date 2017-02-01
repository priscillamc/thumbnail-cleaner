<?php
   /**
    * Author:      Kolja Nolte
    * E-mail:      kolja.nolte@gmail.com
    * Website:     www.koljanolte.com
    * Created:     11.06.2015 at 01:43 GMT+7
    */

   /**
    * Stop script when the file is called directly.
    */
   if(!function_exists("add_action")) {
      return;
   }

   /**
    * Retrieves the date of the last performed backup.
    *
    * @param string $format
    *
    * @since 1.0.0
    *
    * @return string
    */
   function thumbnail_cleaner_get_last_backup_date($format = "") {
      $last_backup_date = get_option("thumbnail_cleaner_last_backup_date");
      /** Abort if no backup has been found */
      if(!$last_backup_date) {
         return "";
      }

      /** Accept date format */
      if($format) {
         $last_backup_date = strtotime($last_backup_date);
         $last_backup_date = date($format, $last_backup_date);
      }

      return (string)$last_backup_date;
   }

   /**
    * Prints the date of the last performed backup.
    *
    * @since 1.0.0
    *
    * @param string $format
    */
   function thumbnail_cleaner_the_last_backup_date($format = "") {
      $last_backup_date = thumbnail_cleaner_get_last_backup_date($format);
      if(!$last_backup_date) {
         $last_backup_date = __("Never", "thumbnail_cleaner");
      }

      echo (string)$last_backup_date;
   }

   /**
    * Retrieves all backup files.
    *
    * @since 1.0.0
    *
    * @return array|bool
    */
   function thumbnail_cleaner_get_backups() {
      $wp_content_directory = ABSPATH . "wp-content/";
      $backups_directory    = $wp_content_directory . "backups/thumbnail-cleaner";

      if(!is_dir($backups_directory)) {
         return false;
      }

      /** Fetch all .zip files within the backup directory */
      $files = glob($backups_directory . "/*.zip");
      if(!count($files)) {
         return false;
      }

      $backups = array();

      /** Generate backup metadata */
      foreach((array)$files as $file) {
         $backups[] = array(
            "path"      => $file,
            "url"       => urlencode(get_bloginfo("url") . "/wp-content/backups/thumbnail-cleaner/" . basename($file)),
            "file_size" => filesize($file),
            "file_name" => basename($file)
         );
      }

      return (array)$backups;
   }

   /**
    * Recursively deletes a directory and its content.
    *
    * @since 1.0.0
    *
    * @param $directory
    *
    * @return bool
    */
   function thumbnail_cleaner_rmdir($directory) {
      if(is_dir($directory)) {
         return false;
      }

      $objects = (array)scandir($directory);

      foreach($objects as $object) {
         if($object !== "." && $object !== "..") {
            if(filetype($directory . "/" . $object) === "dir") {
               thumbnail_cleaner_rmdir($directory . "/" . $object);
            }
            else {
               unlink($directory . "/" . $object);
            }
         }
      }

      reset($objects);

      return rmdir($directory);
   }

   /**
    * Returns the path of the upload directory.
    *
    * @since 1.0.0
    *
    * @return array|string
    */
   function thumbnail_cleaner_get_uploads_directory() {
      $uploads_directory = wp_upload_dir();
      $uploads_directory = realpath($uploads_directory["basedir"]);

      return $uploads_directory;
   }