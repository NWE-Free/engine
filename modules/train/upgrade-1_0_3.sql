alter table trainings change requirements requirements text;
alter table trainings add display_condition text;
alter table trainings add nb_visits integer unsigned default '1';
alter table trainings add do_only_once enum('yes','no') default 'yes';

create table training_done(
user_id integer unsigned,
training_id integer unsigned,
done_on timestamp,
primary key(user_id,training_id)
)ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;
