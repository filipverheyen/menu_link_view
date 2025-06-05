/**
 * @file
 * Aggressive action remover for menu link view items.
 */
(function (Drupal, settings, once) {
  "use strict";

  /**
   * Checks if a DOM element represents or contains a view menu item.
   */
  function isViewMenuItem(element) {
    // Direct check - element has view menu item class
    if (
      element.classList &&
      element.classList.contains("menu-link-view-item")
    ) {
      return true;
    }

    // Check for title with [View]
    if (element.textContent && element.textContent.includes("[View]")) {
      element.classList.add("menu-link-view-item"); // Mark for future checks
      return true;
    }

    // Check for data plugin ID if available
    if (element.dataset && element.dataset.pluginId) {
      // Get view menu items from settings
      const viewItems =
        (settings.menuLinkView && settings.menuLinkView.viewMenuItems) || [];
      if (viewItems.includes(element.dataset.pluginId)) {
        element.classList.add("menu-link-view-item"); // Mark for future checks
        return true;
      }
    }

    return false;
  }

  /**
   * Process a single row/element to remove add operations.
   */
  function processRowElement(row) {
    if (isViewMenuItem(row)) {
      // Find dropbutton elements first
      const dropbuttonElements = row.querySelectorAll(".dropbutton-wrapper");

      dropbuttonElements.forEach(function (dropbutton) {
        // Look for add/create links inside
        const actionLinks = dropbutton.querySelectorAll("a");

        actionLinks.forEach(function (link) {
          const href = link.getAttribute("href") || "";
          const text = link.textContent || "";

          // Check both href and text to detect add/create operations
          if (
            href.includes("add") ||
            href.includes("create") ||
            text.toLowerCase().includes("add") ||
            text.toLowerCase().includes("create")
          ) {
            const listItem = link.closest("li") || link.parentNode;
            if (listItem) {
              listItem.remove();
            } else {
              link.style.display = "none";
              link.setAttribute("disabled", "disabled");
            }
          }
        });
      });

      // Also look for any standalone links
      const actionButtons = row.querySelectorAll("a.button, a.action-link");

      actionButtons.forEach(function (button) {
        const href = button.getAttribute("href") || "";
        const text = button.textContent || "";

        if (
          href.includes("add") ||
          href.includes("create") ||
          text.toLowerCase().includes("add") ||
          text.toLowerCase().includes("create")
        ) {
          button.style.display = "none";

          // If inside a container, hide the container too
          const container = button.closest(".action-links-item");
          if (container) {
            container.style.display = "none";
          }

          // Disable the button
          button.addEventListener(
            "click",
            function (e) {
              e.preventDefault();
              return false;
            },
            true
          );
        }
      });
    }
  }

  /**
   * Main processing function for the entire document.
   */
  function processDocument() {
    // Process menu items in tables
    document
      .querySelectorAll('tr[data-drupal-selector*="menu-link"]')
      .forEach(processRowElement);

    // Process any elements with view menu item class
    document
      .querySelectorAll(".menu-link-view-item")
      .forEach(processRowElement);

    // Process action links above tables (targeting only when needed)
    document.querySelectorAll("ul.action-links").forEach(function (actionList) {
      // Only process if we actually have view menu items on the page
      const viewItemsExist =
        document.querySelector(".menu-link-view-item") !== null;

      if (viewItemsExist) {
        // Find action links that would create children
        const childActions = actionList.querySelectorAll(
          'a[href*="add"], a[href*="parent="]'
        );

        childActions.forEach(function (link) {
          const href = link.getAttribute("href") || "";
          const listItem = link.closest("li");

          // If this link has a parent parameter, check if it points to a view menu item
          if (href.includes("parent=")) {
            const matches = href.match(/parent=([^&]+)/);

            if (matches && matches[1]) {
              const parentId = decodeURIComponent(matches[1]);
              const viewItems =
                (settings.menuLinkView &&
                  settings.menuLinkView.viewMenuItems) ||
                [];

              if (viewItems.includes(parentId)) {
                // Hide the link as it's trying to add a child to a view menu item
                if (listItem) {
                  listItem.remove();
                } else {
                  link.style.display = "none";
                }
              }
            }
          }
        });
      }
    });
  }

  /**
   * Drupal behavior to remove add operations.
   */
  Drupal.behaviors.menuLinkViewActionRemover = {
    attach: function (context, settings) {
      // Initial processing
      once("menu-link-view-action-remover", "body", context).forEach(
        function () {
          processDocument();
        }
      );

      // Set up mutation observer to continuously monitor for changes
      const observer = new MutationObserver(function (mutations) {
        processDocument();
      });

      // Observe important parts of the page for changes
      const menuTables = document.querySelectorAll(".menu-edit-form table");
      if (menuTables.length) {
        menuTables.forEach(function (table) {
          observer.observe(table, {
            childList: true,
            subtree: true,
            attributes: true,
          });
        });
      } else {
        // If no specific tables found, monitor body
        observer.observe(document.body, {
          childList: true,
          subtree: true,
        });
      }

      // Also directly target any dynamically loaded dropbuttons
      document.addEventListener("drupal:ajax-success", function () {
        setTimeout(processDocument, 100);
      });
    },
  };
})(Drupal, drupalSettings, once);
