drop table if exists inside_menu_ext;
create table inside_menu_ext(
name varchar(80) primary key,
category varchar(80),
link varchar(1024),
position integer unsigned) ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;
