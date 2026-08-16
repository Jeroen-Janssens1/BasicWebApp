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

INSERT INTO movies (movieName, movieDescription)
VALUES
 ("Harry Potter and the Philosopher's Stone", "An orphaned boy enrolls in a school of wizardry, where he learns the truth about himself, his family and the terrible evil that haunts the magical world."),
("Harry Potter and the Chamber of Secrets", "Harry Potter lives his second year at Hogwarts with Ron and Hermione when a message on the wall announces that the legendary Chamber of Secrets has been opened. The trio soon realize that, to save the school, it will take a lot of courage."),
("Harry Potter and the Prisoner of Azkaban", "Harry Potter, Ron and Hermione return to Hogwarts School of Witchcraft and Wizardry for their third year of study, where they delve into the mystery surrounding an escaped prisoner who poses a dangerous threat to the young wizard."),
("Harry Potter and the Goblet of Fire", "Harry Potter finds himself competing in a hazardous tournament between rival schools of magic, but he is distracted by recurring nightmares."),
("Harry Potter and the Order of the Phoenix", "With their warning about Lord Voldemort's return scoffed at, Harry and Dumbledore are targeted by the Wizard authorities as an authoritarian bureaucrat slowly seizes power at Hogwarts."),
("Harry Potter and the Half-Blood Prince", "As Harry Potter begins his sixth year at Hogwarts, he discovers an old book marked as 'the property of the Half-Blood Prince' and begins to learn more about Lord Voldemort's dark past."),
("Harry Potter and the Deathly Hallows: Part 1", "Harry Potter is tasked with the dangerous and seemingly impossible task of locating and destroying Voldemort's remaining Horcruxes. Harry must rely on Ron and Hermione more than ever, but dark forces threaten to tear them apart."),
("Harry Potter and the Deathly Hallows: Part 2", "Harry, Ron and Hermione set out on a quest to eliminate the remaining horcruxes. On the other hand, the students and teachers must unite to defend Hogwarts against Lord Voldemort and his army."),
("The Lord of the Rings: The Fellowship of the Ring", "A meek Hobbit from the Shire and eight companions set out on a journey to destroy the powerful One Ring and save Middle-earth from the Dark Lord Sauron."),
("The Lord of the Rings: The Two Towers", "While Frodo and Sam edge closer to Mordor with the help of the shifty Gollum, the divided fellowship makes a stand against Sauron's new ally, Saruman, and his hordes of Isengard."),
("The Lord of the Rings: The Return of the King", "Gandalf and Aragorn lead the World of Men against Sauron's army to draw his gaze from Frodo and Sam as they approach Mount Doom with the One Ring.");