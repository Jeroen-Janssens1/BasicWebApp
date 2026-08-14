-- movies / users / ratings with denormalized avg_rating in movies
-- MySQL 8+

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

USE appdb;

-- Clean start (optional)
 DROP TRIGGER IF EXISTS ratings_ai;
 DROP TRIGGER IF EXISTS ratings_au;
 DROP TRIGGER IF EXISTS ratings_ad;
 DROP TABLE IF EXISTS ratings;
 DROP TABLE IF EXISTS movies;
 DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT NOT NULL AUTO_INCREMENT,
  username VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB;

CREATE TABLE movies (
  id INT NOT NULL AUTO_INCREMENT,
  movieName VARCHAR(255) NOT NULL,
  movieDescription TEXT NULL,
  avg_rating DECIMAL(4,2) NULL,
  ratings_count INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_movies_movieName (movieName)
) ENGINE=InnoDB;

CREATE TABLE ratings (
  user_id INT NOT NULL,
  movie_id INT NOT NULL,
  score INT NOT NULL,
  PRIMARY KEY (user_id, movie_id),
  CONSTRAINT fk_ratings_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_ratings_movie
    FOREIGN KEY (movie_id) REFERENCES movies (id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Helpful for trigger updates
CREATE INDEX idx_ratings_movie_id ON ratings (movie_id);

DELIMITER $$

-- INSERT: update the affected movie
CREATE TRIGGER ratings_ai
AFTER INSERT ON ratings
FOR EACH ROW
BEGIN
  UPDATE movies m
  JOIN (
    SELECT movie_id,
           AVG(score) AS avg_rating,
           COUNT(*)   AS ratings_count
    FROM ratings
    WHERE movie_id = NEW.movie_id
    GROUP BY movie_id
  ) r ON r.movie_id = m.id
  SET m.avg_rating = r.avg_rating,
      m.ratings_count = r.ratings_count;
END$$

-- UPDATE: recompute old/new movie when movie_id changes; otherwise recompute once
CREATE TRIGGER ratings_au
AFTER UPDATE ON ratings
FOR EACH ROW
BEGIN
  IF NEW.movie_id = OLD.movie_id THEN
    UPDATE movies m
    JOIN (
      SELECT movie_id,
             AVG(score) AS avg_rating,
             COUNT(*)   AS ratings_count
      FROM ratings
      WHERE movie_id = NEW.movie_id
      GROUP BY movie_id
    ) r ON r.movie_id = m.id
    SET m.avg_rating = r.avg_rating,
        m.ratings_count = r.ratings_count;
  ELSE
    -- Recompute old movie
    UPDATE movies m
    LEFT JOIN (
      SELECT movie_id,
             AVG(score) AS avg_rating,
             COUNT(*)   AS ratings_count
      FROM ratings
      WHERE movie_id = OLD.movie_id
      GROUP BY movie_id
    ) r ON r.movie_id = m.id
    SET m.avg_rating = r.avg_rating,
        m.ratings_count = IFNULL(r.ratings_count, 0)
    WHERE m.id = OLD.movie_id;

    -- Recompute new movie
    UPDATE movies m
    JOIN (
      SELECT movie_id,
             AVG(score) AS avg_rating,
             COUNT(*)   AS ratings_count
      FROM ratings
      WHERE movie_id = NEW.movie_id
      GROUP BY movie_id
    ) r ON r.movie_id = m.id
    SET m.avg_rating = r.avg_rating,
        m.ratings_count = r.ratings_count
    WHERE m.id = NEW.movie_id;
  END IF;
END$$

-- DELETE: update affected movie; avg_rating stays NULL if no ratings remain
CREATE TRIGGER ratings_ad
AFTER DELETE ON ratings
FOR EACH ROW
BEGIN
  UPDATE movies m
  LEFT JOIN (
    SELECT movie_id,
           AVG(score) AS avg_rating,
           COUNT(*)   AS ratings_count
    FROM ratings
    WHERE movie_id = OLD.movie_id
    GROUP BY movie_id
  ) r ON r.movie_id = m.id
  SET m.avg_rating = r.avg_rating,
      m.ratings_count = IFNULL(r.ratings_count, 0)
  WHERE m.id = OLD.movie_id;
END$$

DELIMITER ;