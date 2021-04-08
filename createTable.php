<?php
require_once "pdo.php";
session_start();
// $sql = '''CREATE DATABASE tracker;
// GRANT ALL ON tracker.* TO 'epiz_27750907'@'localsql308.epizy.com' IDENTIFIED BY 'tJi41geApa';
// GRANT ALL ON tracker.* TO 'epiz_27750907'@'3306' IDENTIFIED BY 'tJi41geApa';
// USE tracker;
// '''
// $stmt = $pdo->prepare($sql);
// $stmt->execute();

// CREATE DATABASE tracker;
// GRANT ALL ON tracker.* TO 'kris'@'localhost' IDENTIFIED BY '106123';
// GRANT ALL ON tracker.* TO 'kris'@'127.0.0.1' IDENTIFIED BY '106123';

$sql = '''
CREATE TABLE Location (
    id INTEGER NOT NULL AUTO_INCREMENT KEY,
    name VARCHAR(30) NOT NULL,
    text VARCHAR(256) NOT NULL
) ENGINE=InnoDB CHARSET=utf8;

CREATE TABLE Type (
    id INTEGER NOT NULL AUTO_INCREMENT KEY,
    name VARCHAR(30) NOT NULL,
    text VARCHAR(256) NOT NULL
) ENGINE=InnoDB CHARSET=utf8;

CREATE TABLE User (
    id INTEGER NOT NULL AUTO_INCREMENT KEY,
    name VARCHAR(30) NOT NULL,
    email VARCHAR(128) NOT NULL UNIQUE,
    password VARCHAR(128) NOT NULL
) ENGINE=InnoDB CHARSET=utf8;

CREATE TABLE Family (
    id INTEGER NOT NULL AUTO_INCREMENT KEY,
    name VARCHAR(30) NOT NULL,
    admin INTEGER NOT NULL
) ENGINE=InnoDB CHARSET=utf8;

// CREATE TABLE Device (
//     id INTEGER NOT NULL AUTO_INCREMENT KEY,
//     name VARCHAR(30) NOT NULL,
//     location VARCHAR(50),
//     type VARCHAR(30),
//     ip_address VARCHAR(128),
//     admin INTEGER NOT NULL
// ) ENGINE=InnoDB CHARSET=utf8;

CREATE TABLE Device (
    id INTEGER NOT NULL AUTO_INCREMENT KEY,
    name VARCHAR(30) NOT NULL,
    location INTEGER,
    type INTEGER,
    ip_address VARCHAR(128),
    admin INTEGER NOT NULL,

    CONSTRAINT FOREIGN KEY (location) REFERENCES Location (id) ON UPDATE CASCADE,
    CONSTRAINT FOREIGN KEY (type) REFERENCES Type (id) ON UPDATE CASCADE
) ENGINE=InnoDB CHARSET=utf8;

CREATE TABLE Record (
    device_id INTEGER NOT NULL,
    time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    current FLOAT NOT NULL,
    voltage INTEGER NOT NULL,

    CONSTRAINT FOREIGN KEY (device_id) REFERENCES Device (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB CHARSET=utf8;

CREATE TABLE FamilyMap (
    rank INTEGER NOT NULL AUTO_INCREMENT KEY,
    user_id INTEGER NOT NULL,
    family_id INTEGER NOT NULL,

    CONSTRAINT FOREIGN KEY (user_id) REFERENCES User (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT FOREIGN KEY (family_id) REFERENCES Family (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB CHARSET=utf8;

CREATE TABLE DeviceMap (
    rank INTEGER NOT NULL AUTO_INCREMENT KEY,
    device_id INTEGER NOT NULL,
    user_id INTEGER,
    family_id INTEGER,

    CONSTRAINT FOREIGN KEY (device_id) REFERENCES Device (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT FOREIGN KEY (user_id) REFERENCES User (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT FOREIGN KEY (family_id) REFERENCES Family (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB CHARSET=utf8;
'''

$stmt = $pdo->prepare($sql);
$stmt->execute();

$sql =  '''
INSERT INTO Type (name, text) values ('Lighting', 'Lighting');
INSERT INTO Type (name, text) values ('A/C', 'Air Conditioning');
INSERT INTO Type (name, text) values ('Fan', 'Fan');
INSERT INTO Type (name, text) values ('Heater', 'Heater');
INSERT INTO Type (name, text) values ('Humidity/Air Quality', 'Humidifier/Dehumidifier/Air Purifier/etc.');
INSERT INTO Type (name, text) values ('Food Processor', 'Food Processor/Blender/Mixer/Coffee Maker/Toaster/etc.');
INSERT INTO Type (name, text) values ('Cooking', 'Microwave/Oven/Rice Cooker/Stove/etc.');
INSERT INTO Type (name, text) values ('Freezer/Refrigerator', 'Freezer/Refrigerator');
INSERT INTO Type (name, text) values ('Dishwasher', 'Dishwasher');
INSERT INTO Type (name, text) values ('Laundry', 'Laundry Washer/Dryer');
INSERT INTO Type (name, text) values ('Computer', 'Desktop/Laptop/Tablet/etc.');
INSERT INTO Type (name, text) values ('Cleaning', 'Electric Scrubber/Vacuum/etc.');
INSERT INTO Type (name, text) values ('Clothes', 'Iron/Steamer/etc.');
INSERT INTO Type (name, text) values ('Personal Electronics', 'Electric Toothbrush/Hair Dryer/etc.');
INSERT INTO Type (name, text) values ('Other', 'Other Electronics');
INSERT INTO Type (name, text) values ('Boiler', 'Boiler');

INSERT INTO Location (name) values ('Living Room');
INSERT INTO Location (name) values ('Dinning Room');
INSERT INTO Location (name) values ('Kitchen');
INSERT INTO Location (name) values ('Bedroom 1');
INSERT INTO Location (name) values ('Bedroom 2');
INSERT INTO Location (name) values ('Bedroom 3');
INSERT INTO Location (name) values ('Bath Room 1');
INSERT INTO Location (name) values ('Bath Room 2');
INSERT INTO Location (name) values ('Bath Room 3');
INSERT INTO Location (name) values ('Dressing Room');
INSERT INTO Location (name) values ('Study');
INSERT INTO Location (name) values ('Home Office');
INSERT INTO Location (name) values ('Garden / Yard');
INSERT INTO Location (name) values ('Entryway / Hallway');
INSERT INTO Location (name) values ('Roof / Attic');
INSERT INTO Location (name) values ('Balcony / Terrace');
INSERT INTO Location (name) values ('Storage');
INSERT INTO Location (name) values ('Basement');
INSERT INTO Location (name) values ('Garage');
'''
?>