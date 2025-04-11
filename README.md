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