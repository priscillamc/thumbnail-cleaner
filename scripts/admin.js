jQuery(document).ready(
    function () {
        "use strict";

        if (jQuery(".thumbnail-cleaner").length > 0) {
            jQuery("a").click(
                function () {
                    if (jQuery(this).attr("href") === "#") {
                        return false;
                    }
                }
            );

            jQuery("#clean-thumbnails").click(
                function () {
                    if (jQuery("#has-backups").attr("value") === "true") {
                        return true;
                    }
                    var text = jQuery("#has-backups-text").attr("value");
                    if (confirm(text) === false) {
                        return false;
                    }
                }
            );
        }
    }
);