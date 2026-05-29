# Rule: Mandatory Local SQLite Database for Scraper Tools

## Problem Statement
Web scraping operations can be long-running and fragile. If a page is accidentally refreshed, the browser crashes, or a network error occurs, any scraped data held only in memory (RAM) is entirely lost. This forces the user to restart the scraping process from scratch, wasting significant time and resources. Furthermore, data without timestamps lacks historical context.

## Rules & Guidelines

### 1. Mandatory Local SQLite Database
- **Never** rely solely on in-memory storage (e.g., arrays, variables) for scraped data.
- **Every** scraper tool must instantiate and utilize a local SQLite database (or an equivalent local relational storage if restricted by environment) on the user's PC.
- The database must remain strictly local to the user's machine. It must **not** be hosted on, or primarily saved to, a remote or central server.

### 2. Continuous and Immediate Saving
- Scraped data must be inserted into the local SQLite database continuously as the tool runs (e.g., row-by-row or in small, frequent batches).
- **Do not** wait until the end of the scraping operation to bulk-save the data. This guarantees that if a disruption occurs, the maximum amount of data is preserved.

### 3. Required Timestamping
- Every single record saved to the database must include a datetime stamp (e.g., `scraped_at` or `created_at`).
- This allows the user to access the data anytime in the future and precisely track when the information was gathered.

### 4. Crash Recovery and Resumption
- Because the data is saved locally in real-time, the scraper tool should be designed to leverage this database to prevent data loss. 
- If a page is refreshed by mistake, the tool should be able to query the local database to recognize what has already been scraped and resume operations without losing progress or creating duplicates.
