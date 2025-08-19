=== MapSVG - Vector maps, Image maps, Google Maps ===
Contributors: oyatek
Tags: map, store locator, google maps, floorplan, image map
Requires at least: 5.0
Tested up to: 6.6
Stable tag: 8.5.34
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create interactive vector maps, floor plans, and image maps. Support for Google Maps integration, custom markers, tooltips, and popups.

== Description ==

MapSVG is a powerful WordPress mapping plugin that allows you to create interactive vector maps, floor plans, and image maps. Perfect for real estate websites, store locators, seating charts, and more.

* [WP-Admin Demo](https://mapsvg.com/demo)

https://youtu.be/GXrGA9ecN-Y?si=ExhZthcs1AOn58IQ

= Key Features (free version) =

* SVG Vector Maps (100+ maps are included in the plugin)
* Custom SVG files support (upload your own SVG files and turn them into interactive maps)
* Google Maps
* Custom overlays on Google Maps
* Image Maps
* Floor Plans
* Store Locator (search by address)
* Markers (add markers by entering an address)
* Connect markers to clickable areas on the map (show all locations connected to a specific area in the popover window)
* Map areas and markers working as links
* Custom Tooltips, Popovers, Large scrollable modals
* Database Integration
* Custom Fields
* Filters & Search
* REST API Support

= MapSVG vs other mapping plugin =

Below is the comparison of the free version of MapSVG to other popular free mapping plugins on WordPress.org.

### Unlimited maps
* **MapSVG**: Unlimited number of maps
* **Others**: 1 map

### Unlimited locations
* **MapSVG**: Unlimited number of markers 
* **Others**: Limited number of markers

### Map regions to locations connection
* **MapSVG**: Connect markers to map clickable areas. Every area of the map can have one or many markers connected to it. Show the list of connected locations in the popover window.
* **Others**: No connection between markers and map clickable areas

### Internal Database
* **MapSVG**: Custom database, with custom fields support
* **Others**: You can only use a small set of pre-defined fields, such as title, description and address.

### Views
* **MapSVG**: Show custom fields, as formatted HTML, or show the fully rendered WP Post content - in the Tooltips / Popovers (unique feature of MapSVG)
* **Others**: You can only show the coordinates of the marker in a small tooltip

### Labels
* **MapSVG**: Show marker labels (name or address of the location) and region labels (names of countries, states, etc.)
* **Others**: No labels


= MapSVG Premium Features =

- **Directory**: Display a list of interactive items alongside the map.
- **Custom Overlays**: Add custom overlays on Google Maps.
- **Advanced Filters**: Enable fulltext search, search by category, zip code, by date.
- **Country-Specific Address Search**: Restrict address searches by country.
- **CSV Data Import**: Load data seamlessly from CSV files.
- **Image Gallery Module**: Showcase images in a lightbox format.
- **Parent/Child Maps**: Open an additional map upon clicking a region.
- **Marker Clustering**: Group large amount of markers into clusters.
- **Data Source: WordPressCPT**: Use WordPress CPTs as a data source.
- **Data Source: Custom API**: Use JSON response from your own API as a data source.
- **Code editor: CSS**: Edit CSS directly in the map editor, customize the look and feel of the map.
- **Code editor: Handlebars Templates**: Get full control over the HTML of the popovers / tooltips / details view.
- **Code editor: JavaScript**: Use built-in JavaScript editor for event handling, middleware customization, modify data before it is displayed on the map.
- **Custom Marker Images**: Upload your own marker images.
- **Chat Authorization**: Store the chat history. Get access to the same chats online any time on mapsvg.com/dashboard. 
- **Priority Support**: Faster response time in the chat.
- **Purchase Code for Updates**: Enable automatic updates from our servers with the purchase code for more frequent updates.
- **One-Click wp-admin Access**: Grant support agents quick access to your wp-admin for faster issue resolution.


== Installation ==

1. Unzip the plugin folder
2. Upload the plugin folder to `/wp-content/plugins` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Click on the MapSVG menu item
5. Get your Google Maps API keys and add them to the MapSVG settings
6. Create your first map
7. Activate support chat
8. Ask our AI bot any questions

== Frequently Asked Questions ==

= Can I use my own SVG files? =

Yes, you can upload any SVG file and turn it into an interactive map.

= Do you have a vector map of XYZ? = 

MapSVG has 100+ SVG vector maps inlcuded in the plugin. Also, MapSVG can create an interactive map from any custom SVG file. But it can't generate SVG maps from scratch and we don't provide map drawing services. If you need a specific map of some country, you can try to search on Google "blank svg map of XYZ", download the SVG file and then upload it to MapSVG.

= Can I turn map areas into clickable links? =

Yes, you can do that.

= Can I show another map on click on a map region? =

Yes, you can do that - but only in the Premium version.

= Can I show fields created with ACF plugin? =

You can show a full rendered post content in the modal window on click on a map region. If you WP theme page template shows ACF fields - you can show them in a modal window. If you want to show just some of the ACF fields, it is possible to do - but only in the Premium version.

= Can I create a store locator with MapSVG? =

Yes, you can create store locator with search by address. But if you also need to show a clickable list of stores, you need to use MapSVG Premium.

= Can I use a custom marker image? =

You can - but only in the Premium version.

= Can I customize the look and feel of the map? =

In the free version, you can use your WP theme's CSS file to customize the looks of the map.
MapSVG Premium has an embedded CSV editor, which makes style editing more comfortable, because you can see the changes you make in real-time.

== Changelog ==

= 8.5.17 =
* Fixed: show popover on directory item click

= 8.5.15 =
* Fixed: filters not working properlywhen "search" button is added to the form

= 8.5.14 =
* Fix filters

= 8.5.11 = 
* Fix tables creation on plugin activation

= 8.5.10 = 
* Fix tables creation on plugin activation
* Fix viewBox reset to initial value

= 8.5.9 =
* Fix pagination

= 8.5.8 =
* Limit zoom levels to 1-22 (same as Google Maps)

= 8.5.7 =
* Fix reloading filters state on directory redraw

= 8.5.6 =
* Adjust Australia SVG map for better region label placement

= 8.5.29 =
* Fix mobile buttons list/map 

= 8.5.30 =
* Fix zero header/footer size, when used with details view

= 8.5.31 =
* Fix invisible settings tabs in the Map Editor
* Fix the flush_rewrite_rules() execution issues
* Defer JS scripts loading

= 8.5.32 =
* Fix handle click on HTML links in SVG files
* Fix duplicate map feature
* Fix on mobile devices, on directory item click - properly fit markers on google maps
* Fix keep text search field state on database fetch, when filters are located in directory

= 8.5.32 =
* Change "Upgrade to Pro" links to mapsvg.com/pricing


== Upgrade Notice ==

== Screenshots ==
1. Adding a location
2. Setting up actions
3. Drawing clickable areas on Google Maps