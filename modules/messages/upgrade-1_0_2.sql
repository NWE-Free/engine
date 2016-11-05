alter table messages add has_attachement enum('yes','no') default 'no';
update messages set has_attachement = 'yes' where message like '%--* MODULE:%';
