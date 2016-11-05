drop table if exists wizard_logic;
create table wizard_logic(
id integer unsigned primary key auto_increment,
name varchar(80) unique,
code varchar(255),
label_1 varchar(20),
label_2 varchar(20),
label_3 varchar(20),
label_4 varchar(20),
label_5 varchar(20),
param_1 varchar(1024),
param_2 varchar(1024),
param_3 varchar(1024),
param_4 varchar(1024),
param_5 varchar(1024)) ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

insert into wizard_logic(name,code,label_1,param_1,label_2,param_2)
values('stat <= value','$userStats["@p1@"]->value <= @p2@','Stat','select name from user_stat_types order by name','Value','number');
insert into wizard_logic(name,code,label_1,param_1,label_2,param_2)
values('stat >= value','$userStats["@p1@"]->value >= @p2@','Stat','select name from user_stat_types order by name','Value','number');
insert into wizard_logic(name,code,label_1,param_1,label_2,param_2)
values('stat <> value','$userStats["@p1@"]->value != @p2@','Stat','select name from user_stat_types order by name','Value','number');
insert into wizard_logic(name,code,label_1,param_1,label_2,param_2)
values('stat = value','$userStats["@p1@"]->value == @p2@','Stat','select name from user_stat_types order by name','Value','number');
insert into wizard_logic(name,code,label_1,param_1)
values('quest started','IsQuestStarted(@p1@)','Quest','select id,name from quests order by name');
insert into wizard_logic(name,code,label_1,param_1)
values('quest not started','!IsQuestStarted(@p1@)','Quest','select id,name from quests order by name');
insert into wizard_logic(name,code,label_1,param_1)
values('quest goal comlpeted','AreAllGoalReached(@p1@)','Quest','select id,name from quests order by name');
insert into wizard_logic(name,code,label_1,param_1)
values('quest goal not comlpeted','!AreAllGoalReached(@p1@)','Quest','select id,name from quests order by name');
insert into wizard_logic(name,code,label_1,param_1)
values('quest finished','IsQuestFinished(@p1@)','Quest','select id,name from quests order by name');
insert into wizard_logic(name,code,label_1,param_1)
values('quest not finished','!IsQuestFinished(@p1@)','Quest','select id,name from quests order by name');
insert into wizard_logic(name,code)
values('in jail','IsInJail()');
insert into wizard_logic(name,code)
values('not in jail','!IsInJail()');
insert into wizard_logic(name,code)
values('in hospital','IsInHospital()');
insert into wizard_logic(name,code)
values('not in hospital','!IsInHospital()');
insert into wizard_logic(name,code,label_1,param_1,label_2,param_2)
values('own >= nb items','Item::OwnedNumber(@p1@) >= @p2@','Item','select id,name from objects order by name','Quantity','number');
insert into wizard_logic(name,code,label_1,param_1,label_2,param_2)
values('own <= nb items','Item::OwnedNumber(@p1@) <= @p2@','Item','select id,name from objects order by name','Quantity','number');
insert into wizard_logic(name,code,label_1,param_1)
values('is wearing','Item::IsWearing(@p1@)','Item','select id,name from objects order by name');
insert into wizard_logic(name,code,label_1,param_1)
values('is not wearing','!Item::IsWearing(@p1@)','Item','select id,name from objects order by name');
insert into wizard_logic(name,code,label_1,param_1,label_2,param_2)
values('user variable <= value','GetUserVariable(@p1@)+0 <= @p2@','UserVariable','variable','Value','number 0');
insert into wizard_logic(name,code,label_1,param_1,label_2,param_2)
values('user variable >= value','GetUserVariable(@p1@)+0 >= @p2@','UserVariable','variable','Value','number 0');
insert into wizard_logic(name,code,label_1,param_1,label_2,param_2)
values('user variable <> value','GetUserVariable(@p1@)+0 != @p2@','UserVariable','variable','Value','number 0');
insert into wizard_logic(name,code,label_1,param_1,label_2,param_2)
values('user variable = value','GetUserVariable(@p1@)+0 == @p2@','UserVariable','variable','Value','number 0');
insert into wizard_logic(name,code,label_1,param_1)
values('is member of clan X','IsClanMemberOf(@p1@) == true','Clan','select id,name from clans order by name');
insert into wizard_logic(name,code,label_1,param_1)
values('is NOT member of clan X','IsClanMemberOf(@p1@) == false','Clan','select id,name from clans order by name');
insert into wizard_logic(name,code)
values('is clan master','IsClanMaster() == true');
insert into wizard_logic(name,code)
values('is NOT clan master','IsClanMaster() == false');
insert into wizard_logic(name,code,label_1,param_1)
values('can propose quest X','CanProposeQuest(@p1@) == false','Quest','select id,name from quests order by name');
insert into wizard_logic(name,code)
values('always false','false');
insert into wizard_logic(name,code)
values('always true','true');
insert into wizard_logic(name,code,label_1,param_1)
values('can propose shop X','CanProposeShop(@p1@) == true','Shop','select id,name from npc_shops order by name');
insert into wizard_logic(name,code,label_1,param_1)
values('can NOT propose shop X','CanProposeShop(@p1@) == false','Shop','select id,name from npc_shops order by name');
insert into wizard_logic(name,code,label_1,param_1)
values('player in location','CurrentPlayerLocation() == @p1@','Location','select id,name from locations order by name');
insert into wizard_logic(name,code,label_1,param_1)
values('player NOT in location','CurrentPlayerLocation() != @p1@','Location','select id,name from locations order by name');
insert into wizard_logic(name,code)
values('player is Premium Member','IsPremiumMember() == true');
insert into wizard_logic(name,code,label_1,param_1)
values('day hour <= X','(date("H")+0 <= @p1@)','Hour','number 0');
insert into wizard_logic(name,code,label_1,param_1)
values('day hour >= X','(date("H")+0 >= @p1@)','Hour','number 0');
insert into wizard_logic(name,code,label_1,param_1)
values('day hour = X','(date("H")+0 == @p1@)','Hour','number 0');

