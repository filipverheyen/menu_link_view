# Menu Link View

## INTRODUCTION

Menu Link View is a Drupal module that enables dynamic menu items by integrating Drupal Views directly into your site navigation. This module seamlessly expands your menus with view results while preserving the standard DOM structure and markup of Drupal menus, ensuring theme compatibility and consistent styling throughout your site.

## REQUIREMENTS

This module requires the following:
* Drupal Core 11.x
* Views (included in Drupal Core)

## INSTALLATION

1. Install the module as you would normally install a contributed Drupal module.
   Visit [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules) for further information.
2. Navigate to "Extend" and enable the Menu Link View module.

## CONFIGURATION

### Adding a View-based menu item

1. Go to Structure > Menus and edit your desired menu.
2. Add a view-based menu item with the "Add view menu link" button.
3. Drag the placeholder menu item where you want the view expanded into menu links.

Note that you can't attach other menu items as children of a Menu View Link.

### Creating Views for menu integration

Your View should:
1. Return entity references (nodes, taxonomy terms, or other entities)

## FEATURES

* **Dynamic Menus**: Update your navigation automatically when content changes
* **Display Flexibility**: Use any View display to generate menu items
* **Content-driven Navigation**: Create navigation based on taxonomy terms, nodes, or custom entities
* **Preservation of Menu Structure**: Works with existing menu items and maintains parent/child relationships
* **Active Trail Support**: Maintains proper highlighting of current and parent items
* **Clean DOM Structure**: Preserves the standard Drupal menu DOM structure without adding extra wrappers or custom styling
* **Theme Compatibility**: Generated menu items appear identical to manually created menu items, ensuring perfect integration with your theme

## HOW IT WORKS

Menu Link View dynamically replaces designated menu items with links generated from View results. The module:

1. Identifies menu items that have been configured with view information
2. Loads and executes the configured View
3. Creates synthetic menu items from each row in the View results
4. Replaces the original menu item with these generated items
5. Preserves all standard menu styling, active trail, and behavior
6. Maintains the exact same DOM structure as regular menu items - no additional markup or wrappers are added

## TROUBLESHOOTING

### Menu items aren't displaying
* Clear the Drupal cache
* Verify your View is returning results
* Check that the View's display is configured to show entity references
* Verify the view_id and display_id are correct in your menu item options

### Missing styling or active trail
* Clear the theme cache
* Check your theme's menu template for compatibility with standard Drupal menu classes

## MAINTAINERS

* Filip Verheyen (filipverheyen) - https://github.com/filipverheyen

## DEVELOPMENT

This module is developed on GitHub:
https://github.com/filipverheyen/menu_link_view

Issues and pull requests are welcome.
