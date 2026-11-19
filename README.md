# 🇳🇵 Nepal Tourism Analytics & Destination Recommendation System

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?style=for-the-badge&logo=javascript)
![Custom CSS](https://img.shields.io/badge/CSS-Custom_No_Bootstrap-264de4?style=for-the-badge&logo=css3)

A full-stack decision-support system designed to analyze Nepal's tourism data and provide personalized destination recommendations. Built entirely from scratch using a custom PHP MVC architecture, without heavy frameworks or external CDNs.

## 📖 About the Project
Nepal's tourism data is publicly available but locked inside complex PDF reports and unformatted spreadsheets. This system solves two major problems:
1. **For Researchers:** Transforms raw government data into interactive dashboards, calculating seasonal trends, market share, and demand forecasts.
2. **For Tourists:** Uses a dynamic, **Entropy-Weighted Algorithm** to recommend the top 5 destinations based on their specific interests, budget, and travel month.

## ✨ Key Features (Role-Based)

### 👨‍💻 Admin
*   **CSV Data Importer:** Upload real government data with strict row-by-row validation (rejects invalid dates, negative arrivals, and mismatched headers).
*   **Destination Management:** Auto-creates "Pending" destinations from CSV imports. Admin can upload photos, set NPR costs, assign regions, and approve them.
*   **System Logs:** Tracks every import and user action for accountability.

### 📊 Researcher
*   **Analytics Dashboard:** 5 interactive charts including an Arrival Trend line chart, Seasonal Heatmap, Lifecycle Scatter Plot (BCG Matrix), Nationality Mix donut chart, and Demand Forecast bar chart.
*   **Auto-Generated Insights:** Plain-English descriptions generated dynamically under every chart so non-experts can understand the data.
*   **Export Options:** Export filtered data to CSV, or use the custom print stylesheet to generate a clean PDF report (hides the sidebar and buttons).

### 🏖️ Tourist
*   **Netflix-Style Dashboard:** Horizontally scrolling rows of destination cards with visual star ratings and match percentages.
*   **Personalized Recommendations:** The system dynamically calculates the weight of 5 factors (Interest, Season, Budget, Popularity, Rating) using Shannon Entropy.
*   **Interactions:** Save destinations to Favourites, write 1-5 star reviews, and view locations on Google Maps.

## 🧠 The Algorithms (Mathematical Core)
The system features 5 algorithms implemented from scratch and mathematically verified via CLI tests:
1.  **Seasonal Index:** Calculates peak, shoulder, and low seasons for each destination.
2.  **Destination Lifecycle (BCG Matrix):** Classifies destinations into Star, Question Mark, Cash Cow, or Dog based on YoY growth and market share.
3.  **Demand Forecast:** Uses Multiplicative Classical Decomposition (Trend × Season) to predict next month's arrivals.
4.  **Recommendation Score:** Uses **Shannon Entropy** to calculate weights based on data variance. No arbitrary hardcoded weights!
5.  **YoY Growth:** Tracks year-over-year performance for national and destination-level data.

## 🛡️ Security & Architecture
*   **Custom MVC:** No frameworks used. Clean separation of Controllers, Views, and Models.
*   **Repository Pattern:** Database queries are completely decoupled from the analytical math (Service Layer).
*   **Security Best Practices:**
    *   **SQL Injection:** Prevented via PDO Prepared Statements.
    *   **XSS Protection:** Outputs escaped using `htmlspecialchars()` and `image_src()`.
    *   **CSRF:** Tokens on every POST form.
    *   **File Uploads:** Validated via `getimagesize()` and randomized filenames.
    *   **RBAC:** Strict Role-Based Access Control via `Auth::requireRole()`.

## 📂 Folder Structure
nepal-tourism-analytics/

├── app/
│   ├── Controllers/    # Admin, Researcher, Tourist, and Auth controllers
│   ├── Core/           # Router, Database, Session, CSRF, Auth
│   ├── Models/         # Data models
│   ├── Services/       # Analytics & Recommendation Algorithms
│   └── Views/          # UI Templates
├── assets/             # Custom CSS, Vanilla JS, Local Chart.js
├── config/             # Application configuration
├── database/           # Schema and Seeder scripts
├── storage/            # Logs and CSV imports
├── tests/              # CLI Analytics Tests
└── index.php           # Front Controller

## 🚀 Installation (XAMPP)

1. **Clone the repository** into your XAMPP `htdocs` folder:
   git clone https://github.com/YOUR_USERNAME/nepal-tourism-analytics.git nepal-tourism

2. **Start Apache and MySQL in the XAMPP Control Panel.**
3. **Database Setup**: Create a database named nepal_tourism in phpMyAdmin and import database/schema.sql.
4. **Configuration**: Ensure config/config.php matches your local MySQL credentials (default is root with no password).
5. **Run**: Visit http://localhost/nepal-tourism/


## 🧪 Testing
The mathematical logic is decoupled from the database to allow pure unit testing. To verify the algorithmic accuracy, run:
**php tests/analytics_test.php**
*Expected Output: PASSED: 87, FAILED: 0 (Tests cover Forecast, Best Months, Lifecycle, Entropy Weights, and Recommender Reasons).*
