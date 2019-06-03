-- ACCESS=access content
SELECT
SUM(case when sub.Sexo = 'M' then 1 else 0 end) as 'Male', 
SUM(case when sub.Sexo = 'F' then 1 else 0 end) as 'Female',
SUM(CASE WHEN sexo = 'F' AND age >= 10 AND age <= 14 THEN 1 ELSE 0 END) AS fem_11_14_total,
SUM(CASE WHEN sexo = 'M' AND age >= 10 AND age <= 14 THEN 1 ELSE 0 END) AS mas_11_14_total,
SUM(CASE WHEN age >= 10 AND age <= 17 THEN 1 ELSE 0 END) AS 11_17_total,
SUM(CASE WHEN age >= 18 AND age <= 24 THEN 1 ELSE 0 END) AS 18_24_total,
SUM(CASE WHEN sexo = 'F' AND age >= 15 AND age <= 19 THEN 1 ELSE 0 END) AS fem_15_19_total,
SUM(CASE WHEN sexo = 'M' AND age >= 15 AND age <= 19 THEN 1 ELSE 0 END) AS mas_15_19_total,
SUM(CASE WHEN sexo = 'F' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS fem_20_24_total,
SUM(CASE WHEN sexo = 'M' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS mas_20_24_total,
SUM(CASE WHEN 9Dóndenaciste = 'República Dominicana' THEN 1 ELSE 0 END) AS rep_dom_total,
SUM(CASE WHEN 9Dóndenaciste = 'Haití' THEN 1 ELSE 0 END) AS haiti_total,
SUM(CASE WHEN 9Dóndenaciste = 'Otro' THEN 1 ELSE 0 END) AS otro_total, 
count(distinct uuid) as Grand_Total,  
IFNULL(ProgramName, '_ALL_PROGRAMS') AS 'ProgramName', 
sub.provider_id as 'provider_id',
IFNULL(Provider, '_ALL_PROVIDERS') AS 'Provider'
FROM (
SELECT distinct reg.uuid, reg.SEXO, reg.DOB, reg.fecha, 
DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 AS age,
9Dóndenaciste,
pnamename.field_programname_name_value as 'ProgramName', 
pp.field_program_provider_target_id as 'provider_id',
provider.field_agency_name_value as 'Provider',
aprog.field_activity_program_target_id as 'Program_id'

FROM bitnami_drupal7.aj_registration reg
join bitnami_drupal7.aj_attendance atten on atten.uuid=reg.uuid
LEFT JOIN bitnami_drupal7.aj_survey sur ON sur.uuid = reg.uuid
join bitnami_drupal7.field_data_field_agency_name provider on provider.entity_id=atten.provider_id
join bitnami_drupal7.field_data_field_activity_name aname on aname.entity_id=atten.activity_id
join bitnami_drupal7.field_data_field_activity_date adate on adate.entity_id=atten.activity_id
join bitnami_drupal7.field_data_field_activity_program aprog on aprog.entity_id=atten.activity_id
join bitnami_drupal7.field_data_field_program_provider pp on pp.entity_id=aprog.field_activity_program_target_id
join bitnami_drupal7.field_data_field_program_name pname on pname.entity_id=pp.entity_id
join bitnami_drupal7.field_data_field_programname_name pnamename on pnamename.entity_id=pname.field_program_name_target_id
where 1 = 1 
and reg.uuid in (select distinct(atten.uuid) from bitnami_drupal7.aj_attendance atten)
and atten.provider_id in (:provider_id) 

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

group by reg.uuid, pnamename.field_programname_name_value) sub
group by sub.Provider, sub.ProgramName WITH ROLLUP



