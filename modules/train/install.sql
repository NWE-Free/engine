drop table if exists trainings;
create table trainings(
id integer unsigned primary key,
name varchar(80),
description varchar(1024),
requirements text,
display_condition text,
nb_visits integer unsigned default 1,
do_only_once enum('yes','no') default 'yes',
effect varchar(255))
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

drop table if exists training_done;
create table training_done(
user_id integer unsigned,
training_id integer unsigned,
done_on timestamp,
primary key(user_id,training_id)
)ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;
