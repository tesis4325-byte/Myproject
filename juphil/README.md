# Digital Love Journal System

The Digital Love Journal System is a personalized, web-based platform designed to help couples preserve and celebrate their most meaningful moments together.

## Features

*   **Secure User Authentication:** Separate login for each partner.
*   **Shared Journal:** Create and view journal entries.
*   **Private Entries:** Mark entries as private, visible only to the author.
*   **Photo Uploads:** Attach photos to your memories.
*   **Mood Tracker:** Log your daily mood with emojis.
*   **Memory Timeline:** View all your entries in a beautiful, chronological timeline.
*   **Customizable Themes:** Personalize your journal with themes like Sakura, Minimalist White, or Blush Pink.

## Requirements

*   PHP 7.4 or higher
*   MySQL or MariaDB
*   A web server (e.g., Apache, Nginx)

## Setup Instructions

1.  **Clone the Repository:**
    ```bash
    git clone <repository-url>
    ```

2.  **Database Setup:**
    *   Create a new MySQL database named `love_journal`.
    *   Import the `database.sql` file to set up the required tables and pre-populate the themes.
    ```bash
    mysql -u [your_username] -p love_journal < database.sql
    ```

3.  **Configure Database Connection:**
    *   Open the `config/database.php` file.
    *   Update the `DB_SERVER`, `DB_USERNAME`, `DB_PASSWORD`, and `DB_NAME` constants with your database credentials.

4.  **File Permissions:**
    *   Ensure that the `uploads/` directory is writable by the web server.

5.  **Deploy:**
    *   Place the project files in the document root of your web server.
    *   Access the application through your web browser.

## How to Use

1.  **Register:** Create a new account on the registration page.
2.  **Create or Join a Couple:**
    *   **Create:** After registering, you can create a new couple. This will generate an invite code.
    *   **Join:** If your partner has already created a couple, use their invite code to join.
3.  **Start Journaling:** Once you are part of a couple, you can start creating new entries, uploading photos, and logging your moods.
4.  **Customize:** Go to the settings page to change your journal's theme.