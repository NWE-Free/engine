drop table if exists users;
create table users(
id integer unsigned primary key auto_increment,
username varchar(20) not null unique,
password varchar(40) not null,
email varchar(255) unique,
blocked_module varchar(80),
last_action timestamp,
stats_modified enum('yes','no') default 'no',
online enum('yes','no') default 'no',
created_on date)
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

insert into users(id,username,password,email,created_on) values(1,'system','*','do-not-use',NOW());

drop table if exists bad_trials;
create table bad_trials(
user_id integer unsigned primary key,
ip varchar(32),
nb_trials integer default 1,
last_tried timestamp)
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists user_roles;
create table user_roles(
user_id integer unsigned not null,
role_id integer unsigned not null,
primary key (user_id, role_id))
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists roles;
create table roles(
id integer unsigned primary key,
name varchar(30) unique)
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

insert into roles(id,name) value(1000,'Super User');
insert into roles(id,name) value(900,'Administrator');
insert into roles(id,name) value(500,'Moderator');

drop table if exists user_stats;
create table user_stats(
user_id integer unsigned not null,
stat_type integer unsigned,
value double default 0,
max_value double,
primary key (user_id, stat_type))
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists user_stat_types;
create table user_stat_types(
id integer unsigned primary key,
name varchar(30),
description varchar(1024),
position integer default 0,
stat_bar enum('yes','no') default 'yes',
initial_value double default 0,
max_value double,
min_value double default 0,
code_when_min text default null,
on_change text default null,
display_code text default null,
restore_rate double)
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists user_variables;
create table user_variables(
user_id integer unsigned not null,
variable_id integer unsigned not null,
value varchar(1024),
primary key (user_id, variable_id))
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists variables;
create table variables(
id integer unsigned primary key,
name varchar(80),
description varchar(255))
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists modules;
create table modules(
name varchar(80) primary key,
version varchar(10) default '1.0')
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists object_types;
create table object_types(
id integer unsigned primary key,
name varchar(80),
usage_label varchar(80),
usage_code text)
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists object_types_attributes;
create table object_types_attributes(
id integer unsigned primary key,
object_type integer unsigned not null,
name varchar(40))
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists objects;
create table objects(
id integer unsigned primary key,
name varchar(80),
description varchar(1024),
requirements varchar(255),
durability double default 0,
price double default 0,
object_type integer unsigned not null,
allow_fraction enum('yes','no') default 'no',
quest_item enum('yes','no') default 'no',
image_file varchar(80),
usage_label varchar(80),
usage_code text)
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists object_attributes;
create table object_attributes(
object_id integer unsigned not null,
attribute_id integer unsigned not null,
value varchar(255),
primary key (object_id,attribute_id))
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists inventory;
create table inventory(
user_id integer unsigned not null,
object_id integer unsigned not null,
health double default 1,
quantity double default 1,
primary key(user_id,object_id,health))
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists slots;
create table slots(
id integer unsigned primary key,
name varchar(80))
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists slot_type_accepted;
create table slot_type_accepted(
slot_id integer unsigned not null,
object_type integer unsigned not null,
primary key (slot_id,object_type))
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists equiped;
create table equiped(
user_id integer unsigned not null,
slot_id integer unsigned not null,
object_id integer unsigned not null,
health double,
primary key(user_id, slot_id))
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists module_config_values;
create table module_config_values(
module varchar(80),
name varchar(80),
value varchar(255),
primary key (module,name))
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;
