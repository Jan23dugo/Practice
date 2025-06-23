# Transcript of Records (TOR) Scanner

A PHP application that uses Google Cloud Vision OCR to scan and extract data from academic Transcript of Records documents. The system detects column headers like Subject Code, Subject Description, Units, and Grades, and organizes the data into a structured format.

## Features

- Uploads and processes images of Transcript of Records
- Extracts subject codes, descriptions, units, and grades
- Handles different TOR formats by adapting to various column header names
- Stores extracted data in a MySQL database
- Provides a clean user interface for uploading and viewing results

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB
- Google Cloud Vision API key
- Web server (Apache, Nginx, etc.)
- PHP extensions: cURL, JSON, MySQLi

## Installation

1. Clone the repository to your web server directory
2. Import the database schema from `transcript_table.sql`
3. Configure your database connection in `config/config.php`
4. Ensure your Google Cloud Vision API key is set in `config/google_cloud_config.php`
5. Make sure the `uploads` directory is writable by the web server
6. Access the application through your web browser

## Usage

1. Open the TOR Scanner page in your web browser
2. Upload an image of a Transcript of Records
3. Optionally provide a Student ID if you want to save the data to the database
4. Click "Scan TOR" to process the image
5. View the extracted data in the results table

## How It Works

The system works by:

1. Uploading the TOR image to the server
2. Sending the image to Google Cloud Vision API for OCR processing
3. Analyzing the returned text to identify column headers
4. Extracting the relevant data based on the identified structure
5. Organizing the data into a structured format
6. Displaying the results to the user and/or saving to the database

## Troubleshooting

- If no data is extracted, try using a clearer image with better resolution
- Make sure the TOR format has clear column headers
- Check the Google Cloud API key and ensure it has access to the Vision API
- Verify that your PHP has the required extensions enabled

## License

This project is licensed under the MIT License.

# PUP Qualifying Exam Portal

## Frontend Architecture

The portal follows a consistent design system with centralized styles and JavaScript functionality.

### CSS Organization

The CSS is organized in two levels:

1. **Global Styles** (`styles/main.css`):
   - Contains all common styles used across the application
   - Defines color variables, typography, and layout components
   - Includes header, footer, sidebar, and responsive adjustments
   - Standardizes component styles (cards, buttons, notifications)

2. **Page-specific Styles** (inline in each page):
   - Only contains styles unique to that specific page
   - Keeps file size minimal by leveraging the global styles

### JavaScript Organization

JavaScript is also modularized:

1. **Global Scripts** (`js/main.js`):
   - Handles universal interactive elements
   - Manages sidebar toggling on mobile
   - Controls dropdowns and profile menus
   - Provides responsive behavior

### Design System Variables

The design system uses CSS variables for consistent styling:

```css
:root {
    --primary: #75343A;
    --primary-dark: #5a2930;
    --primary-light: #9e4a52;
    --secondary: #f8f0e3;
    --accent: #d4af37;
    --text-dark: #333333;
    --text-light: #ffffff;
    --gray-light: #f5f5f5;
    --gray: #e0e0e0;
    --success: #4CAF50;
    --warning: #FF9800;
    --danger: #F44336;
}
```

### Responsive Design

The portal is responsive across all devices with these breakpoints:
- Desktop: 1024px and above
- Tablet: 768px to 1023px
- Mobile: Below 768px

### Adding New Pages

When adding new pages to the system:

1. Include the global stylesheet:
   ```html
   <link rel="stylesheet" href="styles/main.css">
   ```

2. Include the global JavaScript:
   ```html
   <script src="js/main.js"></script>
   ```

3. Add page-specific styles using inline `<style>` tags
   ```html
   <style>
     /* Page-specific styles here */
   </style>
   ```

4. Use the standard page structure:
   ```html
   <div class="main-wrapper">
     <!-- Mobile Menu Toggle -->
     <button class="menu-toggle" id="menuToggle">
       <span class="material-symbols-rounded">menu</span>
     </button>

     <!-- Sidebar Overlay -->
     <div class="sidebar-overlay" id="sidebarOverlay"></div>

     <!-- Sidebar -->
     <aside class="sidebar" id="sidebar">
       <!-- Sidebar content -->
     </aside>

     <!-- Main Content -->
     <main class="main-content">
       <!-- Page content -->
     </main>
   </div>
   ``` 