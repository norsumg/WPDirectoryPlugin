# Local Business Directory

A WordPress plugin that creates a comprehensive business directory with categories, search functionality, and premium listings.

## Features

- Custom post type for businesses
- Business categories and areas taxonomies
- Custom fields for business details (phone, address, website)
- Premium listings designation
- Shortcodes for displaying categories and search functionality
- Customizable templates
- SEO-friendly URL structure: `/area-name/category-name/business-name/`

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

Area pages will be available directly at the root level (e.g., `/ashford/`).

### Setting Up Front-End Pages

#### Homepage

Add these shortcodes to your homepage or any other page:

- Display specific categories: `[custom_categories ids="1,2,3"]` (replace with your category IDs)
- Display business areas: `[business_areas ids="1,2,3"]` (replace with your area IDs)
- Display search form: `[business_search_form]`

#### Search Results Page

The plugin uses WordPress's built-in search functionality for displaying business search results.

1. All search forms (including the `[business_search_form]` shortcode) submit to the standard WordPress search URL
2. The plugin automatically modifies search queries when `post_type=business` is present in the URL
3. Results will be displayed using your theme's search.php template

You don't need to create a dedicated search page - simply use the `[business_search_form]` shortcode on any page, and the search results will display correctly using your theme's search template.

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
[business_search_form layout="horizontal" button_style="pill" placeholder="Find businesses..." submit_text="Search"]
```

**Available options:**

- `layout`: Choose from `vertical` (default) or `horizontal` layout
- `button_style`: Choose from `default`, `rounded`, `square`, or `pill`
- `placeholder`: Custom placeholder text for the search input
- `submit_text`: Custom text for the submit button

The search form submits to WordPress's built-in search functionality and the plugin automatically modifies search results when the `post_type=business` parameter is present.

## Premium Listings

Businesses marked as "Premium" will appear first in search results and category listings.

## Troubleshooting

### Permalink Issues
If business pages or area pages return 404 errors:
1. Go to Settings > Permalinks in your WordPress admin
2. Do not change anything, just click "Save Changes" 
3. This will flush the permalink rules and should resolve the issue

### Required Fields
Remember that every business needs:
1. A title
2. At least one business area assigned
3. At least one business category assigned
to function properly with the URL structure

### URL Structure
The plugin creates the following URL structure:
- Business listings: `/area-name/category-name/business-name/`
- Business areas: `/area-name/`
- Business categories: `/directory/category-name/`

This structure helps with SEO and user navigation.

## Search Widget Options

The search form shortcode has been enhanced with several styling options:

```
[business_search_form layout="horizontal" button_style="pill" placeholder="Find businesses..." submit_text="Search"]
```

**Available options:**

- `layout`: Choose from `vertical` (default) or `horizontal` layout
- `button_style`: Choose from `default`, `rounded`, `square`, or `pill`
- `placeholder`: Custom placeholder text for the search input
- `submit_text`: Custom text for the submit button

The search widget now smartly directs users to the appropriate pages:

- If a user selects only an area (e.g., "London"), they will be redirected directly to that area page
- If a user selects an area and category (e.g., "Restaurants in London"), they will be redirected to that specific category-in-area page
- Only when a search term is entered will they be directed to the search results page

## Directory URL Structure

All directory pages now use the `/directory/` namespace to prevent conflicts with regular WordPress pages. The URL structure is as follows:

- `/directory/` - Directory homepage (use the `[directory_home]` shortcode)
- `/directory/london/` - All businesses in the London area
- `/directory/london/restaurants/` - All restaurants in London
- `/directory/categories/restaurants/` - All restaurants across all areas

This structure ensures that your directory pages won't conflict with regular pages that might have the same slugs. 