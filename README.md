# Tainan Government Website Crawler

A PHP-based web crawler that extracts and archives news information from www.tainan.gov.tw (Tainan City Government website).

View the collected news at: 
- Web: https://kiang.github.io/www.tainan.gov.tw/
- Facebook: https://www.facebook.com/tainan.focus (News with pictures)

## Description

This project crawls the Tainan City Government website to:
- Extract news articles and announcements
- Store structured data in the `docs/` directory
- Parse and organize government information for easier access
- Use Facebook Graph API for additional data collection
- Publish collected data through GitHub Pages
- Auto-post news with pictures to Facebook page "台南焦點"

## Prerequisites

- PHP 7.4 or higher
- Web server (Apache/Nginx)
- MySQL/MariaDB
- Composer (PHP package manager)
- Facebook Graph SDK

## Installation

1. Clone this repository
```bash
git clone https://github.com/yourusername/www.tainan.gov.tw.git
cd www.tainan.gov.tw
```

2. Install PHP dependencies using Composer
```bash
composer install
```

3. Configure your web server to point to the public directory

4. Configure Facebook App credentials for Graph API access in your scripts

## Facebook API Configuration

1. Create a Facebook App at https://developers.facebook.com/apps/
2. Get your Access Token:
   - Go to https://developers.facebook.com/tools/explorer/
   - Request permissions: `pages_show_list`, `pages_read_engagement`, `pages_manage_posts`
   - Extend the token expiration at https://developers.facebook.com/tools/debug/accesstoken/

3. Copy the configuration template:
```bash
cp scripts/config_ex.php scripts/config.php
```

4. Edit `scripts/config.php` with your Facebook App details:
   - `app_id`: Your Facebook App ID
   - `app_secret`: Your Facebook App Secret
   - `page_id`: The Facebook Page ID you want to monitor
   - `token`: The extended access token from step 2

## Usage

### Data Collection

The crawler scripts are located in the `scripts/` directory and will store extracted data in the `docs/` directory.

```bash
# Run the main crawler
php scripts/crawler.php

# The extracted data will be saved to:
docs/
  ├── news/           # News articles (viewable on GitHub Pages)
  ├── announcements/  # Government announcements
  └── data/          # Other structured data
```

### Accessing the Data

1. Raw data: Available in the `docs/` directory of this repository
2. Web interface: Visit https://kiang.github.io/www.tainan.gov.tw/ to browse the collected news
3. Facebook page: Follow https://www.facebook.com/tainan.focus for news with pictures
4. JSON format: All data is stored in JSON format for easy integration

### Running Scripts

The project contains the following PHP scripts in the `scripts/` directory:

1. `01_fetch_all.php`
   - Main crawler script that fetches all historical news
   - Downloads news articles from multiple department nodes
   - Processes and stores articles in JSON format

2. `02_fetch_rss.php`
   - Fetches news from various RSS feeds
   - Includes feeds for city news, department news, labor news, education news
   - Also fetches procurement announcements and city council meetings

3. `03_fetch_update.php`
   - Incremental update script for new content
   - Posts news with images to Facebook page
   - Uses Facebook Graph API for auto-posting

4. `04_daily_meta.php`
   - Generates and updates metadata files
   - Creates daily index files for better organization
   - Manages file relationships and references

5. `05_topic1.php`
   - Specialized script for temple events and activities
   - Extracts and processes temple-related announcements
   - Organizes data by location and date

6. `06_remove_raw.php`
   - Cleanup script to remove raw HTML files
   - Helps maintain disk space
   - Removes processed temporary files

To run any script:
```bash
php scripts/script-name.php
```

### Data Format

Extracted data is stored in structured format in the `docs/` directory:
- JSON format for easy parsing and usage
- Organized by date and category
- Includes metadata such as publication date, source, and categories

## Development

To start development:

1. Make sure all dependencies are installed via Composer
2. Configure your local development environment
3. Follow the PHP PSR coding standards

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details

## Contact

For any inquiries, please open an issue in the GitHub repository.
