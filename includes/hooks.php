<?php
   /**
    * Author:      Kolja Nolte
    * E-mail:      kolja.nolte@gmail.com
    * Website:     www.koljanolte.com
    * Created:     11.06.2015 at 01:44 GMT+7
    */

   /**
    * Stop script when the file is called directly.
    */
   if(!function_exists("add_action")) {
      return;
   }

   /**
    * Loads .css and .js files.
    *
    * @since 1.0.0
    */
   function thumbnail_cleaner_scripts_and_styles() {
      $root = plugin_dir_url(dirname(__FILE__));

      /** Styles */

      wp_enqueue_style(
         "thumbnail-cleaner-styles-admin",
         "$root/styles/admin.css",
         array(),
         THUMBNAIL_CLEANER_VERSION
      );

      /** Fonts */

      wp_enqueue_style(
         "thumbnail-cleaner-font-awesome",
         "$root/fonts/font-awesome/css/font-awesome.min.css",
         array(),
         "4.6.3"
      );

      /** Scripts */

      wp_enqueue_script(
         "thumbnail-cleaner-scripts-admin",
         "$root/scripts/admin.js",
         array("jquery"),
         THUMBNAIL_CLEANER_VERSION,
         false
      );
   }

   add_action("admin_enqueue_scripts", "thumbnail_cleaner_scripts_and_styles");

   /**
    * Registers Thumbnail Cleaner's menu page.
    *
    * @since 1.0.0
    */
   function thumbnail_cleaner_register_menu_page() {
      add_management_page(
         "Thumbnail Cleaner",
         "Thumbnail Cleaner",
         "manage_options",
         "thumbnail-cleaner",
         "thumbnail_cleaner_menu_page"
      );
   }

   add_action("admin_menu", "thumbnail_cleaner_register_menu_page");