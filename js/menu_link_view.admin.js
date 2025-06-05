/**
 * @file
 * JavaScript behaviors for menu link view administration.
 */
(function ($, Drupal) {
  "use strict";

  /**
   * Prevent other menu items from being dragged under menu view items.
   */
  Drupal.behaviors.menuLinkViewAdmin = {
    attach: function (context, settings) {
      // Find all view menu items
      const viewMenuItems = $(".menu-link-view-item", context);

      // Make them invalid drop targets
      viewMenuItems.once("menu-link-view-admin").each(function () {
        // Add class for styling
        $(this).addClass("no-child-allowed");

        // Listen for drag events and prevent dropping
        $(this).on("dragover", function (e) {
          e.preventDefault();
          e.stopPropagation();
          return false;
        });

        // Indicate visually that dropping is not allowed
        $(this).on("dragenter", function (e) {
          e.preventDefault();
          e.stopPropagation();
          $(this).addClass("drop-not-allowed");
          return false;
        });

        $(this).on("dragleave", function (e) {
          e.preventDefault();
          e.stopPropagation();
          $(this).removeClass("drop-not-allowed");
          return false;
        });

        // Prevent actual drop
        $(this).on("drop", function (e) {
          e.preventDefault();
          e.stopPropagation();
          $(this).removeClass("drop-not-allowed");
          return false;
        });
      });

      // Update Drupal's Tabledrag to recognize our special items
      if (
        Drupal.tableDrag &&
        Drupal.tableDrag.prototype.row.prototype.findTargetForNewLevel
      ) {
        const originalFindTarget =
          Drupal.tableDrag.prototype.row.prototype.findTargetForNewLevel;

        Drupal.tableDrag.prototype.row.prototype.findTargetForNewLevel =
          function () {
            const target = originalFindTarget.apply(this, arguments);

            // If target is a view menu item, return null to prevent dropping
            if (target && $(target).hasClass("menu-link-view-item")) {
              return null;
            }

            return target;
          };
      }
    },
  };
})(jQuery, Drupal);