drop table if exists wizard_actions;
create table wizard_actions(
id integer unsigned primary key auto_increment,
name varchar(80) unique,
code varchar(255),
label_1 varchar(20),
label_2 varchar(20),
label_3 varchar(20),
label_4 varchar(20),
label_5 varchar(20),
param_1 varchar(1024),
param_2 varchar(1024),
param_3 varchar(1024),
param_4 varchar(1024),
param_5 varchar(1024)) ENGINE InnoDB, CHARACTER SET utf8, COLLATE utf8_bin;

delete from wizard_actions;
insert into wizard_actions(name,code,label_1,param_1)
values('Add stat last random','/*rnd*/$userStats["@p1@"]->value+=$v','Stat','select name from user_stat_types order by name');
insert into wizard_actions(name,code,label_1,param_1)
values('Remove stat last random','/*rnd*/$userStats["@p1@"]->value-=$v','Stat','select name from user_stat_types order by name');
insert into wizard_actions(name,code,label_1,param_1,label_2,param_2)
values('Add stat','$userStats["@p1@"]->value+=@p2@','Stat','select name from user_stat_types order by name','Value','number');
insert into wizard_actions(name,code,label_1,param_1,label_2,param_2)
values('Remove stat','$userStats["@p1@"]->value-=@p2@','Stat','select name from user_stat_types order by name','Value','number');
insert into wizard_actions(name,code,label_1,param_1,label_2,param_2,label_3,param_3)
values('Pick random value','$v=rand(@p1@,@p2@)*@p3@','Min','number 0','Max','number 100','Multiplicator','number 1');
insert into wizard_actions(name,code,label_1,param_1,label_2,param_2)
values('Add item','Item::InventoryAdd(@p1@,@p2@)','Item','select id,name from objects order by name','Quantity','number');
insert into wizard_actions(name,code,label_1,param_1,label_2,param_2)
values('Remove item','Item::InventoryRemove(@p1@,@p2@)','Item','select id,name from objects order by name','Quantity','number');
insert into wizard_actions(name,code)
values('Out of hospital','ReleaseFromHospital()');
insert into wizard_actions(name,code,label_1,param_1)
values('Put in hospital','PutInHospital(@p1@)','Seconds','number');
insert into wizard_actions(name,code)
values('Out of jail','ReleaseFromJail()');
insert into wizard_actions(name,code,label_1,param_1)
values('Put in jail','PutInJail(@p1@)','Seconds','number');
insert into wizard_actions(name,code,label_1,param_1,label_2,param_2)
values('Quest action','QuestCallback("@p1@",urldecode("@p2@"))','Action','select name from known_quests_actions order by name','What','text');
insert into wizard_actions(name,code,label_1,param_1,label_2,param_2)
values('Start combat','StartNPCCombat(@p1@,urldecode("@p2@"))','NPC','select id, name from npc_warriors order by name','Return URL','text');
insert into wizard_actions(name,code,label_1,param_1)
values('Reduce hospital','ReduceHospitalTime(@p1@)','Time','number');
insert into wizard_actions(name,code,label_1,param_1,label_2,param_2)
values('Set User Variable','/*direct*/SetUserVariable(@p1@,@p2@)','UserVariable','variable','Value','number 0');
insert into wizard_actions(name,code,label_1,param_1,label_2,param_2)
values('Add User Variable','SetUserVariable(@p1@,GetUserVariable(@p1@)+@p2@)','UserVariable','variable','Value','number 0');
insert into wizard_actions(name,code,label_1,param_1,label_2,param_2)
values('Remove User Variable','SetUserVariable(@p1@,GetUserVariable(@p1@)-@p2@)','UserVariable','variable','Value','number 0');
insert into wizard_actions(name,code,label_1,param_1)
values('Start Quest','StartQuest(@p1@)','Quest','select id,name from quests order by name');
insert into wizard_actions(name,code,label_1,param_1)
values('Close Quest','CloseQuest(@p1@)','Quest','select id,name from quests order by name');
insert into wizard_actions(name,code,label_1,param_1)
values('Show Shop','ShowShop(@p1@)','Shop','select id,name from npc_shops order by name');
insert into wizard_actions(name,code,label_1,param_1)
values('Set Player Location','SetPlayerLocation(@p1@)','Location','select id,name from locations order by name');
insert into wizard_actions(name,code,label_1,param_1)
values('Show Message','ResultMessage(rawurldecode("@p1@"))','Message','text');