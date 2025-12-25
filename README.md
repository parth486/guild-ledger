# Urbana Guild Ledger - WordPress Plugin

Tested up to: 6.9
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A secure and efficient WordPress plugin designed to help the Urbana team log and track personal interactions with key contacts (local councils, landscape architects, etc.).

## Version
**Current**: 1.0.1 (Beta) - Stage 1 with Modern DataViews Interface

### Version History
- **1.0.1** (2025-10-08) - Beta update: Modern DataViews interface, real-time search, CSV export, enhanced UX
- **1.0.0** (2025-09-29) - Initial release with basic admin functionality

## Description

The Urbana Guild Ledger provides a centralized record system for all contact interactions, ensuring data ownership through self-hosted WordPress storage. This Stage 1 implementation focuses on core functionality within the WordPress admin dashboard.

## Features

### Stage 1: Admin-Only MVP
- **Custom Post Type**: Private `urbana_ledger` post type for secure data storage
- **Admin Menu**: Clean "Urbana" menu with "Guild Ledger" submenu in WordPress admin
- **Data Entry Form** with the following fields:
  - Contact Name (required)
  - Company/Council
  - Date (required, auto-defaults to today)
  - Interaction Type (required dropdown: Email, Video Call, In-Person, Phone Call)
  - Notes (rich text editor for detailed conversation notes)
- **Modern DataViews Display**: Beautiful, interactive table interface with:
  - Real-time search filtering
  - Sortable columns
  - Color-coded interaction type badges
  - CSV export functionality
  - Responsive mobile design
  - Quick action buttons
- **Security**: Proper sanitization, validation, and access controls

## Installation

1. **Upload Plugin**:
   - Copy the entire `urbana-guild-ledger` folder to your WordPress `wp-content/plugins/` directory
   - Or zip the folder and upload via WordPress admin (Plugins > Add New > Upload Plugin)

2. **Activate Plugin**:
   - Go to WordPress Admin > Plugins
   - Find "Urbana Guild Ledger" and click "Activate"

3. **Access the Plugin**:
   - Look for the "Urbana" menu item in your WordPress admin sidebar
   - Click "Guild Ledger" to view all entries
   - Click "Add New Entry" to create a new interaction record

## Usage

### Adding a New Entry
1. Navigate to **Urbana > Add New Entry**
2. Fill in the required fields:
   - **Contact Name**: Enter the person's name you interacted with
   - **Date**: Select interaction date (defaults to today)
   - **Interaction Type**: Choose from Email, Video Call, In-Person, or Phone Call
3. Optionally fill in:
   - **Company/Council**: Organization the contact represents
   - **Notes**: Detailed conversation notes using the rich text editor
4. Click "Publish" to save the entry

### Viewing Entries
1. Navigate to **Urbana > Guild Ledger**
2. View all entries in a modern, interactive DataViews interface
3. Features available:
   - **Search**: Type in the search box to filter entries instantly
   - **Sort**: Click column headers to sort by Contact Name, Company/Council, Date, or Interaction Type
   - **Export**: Click the "📊 Export CSV" button to download all entries
   - **Quick View**: Hover over entries to see quick action buttons
4. Click any entry title to edit that record

> **Tip**: See `DATAVIEWS.md` for detailed information about the modern interface features.

### Managing Entries
- **Edit**: Click on any entry title to modify details
- **Delete**: Use the bulk actions or individual delete options
- **Search**: Use the search functionality to quickly find specific interactions
- **Filter**: Sort by any column to organize your data

## File Structure

```
urbana-guild-ledger/
├── urbana-guild-ledger.php    # Main plugin file
├── assets/
│   ├── admin.css              # Admin interface styles
│   └── admin.js               # Admin interface functionality
└── README.md                  # This documentation file
```

## Security Features

- **Access Control**: Only users with `manage_options` capability (typically administrators) can access the plugin
- **Data Sanitization**: All user input is properly sanitized using WordPress functions
- **Nonce Protection**: Forms include WordPress nonces to prevent CSRF attacks
- **Private Post Type**: Custom post type is not publicly queryable or accessible
- **Input Validation**: Required fields are validated both client-side and server-side

## Technical Details

### Custom Post Type
- **Name**: `urbana_ledger`
- **Access**: Private, admin-only
- **Capabilities**: Restricted to users with `manage_options`
- **Features**: Custom meta boxes, no Gutenberg editor

### Meta Fields
- `_urbana_contact_name`: Contact's full name
- `_urbana_company_council`: Organization/council name
- `_urbana_interaction_date`: Date of interaction
- `_urbana_interaction_type`: Type of interaction (email|video_call|in_person|phone_call)
- `_urbana_notes`: Rich text notes about the interaction

### WordPress Standards
- Follows WordPress Coding Standards
- Uses WordPress hooks and filters
- Implements proper sanitization and validation
- Includes internationalization support

## Browser Support
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Internet Explorer 11+

## Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- Administrator access to install plugins

## Future Development (Stage 2)
- Front-end interface for team collaboration
- User role management for team members
- Enhanced search and filtering capabilities
- Data export functionality
- Integration with external contact management systems

## Support

For technical support or feature requests, please contact the Urbana development team.

## Changelog

### Version 1.0.1 (2025-10-08) - Beta Update
**Modern DataViews Implementation**
- ✨ Implemented modern DataViews-style interface for listing page
- 🔍 Added real-time search filtering without page reload
- 📊 Added CSV export functionality with date-stamped filenames
- 🎨 Added color-coded interaction type badges (Email, Video Call, In-Person, Phone Call)
- 📱 Enhanced responsive design with mobile card layout
- ✨ Added smooth animations and transitions throughout
- 👁️ Added quick action buttons (Edit, Quick View, Delete) on row hover
- 🔧 Enabled REST API support (`show_in_rest: true`)
- 🐛 Fixed HTTP 500 error during plugin activation
- 📝 Added comprehensive documentation (DATAVIEWS.md, VISUAL-GUIDE.md)
- 🎯 Improved search with live filtering and debounce
- 💅 Modern UI with gradients, shadows, and hover effects
- ♿ Added accessibility improvements (keyboard navigation, ARIA labels)

### Version 1.0.0 (2025-09-29)
**Initial Release**
- Initial Stage 1 release
- Custom post type implementation
- Admin interface with data entry forms
- Sortable data table display
- Basic search functionality
- Security implementations

## License

This plugin is licensed under the GPL v2 or later.