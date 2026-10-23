-- Meetup website schema. Recreate with:
-- mysql -u root -p < backend/schema.sql

CREATE DATABASE IF NOT EXISTS twae_kya_ml
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE twae_kya_ml;

CREATE TABLE IF NOT EXISTS meetings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  room_number VARCHAR(4) NOT NULL,
  organizer_method VARCHAR(16) NOT NULL,
  organizer_label VARCHAR(160) NOT NULL,
  organizer_data JSON NULL,
  fixed_place VARCHAR(255) NULL,
  confirmed_date DATE NULL,
  confirmed_start TIME NULL,
  confirmed_end TIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_meeting_room (room_number)
  -- A meeting and its members are deleted 90 days after created_at, which frees room_number.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS meetup_members (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  room_number VARCHAR(4) NOT NULL,
  member_name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  suggest_location VARCHAR(255) NOT NULL,
  meetup_style VARCHAR(40) NOT NULL,
  available_time TEXT NOT NULL,
  expected_arrival VARCHAR(8) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_room_phone (room_number, phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yangon venue catalog. The running app fills this from backend/yangon_venues.php.
CREATE TABLE IF NOT EXISTS venue_places (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  city VARCHAR(40) NOT NULL,
  city_mm VARCHAR(40) NOT NULL,
  township VARCHAR(48) NOT NULL,
  township_mm VARCHAR(80) NOT NULL,
  township_sort SMALLINT UNSIGNED NOT NULL,
  meetup_style VARCHAR(40) NOT NULL,
  name VARCHAR(160) NOT NULL,
  address VARCHAR(255) NOT NULL,
  detail VARCHAR(500) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_venue (meetup_style, township, name),
  KEY idx_city_style (city, meetup_style, township_sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS venue_seed (
  id TINYINT UNSIGNED NOT NULL,
  version INT UNSIGNED NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vote rows contain only member and place IDs; API responses expose totals only.
CREATE TABLE IF NOT EXISTS meeting_votes (
  meeting_id INT UNSIGNED NOT NULL,
  member_id INT UNSIGNED NOT NULL,
  place VARCHAR(255) NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (meeting_id, member_id),
  KEY idx_meeting_votes_place (meeting_id, place)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_locations (
  meeting_id INT UNSIGNED NOT NULL,
  member_id INT UNSIGNED NOT NULL,
  latitude DECIMAL(10,7) NOT NULL,
  longitude DECIMAL(10,7) NOT NULL,
  shared_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (meeting_id, member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
