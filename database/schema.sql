-- =============================================================================
-- database/schema.sql
-- Nepal Tourism Analytics - MySQL 8.0, InnoDB, utf8mb4_unicode_ci
-- 21 tables. Drops and recreates on run (development only).
--
-- Design notes:
--   * Real data only. No simulated or demo rows exist in this schema.
--   * Currency: budget_npr and avg_cost_npr store Nepali Rupees (NPR).
--   * destinations.status ENUM('pending','active','inactive') default 'pending'
--     drives the CSV-import auto-create workflow.
--   * destinations.region_id is NULLABLE so auto-created pending rows do not
--     need a region; chk_destinations_active_has_region enforces that an
--     active destination always has a region.
-- =============================================================================

CREATE DATABASE IF NOT EXISTS nepal_tourism
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE nepal_tourism;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS system_logs;
DROP TABLE IF EXISTS import_history;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS favourites;
DROP TABLE IF EXISTS destination_demand;
DROP TABLE IF EXISTS arrivals_by_purpose;
DROP TABLE IF EXISTS arrivals_by_nationality;
DROP TABLE IF EXISTS monthly_arrivals;
DROP TABLE IF EXISTS destination_aliases;
DROP TABLE IF EXISTS destination_best_months;
DROP TABLE IF EXISTS destination_types;
DROP TABLE IF EXISTS destinations;
DROP TABLE IF EXISTS tourist_interests;
DROP TABLE IF EXISTS tourist_profiles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS regions;
DROP TABLE IF EXISTS purposes;
DROP TABLE IF EXISTS interest_types;
DROP TABLE IF EXISTS countries;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. roles
CREATE TABLE roles (
    id           TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(32)      NOT NULL,
    description  VARCHAR(255)     NULL,
    created_at   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. countries
CREATE TABLE countries (
    id         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code       CHAR(2)           NOT NULL,
    name       VARCHAR(64)       NOT NULL,
    is_active  TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_countries_code (code),
    UNIQUE KEY uq_countries_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. interest_types
CREATE TABLE interest_types (
    id           TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(50)      NOT NULL,
    description  VARCHAR(255)     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_interest_types_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. purposes
CREATE TABLE purposes (
    id    TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(50)      NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_purposes_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. regions
CREATE TABLE regions (
    id           TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(64)      NOT NULL,
    description  VARCHAR(255)     NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_regions_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. users
CREATE TABLE users (
    id             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    role_id        TINYINT UNSIGNED NOT NULL,
    name           VARCHAR(100)     NOT NULL,
    email          VARCHAR(190)     NOT NULL,
    password_hash  VARCHAR(255)     NOT NULL DEFAULT '',
    is_active      TINYINT(1)       NOT NULL DEFAULT 1,
    created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_id (role_id),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. tourist_profiles (budget in NPR)
CREATE TABLE tourist_profiles (
    id            INT UNSIGNED       NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED       NOT NULL,
    country_id    SMALLINT UNSIGNED  NULL,
    budget_npr    DECIMAL(12,2)      NULL,
    travel_month  TINYINT UNSIGNED   NULL,
    travel_year   SMALLINT UNSIGNED  NULL,
    created_at    DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tourist_profiles_user (user_id),
    KEY idx_tourist_profiles_country (country_id),
    CONSTRAINT fk_tourist_profiles_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_tourist_profiles_country
        FOREIGN KEY (country_id) REFERENCES countries (id) ON DELETE SET NULL,
    CONSTRAINT chk_tourist_profiles_month
        CHECK (travel_month IS NULL OR (travel_month BETWEEN 1 AND 12))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. tourist_interests
CREATE TABLE tourist_interests (
    tourist_profile_id  INT UNSIGNED     NOT NULL,
    interest_type_id    TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (tourist_profile_id, interest_type_id),
    KEY idx_tourist_interests_interest (interest_type_id),
    CONSTRAINT fk_tourist_interests_profile
        FOREIGN KEY (tourist_profile_id) REFERENCES tourist_profiles (id) ON DELETE CASCADE,
    CONSTRAINT fk_tourist_interests_type
        FOREIGN KEY (interest_type_id) REFERENCES interest_types (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. destinations (cost in NPR; status lifecycle; region_id nullable for pending)
CREATE TABLE destinations (
    id              INT UNSIGNED       NOT NULL AUTO_INCREMENT,
    region_id       TINYINT UNSIGNED   NULL,
    name            VARCHAR(120)       NOT NULL,
    slug            VARCHAR(140)       NOT NULL,
    description     TEXT               NULL,
    latitude        DECIMAL(9,6)       NULL,
    longitude       DECIMAL(9,6)       NULL,
    avg_cost_npr    DECIMAL(12,2)      NOT NULL DEFAULT 0.00,
    status          ENUM('pending','active','inactive') NOT NULL DEFAULT 'pending',
    created_by      INT UNSIGNED       NULL,
    created_at      DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_destinations_slug (slug),
    KEY idx_destinations_region (region_id),
    KEY idx_destinations_status (status),
    CONSTRAINT fk_destinations_region
        FOREIGN KEY (region_id) REFERENCES regions (id) ON DELETE RESTRICT,
    CONSTRAINT fk_destinations_created_by
        FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_destinations_active_has_region
        CHECK (status <> 'active' OR region_id IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. destination_types
CREATE TABLE destination_types (
    destination_id    INT UNSIGNED     NOT NULL,
    interest_type_id  TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (destination_id, interest_type_id),
    KEY idx_destination_types_interest (interest_type_id),
    CONSTRAINT fk_destination_types_destination
        FOREIGN KEY (destination_id) REFERENCES destinations (id) ON DELETE CASCADE,
    CONSTRAINT fk_destination_types_interest
        FOREIGN KEY (interest_type_id) REFERENCES interest_types (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. destination_best_months
CREATE TABLE destination_best_months (
    destination_id  INT UNSIGNED     NOT NULL,
    month_number    TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (destination_id, month_number),
    CONSTRAINT fk_destination_best_months_destination
        FOREIGN KEY (destination_id) REFERENCES destinations (id) ON DELETE CASCADE,
    CONSTRAINT chk_destination_best_months_range
        CHECK (month_number BETWEEN 1 AND 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. destination_aliases
CREATE TABLE destination_aliases (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    destination_id  INT UNSIGNED NOT NULL,
    alias           VARCHAR(120) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_destination_aliases (destination_id, alias),
    CONSTRAINT fk_destination_aliases_destination
        FOREIGN KEY (destination_id) REFERENCES destinations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. monthly_arrivals (national totals)
CREATE TABLE monthly_arrivals (
    id        INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    year      SMALLINT UNSIGNED NOT NULL,
    month     TINYINT UNSIGNED  NOT NULL,
    arrivals  INT UNSIGNED      NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_monthly_arrivals (year, month),
    CONSTRAINT chk_monthly_arrivals_month CHECK (month BETWEEN 1 AND 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. arrivals_by_nationality (annual)
CREATE TABLE arrivals_by_nationality (
    id          INT UNSIGNED       NOT NULL AUTO_INCREMENT,
    year        SMALLINT UNSIGNED  NOT NULL,
    country_id  SMALLINT UNSIGNED  NOT NULL,
    arrivals    INT UNSIGNED       NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_arrivals_by_nationality (year, country_id),
    KEY idx_arrivals_by_nationality_country (country_id),
    CONSTRAINT fk_arrivals_by_nationality_country
        FOREIGN KEY (country_id) REFERENCES countries (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. arrivals_by_purpose (annual)
CREATE TABLE arrivals_by_purpose (
    id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    year        SMALLINT UNSIGNED NOT NULL,
    purpose_id  TINYINT UNSIGNED  NOT NULL,
    arrivals    INT UNSIGNED      NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_arrivals_by_purpose (year, purpose_id),
    KEY idx_arrivals_by_purpose_purpose (purpose_id),
    CONSTRAINT fk_arrivals_by_purpose_purpose
        FOREIGN KEY (purpose_id) REFERENCES purposes (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. destination_demand (monthly per destination, real imported data only)
CREATE TABLE destination_demand (
    id              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    destination_id  INT UNSIGNED      NOT NULL,
    year            SMALLINT UNSIGNED NOT NULL,
    month           TINYINT UNSIGNED  NOT NULL,
    arrivals        INT UNSIGNED      NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_destination_demand (destination_id, year, month),
    KEY idx_destination_demand_year_month (year, month),
    CONSTRAINT fk_destination_demand_destination
        FOREIGN KEY (destination_id) REFERENCES destinations (id) ON DELETE CASCADE,
    CONSTRAINT chk_destination_demand_month CHECK (month BETWEEN 1 AND 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. favourites
CREATE TABLE favourites (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    destination_id  INT UNSIGNED NOT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_favourites_user_destination (user_id, destination_id),
    KEY idx_favourites_destination (destination_id),
    CONSTRAINT fk_favourites_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_favourites_destination
        FOREIGN KEY (destination_id) REFERENCES destinations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. reviews
CREATE TABLE reviews (
    id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED     NOT NULL,
    destination_id  INT UNSIGNED     NOT NULL,
    rating          TINYINT UNSIGNED NOT NULL,
    comment         TEXT             NULL,
    created_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_reviews_user_destination (user_id, destination_id),
    KEY idx_reviews_destination (destination_id),
    CONSTRAINT fk_reviews_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_destination
        FOREIGN KEY (destination_id) REFERENCES destinations (id) ON DELETE CASCADE,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. import_history
CREATE TABLE import_history (
    id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    admin_id       INT UNSIGNED  NOT NULL,
    filename       VARCHAR(255)  NOT NULL,
    rows_total     INT UNSIGNED  NOT NULL DEFAULT 0,
    rows_accepted  INT UNSIGNED  NOT NULL DEFAULT 0,
    rows_rejected  INT UNSIGNED  NOT NULL DEFAULT 0,
    status         ENUM('success','partial','failed') NOT NULL,
    created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_import_history_admin (admin_id),
    CONSTRAINT fk_import_history_admin
        FOREIGN KEY (admin_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. system_logs
CREATE TABLE system_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NULL,
    action      VARCHAR(100)    NOT NULL,
    details     TEXT            NULL,
    ip_address  VARCHAR(45)     NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_system_logs_user (user_id),
    KEY idx_system_logs_created (created_at),
    CONSTRAINT fk_system_logs_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. settings
CREATE TABLE settings (
    id             SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key    VARCHAR(100)      NOT NULL,
    setting_value  TEXT              NOT NULL,
    updated_at     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_settings_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;