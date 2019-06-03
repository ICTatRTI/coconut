-- ACCESS=access content
SELECT 
ifnull(field_activity_name_value, "Total") as 'ServiceName', 
DATE_FORMAT(field_activity_date_value,'%m-%d-%Y')  as 'ActivityDate',
        sum(case when sexo = 'F' then 1 else 0 end) as Fem_Total,
        sum(case when sexo = 'M' then 1 else 0 end) as Mas_Total,
        SUM(case when Sexo != 'M' and Sexo != 'F' then 1 else 0 end) as Unk_Total,
        sum(case when age < 11 then 1 else 0 end) as Less_11_Total, 
        sum(case when age >= 10 and age <=17 then 1 else 0 end) as Bet_11_17_Total,
        sum(case when age >= 18 and age <= 24 then 1 else 0 end) as Bet_18_24_Total,
    --  sum(case when age > 24 then 1 else 0 end) as greater_24_Total,
        sum(case when age is null then 1 else 0 end) as unk_age_Total,
        sum(case when sexo = 'F' and age <11 then 1 else 0 end) as Fem_less_11_Total,
        sum(case when sexo = 'F' and age <=14 then 1 else 0 end) as Fem_11_14_Total, -- INCLUYE MENORES DE 11!!!
    --  sum(case when sexo = 'F' and age > 14 then 1 else 0 end) as Fem_great_14_Total,
        sum(case when sexo = 'F' and age >=10 and age <=17 then 1 else 0 end) as Fem_11_17_Total,
        sum(case when sexo = 'M' and age >=10 and age <=17 then 1 else 0 end) as Mas_11_17_Total,
        sum(case when sexo = 'M' and age < 11 then 1 else 0 end) as Mas_less_11_Total,
        sum(case when sexo = 'M' and age <= 14 then 1 else 0 end) as Mas_11_14_Total, -- INCLUYE MENORES DE 11!!!
    --  sum(case when sexo = 'M' and age > 14 then 1 else 0 end) as Mas_great_14_Total,
        sum(case when sexo = 'F' and age >=15 and age <=19 then 1 else 0 end) as Fem_15_19_Total,
        sum(case when sexo = 'M' and age >=15 and age <=19 then 1 else 0 end) as mas_15_19_Total,
        sum(case when sexo = 'F' and age >=18 and age <=24 then 1 else 0 end) as Fem_18_24_Total,
        sum(case when sexo = 'M' and age >=18 and age <=24 then 1 else 0 end) as Mas_18_24_Total,
        SUM(CASE WHEN sexo = 'F' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS fem_20_24_total,
        SUM(CASE WHEN sexo = 'M' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS mas_20_24_total,
        SUM(CASE WHEN sexo = 'F' AND age >= 25 AND age <= 29 THEN 1 ELSE 0 END) AS fem_25_29_total,
        SUM(CASE WHEN sexo = 'M' AND age >= 25 AND age <= 29 THEN 1 ELSE 0 END) AS mas_25_29_total,
        SUM(CASE WHEN 9Dóndenaciste = 'República Dominicana' THEN 1 ELSE 0 END) AS rep_dom_total,
        SUM(CASE WHEN 9Dóndenaciste = 'Haití' THEN 1 ELSE 0 END) AS haiti_total,
        SUM(CASE WHEN 9Dóndenaciste = 'Otro' THEN 1 ELSE 0 END) AS otro_total,   
count(Sexo) as 'Gender', 
count(uuid) as 'TotalUUID',
field_programname_name_value as 'Program', 
field_program_provider_target_id as 'provider_id',
field_agency_name_value AS 'provider',
activity_id
from
(select distinct
        aname.field_activity_name_value,
		adate.field_activity_date_value,
		reg.Sexo,
		reg.DOB,
		DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y')+0 AS age,
		9Dóndenaciste,
		pnamename.field_programname_name_value,
		pp.field_program_provider_target_id,
		reg.uuid,
		activity_id,
		field_agency_name_value,
		reg.Fecha
FROM bitnami_drupal7.aj_registration reg
join bitnami_drupal7.aj_attendance atten on atten.uuid=reg.uuid
LEFT JOIN bitnami_drupal7.aj_survey sur ON sur.uuid = reg.uuid
join bitnami_drupal7.field_data_field_agency_name provider on provider.entity_id=atten.provider_id
join bitnami_drupal7.field_data_field_activity_name aname on aname.entity_id=atten.activity_id
join bitnami_drupal7.field_data_field_activity_date adate on adate.entity_id=aname.entity_id
join bitnami_drupal7.field_data_field_activity_program aprog on aname.entity_id=aprog.entity_id
join bitnami_drupal7.field_data_field_program_provider pp on pp.entity_id=aprog.field_activity_program_target_id
join bitnami_drupal7.field_data_field_program_name pname on pname.entity_id=pp.entity_id
join bitnami_drupal7.field_data_field_programname_name pnamename on pname.field_program_name_target_id=pnamename.entity_id


where 1 = 1 
-- don't really need the 1 = 1 but if the other where's go away, it IS needed.
-- 
--IF=:provider_id
and pp.field_program_provider_target_id in (:provider_id)  
--END

--SWITCH=:collateral
-- estecolateralparticipante can have 1 of 4 values: No, Si, No Sabe (which means Don't know), blank (which means no value, not set)
--CASE=collateral
and reg.Estecolateralparticipante = 'Sí'
--CASE=nonCollateral
and reg.Estecolateralparticipante != 'Sí'
--END

--IF=:program_id
and pnamename.entity_id in (:program_id)  
--END

--IF=:from_date
and adate.field_activity_date_value >= :from_date
--END
--IF=:to_date
and adate.field_activity_date_value <= :to_date
--END

--group by aname.field_activity_name_value
--WITH ROLLUP
) distinctUUIDs
group by activity_id