USE webfilter;
DELIMITER $$
DROP PROCEDURE IF EXISTS AddColumnIfNotExists$$
CREATE PROCEDURE AddColumnIfNotExists(
	IN dbName tinytext,
	IN tableName tinytext,
	IN fieldName tinytext,
	IN fieldDef text)
BEGIN
	IF NOT EXISTS (
		SELECT * FROM information_schema.COLUMNS
		WHERE column_name=fieldName
		AND table_name=tableName
		AND table_schema=dbName
		)
	THEN
		SET @ddl=CONCAT('ALTER TABLE ',dbName,'.',tableName,
			' ADD COLUMN ',fieldName,' ',fieldDef);
		prepare stmt from @ddl;
		EXECUTE stmt;
	END IF;
END$$
DROP PROCEDURE IF EXISTS DropColumnIfExists$$
CREATE PROCEDURE DropColumnIfExists(
	IN dbName tinytext,
	IN tableName tinytext,
	IN fieldName tinytext)
BEGIN
	IF EXISTS (
		SELECT * FROM information_schema.COLUMNS
		WHERE column_name=fieldName
		AND table_name=tableName
		AND table_schema=dbName
		)
	THEN
		SET @ddl=CONCAT('ALTER TABLE ',dbName,'.',tableName,
			' DROP COLUMN ',fieldName);
		prepare stmt from @ddl;
		EXECUTE stmt;
	END IF;
END$$
DROP PROCEDURE IF EXISTS AddIndexIfNotExists$$
CREATE PROCEDURE AddIndexIfNotExists(
	IN dbName tinytext,
	IN tableName tinytext,
	IN fieldName tinytext,
	IN indexName tinytext)
BEGIN
	IF NOT EXISTS (
		SELECT * FROM information_schema.STATISTICS
		WHERE index_name=indexName
		AND table_name=tableName
		AND table_schema=dbName
		)
	THEN
		SET @ddl=CONCAT('CREATE INDEX ',indexName,' ON ',dbName,'.',tableName,
			' (',fieldName,')');
		prepare stmt from @ddl;
		EXECUTE stmt;
	END IF;
