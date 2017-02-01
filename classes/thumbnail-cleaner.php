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
    * Class Thumbnail Cleaner
    */
   class Thumbnail_Cleaner {
      /**
       * @param string $path
       * @param string $file_name
       * @param bool   $overwrite
       *
       * @since 1.0.0
       *
       * @return bool
       */
      public function backup($path = "backups/thumbnail-cleaner/", $file_name = "auto", $overwrite = false) {
         /** Cancel if uploads directory doesn't exist */
         $uploads_directory = thumbnail_cleaner_get_uploads_directory();

         if(!is_dir($uploads_directory) || !extension_loaded("zip")) {
            return false;
         }

         /** Create backups directory if it doesn't exist; cancel script if failed */
         $backups_directory = ABSPATH . "wp-content/" . $path;
         if(!is_dir($backups_directory) && !wp_mkdir_p($backups_directory)) {
            return false;
         }

         /** Build path to zip archive */
         $backup_path = $backups_directory . "/";
         if($file_name === "auto") {
            $timezone = get_option("timezone_string");
            if($timezone && strstr($timezone, "/")) {
               date_default_timezone_set($timezone);
            }
            $backup_path .= date("Y-m-d_H-i") . ".zip";
         }
         else {
            $backup_path .= $file_name;
         }

         /** Overwrite existing file if activated */
         if($overwrite && file_exists($backup_path)) {
            unlink($backup_path);
         }

         $zip = new ZipArchive();
         if(!$zip->open($backup_path, ZipArchive::CREATE)) {
            return false;
         }

         $uploads_directory = str_replace("\\", "/", realpath($uploads_directory));

         if(is_dir($uploads_directory) === true) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploads_directory), RecursiveIteratorIterator::SELF_FIRST);

            foreach($files as $file) {
               $file = str_replace("\\", "/", $file);
               if(in_array(substr($file, strrpos($file, "/") + 1), array(".", ".."), false)) {
                  continue;
               }

               if(is_dir($file) === true) {
                  $zip->addEmptyDir(str_replace($uploads_directory . "/", "", $file));
               }
               else if(is_file($file) === true) {
                  $str1 = str_replace($uploads_directory . "/", "", "/" . $file);
                  $zip->addFromString($str1, file_get_contents($file));
               }
            }
         }
         else if(is_file($uploads_directory) === true) {
            $zip->addFromString(basename($uploads_directory), file_get_contents($uploads_directory));
         }

         $zip->close();

         /** Stop if the archive has not been successfully created */
         if(!file_exists($backup_path)) {
            return false;
         }

         $index_file = glob($backups_directory . "/index.php");
         /** Create index.php file if it does not yet exist */
         if(!$index_file) {
            $handle = fopen($backups_directory . "/index.php", "a+");
            fwrite($handle, "<?php /** Nothing to see her; just protecting your privacy. */");
            fclose($handle);
         }

         /** Define output array */
         $output = array(
            "url"       => home_url() . "/wp-content/backups/thumbnail-cleaner/" . basename($backup_path),
            "path"      => $backup_path,
            "file_size" => filesize($backup_path),
            "file_name" => basename($backup_path)
         );

         return (array)$output;
      }

      /**
       * Deletes all backup files within the
       * wp-content/backups/thumbnail-cleaner/ directory.
       *
       * @since 1.0.0
       *
       * @return int
       */
      public function delete_backups() {
         $counter = 0;
         $backups = thumbnail_cleaner_get_backups();

         foreach((array)$backups as $backup) {
            if(file_exists($backup["path"]) && unlink($backup["path"])) {
               $counter++;
            }
         }

         return (int)$counter;
      }

      /**
       * Analyzes the uploads directory and - on success - returns
       * an array containing further information.
       *
       * @since 1.0.0
       *
       * @param array $options
       *
       * @return array
       */
      public function analyze(array $options = array()) {
         /** Creates a default array overwritten with user set options if they exist */
         $default_options = array(
            "before_date" => ""
         );

         $options      = array_merge($default_options, $options);
         $uploads_path = thumbnail_cleaner_get_uploads_directory();

         /** Cancel if uploads directory does not exist */
         if(!is_dir($uploads_path)) {
            return false;
         }

         $output = array(
            "original"             => array(),
            "thumbnails"           => array(),
            "originals_file_size"  => 0,
            "thumbnails_file_size" => 0,
            "total_file_size"      => 0
         );

         $query = array(
            "post_type"      => "attachment",
            "post_mime_type" => "image/jpeg",
            "showposts"      => -1
         );

         if($options["before_date"]) {
            $query["date_query"] = array(
               "before" => $options["before_date"]
            );
         }

         $attachments    = get_posts($query);
         $original_files = array();
         $ignored_files  = array();

         foreach((array)$attachments as $attachment) {
            $upload_directory = wp_upload_dir(get_the_date("Y/m", $attachment->ID));
            $path             = $upload_directory["path"] . "/" . basename($attachment->guid);
            $path             = realpath($path);

            if(!file_is_valid_image($path)) {
               $ignored_files[] = $path;
               continue;
            }

            $this_file_size       = filesize($path);
            $output["original"][] = array(
               "path"      => $path,
               "url"       => $attachment->guid,
               "file_size" => $this_file_size,
               "file_name" => basename($attachment->guid)
            );

            $output["originals_file_size"] += $this_file_size;
            $output["total_file_size"] += $this_file_size;

            $original_files[] = $path;
         }

         $iterator = new RecursiveDirectoryIterator($uploads_path);

         foreach(new RecursiveIteratorIterator($iterator) as $file) {
            $file_path = realpath($file->getPathname());
            $file_name = $file->getFilename();
            $file_size = $file->getSize();

            /** Skip file if it's not a valid image or among the original/ignored files */
            if(!file_is_valid_image($file_path) || in_array($file_path, $original_files, true) || in_array($file_path, $ignored_files, true)) {
               continue;
            }

            /** Skip file if it's not a thumbnail */
            if(!preg_match("'[0-9]x'", $file_path)) {
               continue;
            }

            $output["thumbnails"][] = array(
               "path"      => $file_path,
               "file_size" => $file_size,
               "file_name" => $file_name
            );

            $output["thumbnails_file_size"] += $file_size;
            $output["total_file_size"] += $file_size;
         }

         return $output;
      }

      /**
       * Main function; deletes all thumbnails stored
       * in the uploads directory.
       *
       * @since 1.0.0
       *
       * @return int
       */
      public function clean_thumbnails(array $options = array()) {
         /** Creates a default array overwritten with user set options if they exist */
         $default_options = array(
            "before_date" => ""
         );

         $options            = array_merge($default_options, $options);
         $cleaned_thumbnails = 0;
         $thumbnails         = (array)$this->analyze($options);

         foreach((array)$thumbnails["thumbnails"] as $thumbnail) {
            if(unlink($thumbnail["path"])) {
               $cleaned_thumbnails++;
            }
         }

         return $cleaned_thumbnails;
      }

      /**
       * @param string $file_name
       *
       * @param bool   $delete_on_success
       *
       * @since 1.0.0
       *
       * @return bool
       */
      public function restore($file_name = "", $delete_on_success = false) {
         $backups = thumbnail_cleaner_get_backups();
         if(!$backups || !extension_loaded("zip")) {
            return false;
         }

         $success           = false;
         $uploads_directory = wp_upload_dir();

         foreach((array)$backups as $backup) {
            if($backup["file_name"] === $file_name) {
               thumbnail_cleaner_rmdir($uploads_directory["basedir"]);

               $zip = new ZipArchive();
               if(!$zip->open(ABSPATH . "wp-content/backups/thumbnail-cleaner/$file_name", ZipArchive::CREATE)) {
                  return false;
               }

               if($zip->extractTo(thumbnail_cleaner_get_uploads_directory())) {
                  if($delete_on_success) {
                     unlink($backup["path"]);
                  }
                  $success = true;
                  break;
               }
            }
         }

         return (bool)$success;
      }
   }