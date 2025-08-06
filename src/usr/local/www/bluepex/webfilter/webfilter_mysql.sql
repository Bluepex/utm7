CREATE DATABASE IF NOT EXISTS webfilter
    DEFAULT CHARACTER SET latin1;
USE webfilter;
-- =============
-- CREATE TABLES
-- =============
CREATE TABLE IF NOT EXISTS version(
    version BIGINT NOT NULL
) ENGINE = InnoDB;
CREATE TABLE IF NOT EXISTS groups (
    id          SERIAL NOT NULL PRIMARY KEY,
    name        VARCHAR(255) NOT NULL UNIQUE,
    description VARCHAR(255),
    status      TINYINT(1)
) ENGINE = InnoDB;
CREATE TABLE IF NOT EXISTS users (
      id          SERIAL NOT NULL PRIMARY KEY,
      name        VARCHAR(255) NOT NULL UNIQUE,
      description VARCHAR(255),
      status      TINYINT(1)
    ) ENGINE = InnoDB;
CREATE TABLE IF NOT EXISTS categories (
    id SMALLINT NOT NULL PRIMARY KEY,
    description VARCHAR(100)
) ENGINE = InnoDB;
CREATE TABLE IF NOT EXISTS accesses (
    id          SERIAL NOT NULL PRIMARY KEY,
    time_date   TIMESTAMP NOT NULL,
    url_str     VARCHAR(3072) NOT NULL,
    url_path    VARCHAR(3072) NOT NULL,
    url_no_qry  VARCHAR(3072),
    categories  VARCHAR(3072) NOT NULL,
    elapsed_ms  INTEGER,
    size_bytes  INTEGER,
    blocked     BOOLEAN NOT NULL,
    ip          VARCHAR(15) NOT NULL,
    username    VARCHAR(255),
    groupname   VARCHAR(255),
    KEY `time_date` (`time_date`),
    KEY `idx_username` (`username`),
    KEY `idx_ip` (`ip`),
    KEY `idx_url_str` (`url_str`),
    KEY `idx_groupname` (`groupname`)
) ENGINE = InnoDB;
CREATE TABLE IF NOT EXISTS access_categories (
    accesses_id BIGINT UNSIGNED NOT NULL,
    categories_id SMALLINT NOT NULL,
    FOREIGN KEY fk_accesses_categories_accesses (accesses_id) REFERENCES accesses(id) ON DELETE CASCADE,
    FOREIGN KEY fk_accesses_categories_categories (categories_id) REFERENCES categories(id)
) ENGINE = InnoDB;
CREATE TABLE IF NOT EXISTS justification (
    id int(11) NOT NULL AUTO_INCREMENT,
    username varchar(255) NOT NULL,
    ip varchar(15) NOT NULL,
    reason varchar(200) NOT NULL,
    url_blocked varchar(3072) NOT NULL,
    time_date datetime NOT NULL,
    rejected tinyint(1) DEFAULT 0,
    proxy_instance_id INT(2) NOT NULL,
    proxy_instance_name VARCHAR(80) NOT NULL,
    status tinyint(1) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB;
INSERT IGNORE INTO categories(id,description) VALUES
    (1,"Pornografia"),
    (2,"Musica"),
    (3,"Video"),
    (4,"Livro"),
    (5,"Emprego"),
    (6,"Esporte"),
    (7,"Jogos"),
    (8,"Humor"),
    (9,"Ensino a distancia"),
    (10,"Batepapo"),
    (11,"Jornal"),
    (12,"Revista"),
    (13,"Animacoes"),
    (14,"Tutoriais"),
    (15,"Classificados"),
    (16,"Namoro on-line"),
    (17,"Curiosidades"),
    (18,"Compras"),
    (19,"Noticias"),
    (20,"Cartoes Virtuais"),
    (21,"Esoterismo"),
    (22,"Webmail"),
    (25,"Quadrinhos"),
    (26,"Televisao"),
    (27,"Culinaria"),
    (28,"Armas"),
    (29,"Leiloes"),
    (30,"Viagem"),
    (31,"Animais"),
    (32,"Hackers"),
    (33,"Filmes"),
    (34,"Fotografia"),
    (35,"Companhias Aereas"),
    (36,"Artes"),
    (37,"Carros"),
    (38,"Bancos"),
    (39,"Blogs"),
    (40,"Drogas"),
    (41,"Relacionamentos"),
    (42,"Saude"),
    (43,"Seitas e cultos"),
    (44,"Banner"),
    (45,"Proxy"),
    (46,"Sites de busca"),
    (47,"Violencia"),
    (48,"Portais"),
    (49,"Nazismo"),
    (50,"Downloads"),
    (99,"Nao categorizado");