END$$
DROP PROCEDURE IF EXISTS updatedb$$
CREATE PROCEDURE updatedb()
BEGIN
  DECLARE dbversion BIGINT;
  IF(SELECT COUNT(version) FROM version) = 0 THEN
    INSERT INTO version VALUES(5);
  END IF;
  SET dbversion = ( SELECT version FROM version );
  IF dbversion = 0 THEN
    SET dbversion = 1;
    CALL AddColumnIfNotExists(DATABASE(),'accesses','url_str','VARCHAR(3072) NOT NULL');
    CALL AddColumnIfNotExists(DATABASE(),'accesses','url_no_qry','VARCHAR(3072)');
    CALL AddColumnIfNotExists(DATABASE(),'accesses','elapsed_ms','INTEGER');
    CALL AddColumnIfNotExists(DATABASE(),'accesses','size_bytes','INTEGER');
    CALL AddColumnIfNotExists(DATABASE(),'accesses','group_id','BIGINT');
    ALTER TABLE netfilter_log MODIFY category CHAR(16);
    UPDATE version SET version = dbversion;
  END IF;
  IF dbversion <= 1 THEN
    SET dbversion = 2;
    CREATE TABLE IF NOT EXISTS categories (
        id SMALLINT NOT NULL PRIMARY KEY,
        description VARCHAR(100)
    ) ENGINE = InnoDB;
    INSERT IGNORE INTO categories(id,description) VALUES
    (1,"Pornografia"),
    (2,"Musica"),
    (3,"Video"),
    (4,"Livro"),
    (5,"Emprego"),
    (6,"Esporte"),
    (7,"Jogos"),
    (8,"Humor"),
    (9,"Ensino a distância"),
    (10,"Batepapo"),
    (11,"Jornal"),
    (12,"Revista"),
    (13,"Animações"),
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
    (26,"Televisão"),
    (27,"Culinaria"),
    (28,"Armas"),
    (29,"Leilões"),
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
    (99,"Não categorizado");
    CALL DropColumnIfExists(DATABASE(),'accesses','category');
    UPDATE version SET version = dbversion;
  END IF;
  IF dbversion <= 2 THEN
    SET dbversion = 3;
    -- ==================================
    -- Creates all necessary foreign keys
    -- ==================================
    RENAME TABLE urls TO urls_nokeys;
    RENAME TABLE accesses TO accesses_nokeys;
    RENAME TABLE topsites TO topsites_nokeys;
    RENAME TABLE access_categories TO access_categories_nokeys;
    CREATE TABLE IF NOT EXISTS urls (
        id          SERIAL NOT NULL PRIMARY KEY,
        scheme_id   BIGINT UNSIGNED NOT NULL,
        host_id     BIGINT UNSIGNED NOT NULL,
        path_id     BIGINT UNSIGNED NOT NULL,
        query_id    BIGINT UNSIGNED,
        FOREIGN KEY fk_urls_scheme (scheme_id) REFERENCES schemes(id),
        FOREIGN KEY fk_urls_host (host_id) REFERENCES hosts(id),
        FOREIGN KEY fk_urls_paths (path_id) REFERENCES paths(id),
        FOREIGN KEY fk_urls_queries (query_id) REFERENCES queries(id)
    ) ENGINE = InnoDB;
    CREATE TABLE IF NOT EXISTS accesses (
        id          SERIAL NOT NULL PRIMARY KEY,
        time_date   TIMESTAMP NOT NULL,
        url_id      BIGINT UNSIGNED,
        url_str     VARCHAR(3072) NOT NULL,
        url_no_qry  VARCHAR(3072),
        categories  VARCHAR(3072) NOT NULL,
        elapsed_ms  INTEGER,
        size_bytes  INTEGER,
        blocked     BOOLEAN NOT NULL,
        ip          VARCHAR(15) NOT NULL,
        username    VARCHAR(255),
        groupname   VARCHAR(255),
        group_id    BIGINT,
        FOREIGN KEY fk_accesses_urls (url_id) REFERENCES urls(id)
    ) ENGINE = InnoDB;
    CREATE TABLE IF NOT EXISTS topsites (
        id          SERIAL NOT NULL PRIMARY KEY,
        host_id     BIGINT UNSIGNED NOT NULL UNIQUE,
        hits        INTEGER NOT NULL,
        elapsed_ms  INTEGER NOT NULL,
        size_bytes  INTEGER NOT NULL,
        FOREIGN KEY fk_topsites_hosts (host_id) REFERENCES hosts(id)
    ) ENGINE = InnoDB;
    CREATE TABLE IF NOT EXISTS access_categories (
        accesses_id BIGINT UNSIGNED NOT NULL,
        categories_id SMALLINT NOT NULL,
        FOREIGN KEY fk_accesses_categories_accesses (accesses_id) REFERENCES accesses(id) ON DELETE CASCADE,
        FOREIGN KEY fk_accesses_categories_categories (categories_id) REFERENCES categories(id)
    ) ENGINE = InnoDB;
    ALTER TABLE urls DISABLE KEYS;
    INSERT INTO urls (id, scheme_id, host_id, path_id, query_id)
    SELECT id, scheme_id, host_id, path_id, query_id
    FROM urls_nokeys;
    ALTER TABLE urls ENABLE KEYS;
    ALTER TABLE accesses DISABLE KEYS;
    INSERT INTO accesses (id, time_date, url_id, url_str, url_no_qry, elapsed_ms, size_bytes, blocked, ip, username, groupname, group_id)
    SELECT id, time_date, url_id, url_str, url_no_qry, categories, elapsed_ms, size_bytes, blocked, ip, username, groupname, group_id
    FROM accesses_nokeys;
    ALTER TABLE accesses ENABLE KEYS;
    ALTER TABLE topsites DISABLE KEYS;
    INSERT INTO topsites (id, host_id, hits, elapsed_ms, size_bytes)
    SELECT id, host_id, hits, elapsed_ms, size_bytes
    FROM topsites_nokeys;
    ALTER TABLE topsites ENABLE KEYS;
    ALTER TABLE access_categories DISABLE KEYS;
    INSERT INTO access_categories (accesses_id, categories_id)
    SELECT accesses_id, categories_id
    FROM access_categories;
    ALTER TABLE access_categories ENABLE KEYS;
    UPDATE version SET version = dbversion;
  END IF;
  IF dbversion <= 3 THEN
    SET dbversion = 4;
    ALTER TABLE access_categories DROP FOREIGN KEY `access_categories_ibfk_1`;
    ALTER TABLE access_categories ADD CONSTRAINT `access_categories_ibfk_1` FOREIGN KEY (`accesses_id`) REFERENCES `accesses` (`id`) ON DELETE CASCADE;
    CALL AddIndexIfNotExists(DATABASE(),'accesses','url_str','accesses_url_str_idx');
    CALL AddIndexIfNotExists(DATABASE(),'accesses','url_no_qry','accesses_url_no_qry_idx');
    CALL AddIndexIfNotExists(DATABASE(),'accesses','categories','accesses_categories_idx');
    CALL AddIndexIfNotExists(DATABASE(),'accesses','blocked','accesses_blocked_idx');
    CALL AddIndexIfNotExists(DATABASE(),'accesses','ip','accesses_ip_idx');
    CALL AddIndexIfNotExists(DATABASE(),'accesses','username','accesses_username_idx');
    CALL AddIndexIfNotExists(DATABASE(),'accesses','groupname','accesses_groupname_idx');
    CALL AddIndexIfNotExists(DATABASE(),'accesses','group_id','accesses_group_id_idx');
    CALL AddIndexIfNotExists(DATABASE(),'topsites','hits','topsites_hits_idx');
    CALL AddIndexIfNotExists(DATABASE(),'topsites','elapsed_ms','topsites_elapsed_ms_idx');
    CALL AddIndexIfNotExists(DATABASE(),'topsites','size_bytes','topsites_size_bytes_idx');
   
    CALL AddIndexIfNotExists(DATABASE(),'netfilter_log','ip','netfilter_log_ip');
    CALL AddIndexIfNotExists(DATABASE(),'netfilter_log','url','netfilter_log_url');
    CALL AddIndexIfNotExists(DATABASE(),'netfilter_log','time','netfilter_log_time');
    CALL AddIndexIfNotExists(DATABASE(),'netfilter_log','username','netfilter_log_username');
    CALL AddIndexIfNotExists(DATABASE(),'access_log','ip','access_log_ip');
    CALL AddIndexIfNotExists(DATABASE(),'access_log','url','access_log_url');
    CALL AddIndexIfNotExists(DATABASE(),'access_log','time','access_log_time');
    CALL AddIndexIfNotExists(DATABASE(),'access_log','username','access_log_username');
    CALL AddIndexIfNotExists(DATABASE(),'referer_log','ip','referer_log_ip');
    CALL AddIndexIfNotExists(DATABASE(),'referer_log','url','referer_log_url');
    CALL AddIndexIfNotExists(DATABASE(),'referer_log','time','referer_log_time');
    UPDATE version SET version = dbversion;
  END IF;
  IF dbversion <= 4 THEN
    SET dbversion = 5;
    CREATE TABLE IF NOT EXISTS users (
      id          SERIAL NOT NULL PRIMARY KEY,
      name        VARCHAR(255) NOT NULL UNIQUE,
      description VARCHAR(255),
      status      TINYINT(1)
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
  END IF;
  IF dbversion <= 5 THEN
   CALL AddColumnIfNotExists(DATABASE(),'accesses','categories','VARCHAR(3072)');
   CREATE TABLE IF NOT EXISTS `referers` (
     `id_referer` bigint(20) unsigned NOT NULL,
     `url_referer` varchar(3072) DEFAULT NULL,
     KEY `fk_refereres_accesses` (`id_referer`),
     CONSTRAINT `referers_ibfk_1` FOREIGN KEY (`id_referer`) REFERENCES `accesses` (`id`) ON DELETE CASCADE
     ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
    INSERT IGNORE INTO categories(id,description) VALUES (0,"Nao categorizado");
  END IF;
  IF dbversion >= 5 THEN
    -- Add rejected column in justification table
    CALL AddColumnIfNotExists(DATABASE(),'justification','rejected','TINYINT(1) DEFAULT 0');
    CALL AddColumnIfNotExists(DATABASE(),'justification','proxy_instance_id','INT(2) NOT NULL');
    CALL AddColumnIfNotExists(DATABASE(),'justification','proxy_instance_name','VARCHAR(80) NOT NULL');

    CALL AddIndexIfNotExists(DATABASE(),'accesses','time_date','idx_time_date');
  END IF;
END$$
DELIMITER ;
-- UPDATE DATABASE STRUCTURE
CALL updatedb();
-- DROP OLD PROCEDURES
DROP PROCEDURE IF EXISTS updatelogs;
DROP PROCEDURE IF EXISTS log_update;
DROP PROCEDURE IF EXISTS log_update2;
DROP PROCEDURE IF EXISTS log_clean;
-- DROP OLD FUNCTIONS
DROP FUNCTION IF EXISTS insert_scheme;
DROP FUNCTION IF EXISTS insert_host;
DROP FUNCTION IF EXISTS insert_path;
DROP FUNCTION IF EXISTS insert_query;
DROP FUNCTION IF EXISTS insert_urls;
-- DROP OLD VIEWS
DROP VIEW IF EXISTS complete_url;
DROP VIEW IF EXISTS domain_only;
DROP VIEW IF EXISTS topsites_view;
DELIMITER ;;
DROP PROCEDURE IF EXISTS rebuild_webfilter_db;
CREATE PROCEDURE `rebuild_webfilter_db`()
 BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE dbversion, check_exists, id_conn, id_access, url_id_access INT;
  DECLARE cur_access CURSOR FOR SELECT id, url_id FROM accesses WHERE url_id IS NOT NULL;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
  SET dbversion = ( SELECT version FROM version );
  IF dbversion <= 4 THEN
   -- Create table status update
   CREATE TABLE IF NOT EXISTS status_update (id_connection int(5));
   -- Verify processes running to avoid conflict
   IF NOT EXISTS (SELECT ID FROM information_schema.PROCESSLIST WHERE ID=(SELECT id_connection FROM status_update)) THEN
     -- Delete and insert id connection in the table status_update
     DELETE FROM status_update;
     INSERT status_update SET id_connection = (SELECT CONNECTION_ID());
     -- Disable check foreign key
     SET FOREIGN_KEY_CHECKS = 0;
     -- Add columns table accesses
     CALL AddColumnIfNotExists(DATABASE(),'accesses','url_path','VARCHAR(3072) DEFAULT ""');
     CALL AddColumnIfNotExists(DATABASE(),'groups','status','TINYINT(1) NOT NULL');
     -- Create index column url_path
     CALL AddIndexIfNotExists(DATABASE(),'accesses','url_path','accesses_url_path_idx');
     OPEN cur_access;
     read_loop: LOOP
       FETCH cur_access INTO id_access, url_id_access;
       IF done THEN
         LEAVE read_loop;
       END IF;
       -- Update data columns url_path and id_category in table accesses
       SET @update_access = CONCAT("UPDATE accesses SET url_path=(SELECT p.description from paths p INNER JOIN urls u ON u.path_id=p.id WHERE u.id=",url_id_access,") WHERE id=",id_access," and url_path = ''");
       PREPARE stmt FROM @update_access;
       EXECUTE stmt;
       DEALLOCATE PREPARE stmt;
     END LOOP;
     CLOSE cur_access;
     -- Drop tables
     DROP TABLES IF EXISTS urls_nokeys, access_categories_nokeys, accesses_nokeys, access_log, referer_log, netfilter_log, hosts, queries, schemes, topsites, topsites_nokeys, paths, urls, status_update;  
     -- Enable check foreign key
     SET FOREIGN_KEY_CHECKS = 1;
     -- Update version for version 5
     UPDATE version SET version = 5;
    END IF;
  END IF;
 END;;
DELIMITER ;
