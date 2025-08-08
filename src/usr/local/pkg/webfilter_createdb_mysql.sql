-- This script should be run only on MYSQL Databases
CREATE DATABASE IF NOT EXISTS webfilter
    DEFAULT CHARACTER SET latin1;

CREATE USER 'webfilter'@'localhost' IDENTIFIED BY PASSWORD '*F173D0793381C6DC13136F4FFB46FE933D81F464';
GRANT ALL PRIVILEGES ON webfilter.* TO 'webfilter'@'localhost';

