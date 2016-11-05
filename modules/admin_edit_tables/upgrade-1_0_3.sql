delete from wizard_logic;
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
