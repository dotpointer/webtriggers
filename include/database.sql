CREATE DATABASE webtriggers;

USE webtriggers;

CREATE TABLE webtrigger_orders(
  id BIGINT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  id_webtriggers INT NOT NULL,
  returncode INT NOT NULL DEFAULT 0,
  output TEXT NOT NULL DEFAULT "",
  status INT NOT NULL DEFAULT 0,
  created DATETIME NOT NULL,
  started DATETIME NOT NULL,
  ended DATETIME NOT NULL
);
