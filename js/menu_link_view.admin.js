/**
 * @file
 * JavaScript behaviors for menu link view administration.
 *
 * Updated: 2025-06-05
 * By: filipverheyen
 * Drupal 11 compatible
 */
(function (Drupal, once, $) {
  "use strict";

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

          // If operations get added after page load (AJAX), hide any add operation
          const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
              if (mutation.addedNodes.length > 0) {
                // Check for any newly added operation links
                const addLink = element.querySelector(
                  '.dropbutton-wrapper a[href*="add"]'
                );
                if (addLink) {
                  const listItem = addLink.closest("li");
                  if (listItem) {
                    listItem.style.display = "none";
                  }
                }
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
