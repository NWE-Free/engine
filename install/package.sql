-- package setup for medieval knight
-- default stats
insert into user_stat_types(id,name,initial_value,max_value,restore_rate) values(1,'Health',100,100,5);
insert into user_stat_types(id,name,initial_value) values(2,'Dexterity',10);
insert into user_stat_types(id,name,initial_value,on_change) values(3,'Strength',10,'$userStats["Health"]->maxValue=ceil($userStats["Strength"]->value*10);');
insert into user_stat_types(id,name,initial_value,max_value,restore_rate) values(4,'AP',20,20,0.5);
insert into user_stat_types(id,name,initial_value) values(5,'!Currency',100);
insert into user_stat_types(id,name,initial_value,on_change,display_code) values(6,'Experience',0,'CalcExperienceLevel();','ExpPercent()');
insert into user_stat_types(id,name,initial_value) values(7,'Level',1);

insert into trainings(id,name,description,requirements,effect)
values(1,'Make me stronger','Increase between 2-5 points of strength at the cost of 10 AP and 50 !Currency.',
'$userStats["!Currency"]->value >= 50.0 && $userStats["AP"]->value >= 10.0',
'$userStats["!Currency"]->value-=50;$userStats["AP"]->value-=10;$userStats["Strength"]->value+=rand(2,5);');

insert into trainings(id,name,description,requirements,effect)
values(2,'Make me quicker','Increase between 2-5 points of dexterity at the cost of 10 AP and 50 !Currency.',
'$userStats["!Currency"]->value >= 50.0 && $userStats["AP"]->value >= 10.0',
'$userStats["!Currency"]->value-=50;$userStats["AP"]->value-=10;$userStats["Dexterity"]->value+=rand(2,5);');

insert into object_types(id,name) values(1,'Weapons');
insert into object_types(id,name) values(2,'Helms');
insert into object_types(id,name) values(3,'Armor');
insert into object_types(id,name) values(4,'Boots');
insert into object_types(id,name,usage_label,usage_code)
values(5,'Food','Eat','$userStats["Health"]->value+=$object->Health;ResultMessage(Translate("You gained %d Health",$object->Health,FALSE));Item::InventoryRemove($object->id,$object->object_health,1);');
insert into object_types(id,name) value(6,'Potions');

insert into object_types_attributes(id,object_type,name) value(1,1,'Damage');
insert into object_types_attributes(id,object_type,name) value(2,2,'Protection');
insert into object_types_attributes(id,object_type,name) value(3,3,'Protection');
insert into object_types_attributes(id,object_type,name) value(4,4,'Protection');
insert into object_types_attributes(id,object_type,name) value(5,5,'Health');

insert into objects(id,name,description,price,object_type) values(1,'wooden stick','Taken from a bush, certainly one of the worse weapons out there.',1,1);
insert into object_attributes(object_id,attribute_id,value) values(1,1,'1');
insert into objects(id,name,description,price,object_type) values(2,'wooden club','Better than a stick... but nothing more.',10,1);
insert into object_attributes(object_id,attribute_id,value) values(2,1,'3');
insert into objects(id,name,description,price,object_type) values(3,'wooden sword','A sword for kids... yet can hurt if well used.',50,1);
insert into object_attributes(object_id,attribute_id,value) values(3,1,'5');
insert into objects(id,name,description,price,object_type) values(4,'rags','Better than being naked, I suppose',1,3);
insert into object_attributes(object_id,attribute_id,value) values(4,3,'1');
insert into objects(id,name,description,price,object_type) values(5,'apple','Some fresh fruit.',1,5);
insert into object_attributes(object_id,attribute_id,value) values(5,5,'3');
insert into objects(id,name,description,price,object_type) values(6,'dried meat','Gives a bit of energy.',3,5);
insert into object_attributes(object_id,attribute_id,value) values(6,5,'10');
insert into objects(id,name,description,price,object_type) values(7,'Leather Armor','Offers some protection but not much. Good for budget minded people.',20,3);
insert into object_attributes(object_id,attribute_id,value) values(7,3,'5');
insert into objects(id,name,description,price,object_type,usage_label,usage_code) values(8,'Health Potion','Allows you to recover up to 300 points of Health and will release you immediately from hospital.',20,6,'Drink','$userStats["Health"]->value+=300;ReleaseFromHospital();Item::InventoryRemove(8,1);');

insert into slots(id,name) values(1,'Weapon');
insert into slots(id,name) values(2,'Helm');
insert into slots(id,name) values(3,'Armor');
insert into slots(id,name) values(4,'Boots');

insert into slot_type_accepted(slot_id,object_type) value(1,1);
insert into slot_type_accepted(slot_id,object_type) value(2,2);
insert into slot_type_accepted(slot_id,object_type) value(3,3);
insert into slot_type_accepted(slot_id,object_type) value(4,4);

