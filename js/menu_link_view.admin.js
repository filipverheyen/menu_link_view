/**
 * @file
 * JavaScript behaviors for menu link view administration.
 *
 * Updated: 2025-06-26
 * Drupal 11 compatible
 */
(function (Drupal, once) {
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
      // Find all view menu items - use Drupal's once() for D11 compatibility
      once("menu-link-view-admin", ".menu-link-view-item", context).forEach(
        function (element) {
          // Add class for styling
          element.classList.add("no-child-allowed");

          // Remove unwanted operations immediately
          removeUnwantedOperations(element);

          // If operations get added after page load (AJAX), remove unwanted ones
          const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
              if (mutation.addedNodes.length > 0) {
                removeUnwantedOperations(element);
              }
            });
          });

          observer.observe(element, {
            childList: true,
            subtree: true,
          });
        }
      );

      // Enhanced tableDrag integration for better visual feedback
      const tables = context.querySelectorAll("table.menu-overview");
      tables.forEach(function (table) {
        const tableId = table.getAttribute("id");

        if (tableId && Drupal.tableDrag && Drupal.tableDrag[tableId]) {
          const tableDragInstance = Drupal.tableDrag[tableId];

          // Override the findChild method to provide visual feedback
          if (
            tableDragInstance.row &&
            tableDragInstance.row.prototype.findChild
          ) {
            const originalFindChild = tableDragInstance.row.prototype.findChild;

            tableDragInstance.row.prototype.findChild = function (
              childElement
            ) {
              // Check if we're trying to make something a child of a view item
              if (
                this.element &&
                this.element.classList.contains("menu-link-view-item")
              ) {
                // Add visual feedback immediately
                this.element.classList.add("drop-not-allowed");

                // Add warning message
                const titleCell = this.element.querySelector(
                  ".menu-item-title, td:nth-child(2)"
                );
                if (titleCell && !titleCell.querySelector(".drop-warning")) {
                  const warning = document.createElement("div");
                  warning.className = "drop-warning";
                  warning.textContent = "⚠ View items cannot have children";
                  warning.style.cssText = `
                    position: absolute;
                    background: #f00;
                    color: white;
                    padding: 3px 8px;
                    border-radius: 3px;
                    font-size: 11px;
                    font-weight: bold;
                    top: -25px;
                    left: 0;
                    z-index: 1000;
                    white-space: nowrap;
                    animation: fadeIn 0.3s ease-in;
                  `;

                  titleCell.style.position = "relative";
                  titleCell.appendChild(warning);

                  // Remove warning after delay
                  setTimeout(() => {
                    if (warning.parentNode) {
                      warning.remove();
                    }
                    this.element.classList.remove("drop-not-allowed");
                  }, 3000);
                }

                // Return null to indicate no valid child relationship
                return null;
              }

              // Use original logic for non-view items
              return originalFindChild.call(this, childElement);
            };
          }

          // Override the swap method for final prevention
          if (tableDragInstance.row && tableDragInstance.row.prototype.swap) {
            const originalSwap = tableDragInstance.row.prototype.swap;

            tableDragInstance.row.prototype.swap = function (direction, edge) {
              const targetRow = this[direction];

              // Prevent dropping ANY items ONTO view menu items (making them children)
              if (
                targetRow &&
                targetRow.element &&
                targetRow.element.classList.contains("menu-link-view-item") &&
                edge === "child"
              ) {
                // Show strong visual feedback
                targetRow.element.classList.add("drop-not-allowed");

                setTimeout(() => {
                  targetRow.element.classList.remove("drop-not-allowed");
                }, 2000);

                return;
              }

              // Allow all other operations
              return originalSwap.call(this, direction, edge);
            };
          }

          // Override mouseUp to clean up any lingering states
          if (
            tableDragInstance.row &&
            tableDragInstance.row.prototype.mouseUp
          ) {
            const originalMouseUp = tableDragInstance.row.prototype.mouseUp;

            tableDragInstance.row.prototype.mouseUp = function (event) {
              // Clean up any drop feedback
              const viewItems = table.querySelectorAll(".menu-link-view-item");
              viewItems.forEach(function (item) {
                item.classList.remove(
                  "drop-not-allowed",
                  "drag-target-invalid"
                );
                const warnings = item.querySelectorAll(".drop-warning");
                warnings.forEach((w) => w.remove());
              });

              return originalMouseUp.call(this, event);
            };
          }
        }
      });

      // Add CSS for fadeIn animation
      if (!document.querySelector("#menu-link-view-admin-styles")) {
        const style = document.createElement("style");
        style.id = "menu-link-view-admin-styles";
        style.textContent = `
          @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
          }
        `;
        document.head.appendChild(style);
      }
    },
  };
})(Drupal, once);
