drop table if exists messages;
create table messages(
id integer unsigned primary key auto_increment,
from_user integer not null,
inbox_of integer not null,
sent_to varchar(255),
sent_on timestamp,
subject varchar(255),
has_attachement enum('yes','no') default 'no',
message text,
is_new enum('yes','no') default 'yes')
ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;