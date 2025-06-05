/**
 * @file
 * JavaScript behaviors for menu link view administration.
 *
 * Updated: 2025-06-05 11:51:39
 * By: filipverheyen
 * Drupal 11 compatible
 */
(function (Drupal, once, $) {
  "use strict";

  /**
   * Helper function to remove specific operations
   */
  function removeUnwantedOperations(element) {
    // Find and remove add/translate operations in dropbutton
    const operations = element.querySelectorAll(
      'a[href*="add"], a[href*="translate"]'
    );
    operations.forEach(function (op) {
      const listItem = op.closest("li");
      if (listItem) {
        listItem.remove();
      } else {
        op.remove();
      }
    });
  }

  /**
   * Prevent other menu items from being dragged under menu view items.
   */
  Drupal.behaviors.menuLinkViewAdmin = {
    attach: function (context, settings) {
      // Find all view menu items - use Drupal's once() instead of jQuery's once()
      once("menu-link-view-admin", ".menu-link-view-item", context).forEach(
        function (element) {
          // Add class for styling
          element.classList.add("no-child-allowed");

          // Remove unwanted operations immediately
          removeUnwantedOperations(element);

          // Listen for drag events and prevent dropping
          element.addEventListener("dragover", function (e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
          });

          // Indicate visually that dropping is not allowed
          element.addEventListener("dragenter", function (e) {
            e.preventDefault();
            e.stopPropagation();
            element.classList.add("drop-not-allowed");
            return false;
          });

          element.addEventListener("dragleave", function (e) {
            e.preventDefault();
            e.stopPropagation();
            element.classList.remove("drop-not-allowed");
            return false;
          });

          // Prevent actual drop
          element.addEventListener("drop", function (e) {
            e.preventDefault();
            e.stopPropagation();
            element.classList.remove("drop-not-allowed");
            return false;
          });

          // If operations get added after page load (AJAX), remove unwanted ones
          const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
              if (mutation.addedNodes.length > 0) {
                // Check for any newly added operation links
                removeUnwantedOperations(element);
              }
            });
          });

          // Start observing the element for changes
          observer.observe(element, {
            childList: true,
            subtree: true,
          });
        }
      );

      // Update Drupal's Tabledrag to recognize our special items
      if (
        Drupal.tableDrag &&
        Drupal.tableDrag.prototype.row &&
        Drupal.tableDrag.prototype.row.prototype.findTargetForNewLevel
      ) {
        const originalFindTarget =
          Drupal.tableDrag.prototype.row.prototype.findTargetForNewLevel;

        Drupal.tableDrag.prototype.row.prototype.findTargetForNewLevel =
          function () {
            const target = originalFindTarget.apply(this, arguments);

            // If target is a view menu item, return null to prevent dropping
            if (target && target.classList.contains("menu-link-view-item")) {
              return null;
            }

            return target;
          };
      }
    },
  };
})(Drupal, once, jQuery);
