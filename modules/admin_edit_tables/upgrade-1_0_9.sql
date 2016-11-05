replace into wizard_logic(name,code)
values('player is Premium Member','IsPremiumMember() == true');
replace into wizard_logic(name,code,label_1,param_1)
values('day hour <= X','(date("H")+0 <= @p1@)','Hour','number 0');
replace into wizard_logic(name,code,label_1,param_1)
values('day hour >= X','(date("H")+0 >= @p1@)','Hour','number 0');
replace into wizard_logic(name,code,label_1,param_1)
values('day hour = X','(date("H")+0 == @p1@)','Hour','number 0');
