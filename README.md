# Local Business Directory

A WordPress plugin that creates a comprehensive business directory with categories, search functionality, and premium listings.

## Features

- Custom post type for businesses
- Business categories and areas taxonomies
- Custom fields for business details (phone, address, website)
- Premium listings designation
- Shortcodes for displaying categories and search functionality
- Customizable templates
- SEO-friendly URL structure: `/area/category/business-name/`

## Installation

1. Download or clone this repository to your WordPress plugins directory (`wp-content/plugins/`)
2. Activate the plugin through the WordPress admin interface
3. **Important**: After activation, go to Settings > Permalinks and click "Save Changes" to refresh the permalink structure

## Usage

### Adding Businesses

1. Go to Businesses > Add New in your WordPress admin
2. Enter the business name as the title
3. Add a description in the main content editor
4. Upload a featured image (logo or photo of the business)
5. Fill in the Business Details meta box (phone, address, website)
6. Check the "Premium" box if this is a premium listing
7. Assign both a business category and a business area
8. Publish the business

### Business Areas

Business areas represent geographic locations (e.g., neighborhoods, cities, regions). Each business must be assigned to an area for proper URL structure.

### Setting Up Front-End Pages

#### Homepage

Add these shortcodes to your homepage or any other page:

- Display specific categories: `[custom_categories ids="1,2,3"]` (replace with your category IDs)
- Display business areas: `[business_areas ids="1,2,3"]` (replace with your area IDs)
- Display search form: `[business_search_form]`

#### Search Results Page

1. Create a new page called "Search Results"
2. Add the shortcode: `[business_search_results]`
3. Publish the page
4. Make sure the page slug is "search-results" (or update the form action URL in the shortcode)

### Viewing Listings

- Business listings will be available at: `/area-name/category-name/business-name/`
- Area pages will be available at: `/area-name/`
- Category pages will still be available at: `/directory/category-name/`

### Customizing Templates

To override the default templates, copy any of these files to your theme:

- `single-business.php` - Single business profile
- `taxonomy-business_area.php` - Area listing page
- `taxonomy-business_category.php` - Category listing page
- `content-business.php` - Individual business list item

## Shortcodes Reference

### Display Categories
```
[custom_categories ids="1,2,3"]
```
- `ids` - Comma-separated IDs of categories to display (optional)

### Display Areas
```
[business_areas ids="1,2,3"]
```
- `ids` - Comma-separated IDs of areas to display (optional)

### Search Form
```
[business_search_form]
```

### Search Results
```
[business_search_results]
```

## Premium Listings

Businesses marked as "Premium" will appear first in search results and category listings.

## Troubleshooting

### Permalink Issues
If business pages return 404 errors:
1. Go to Settings > Permalinks in your WordPress admin
2. Do not change anything, just click "Save Changes" 
3. This will flush the permalink rules and should resolve the issue

### Required Fields
Remember that every business needs:
1. A title
2. At least one business area assigned
3. At least one business category assigned
to function properly with the URL structure 