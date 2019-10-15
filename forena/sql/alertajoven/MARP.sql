--ACCESS=access content
SELECT 
'HSH',
SUM(case when Sexo = 'M' then 1 else 0 end) as 'Male', 
SUM(case when Sexo = 'F' then 1 else 0 end) as 'Female',
SUM(case when Sexo = 'M' then 0 when Sexo = 'F' then 0 else 1 end) as 'Unknown',
SUM(CASE WHEN sexo = 'F' AND age >= 11 AND age <= 14 THEN 1 ELSE 0 END) AS fem_11_14_total,
SUM(CASE WHEN sexo = 'M' AND age >= 11 AND age <= 14 THEN 1 ELSE 0 END) AS mas_11_14_total,
SUM(CASE WHEN sexo = 'F' AND age >= 15 AND age <= 19 THEN 1 ELSE 0 END) AS fem_15_19_total,
SUM(CASE WHEN sexo = 'M' AND age >= 15 AND age <= 19 THEN 1 ELSE 0 END) AS mas_15_19_total,
SUM(CASE WHEN sexo = 'F' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS fem_20_24_total,
SUM(CASE WHEN sexo = 'M' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS mas_20_24_total,
SUM(case when DOB = null then 1 else 0 end) as 'UnknownAge',
count(uuid) as 'Total', 
entity_id as 'provider_id',
field_agency_name_value as provider_name
from (
select distinct 
    reg.uuid, 
    sexo, 
    dob, 
    DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 AS age,
    entity_id, 
    field_agency_name_value
FROM bitnami_drupal7.aj_registration reg
JOIN bitnami_drupal7.aj_survey sur ON sur.uuid = reg.uuid
JOIN bitnami_drupal7.field_data_field_agency_name provider on provider.entity_id=sur.provider_id
where 1 = 1 
--IF=:provider_id
	and provider.entity_id in (:provider_id)
--END
--SWITCH=:collateral
-- estecolateralparticipante can have 1 of 4 values: No, Si, No Sabe (which means Don't know), blank (which means no value, not set)
--CASE=collateral
and Estecolateralparticipante = 'Sí'
--CASE=nonCollateral
and Estecolateralparticipante != 'Sí'
--END

--IF=:from_date
and SUBSTRING(sur.createdAt, 1, 10) >= :from_date
--END
--IF=:to_date
and SUBSTRING(sur.createdAt, 1, 10) <= :to_date
--END
AND sexo = 'M'
AND (
        84Conquiéneshastenidorelacionessexuales = 'Sólo hombres' OR
        84Conquiéneshastenidorelacionessexuales = 'Hombres y mujeres'
    )
) distinctHSH
group by provider_id

UNION 

SELECT 
'TRSX',
SUM(case when Sexo = 'M' then 1 else 0 end) as 'Male', 
SUM(case when Sexo = 'F' then 1 else 0 end) as 'Female',
SUM(case when Sexo = 'M' then 0 when Sexo = 'F' then 0 else 1 end) as 'Unknown',
SUM(CASE WHEN sexo = 'F' AND age >= 11 AND age <= 14 THEN 1 ELSE 0 END) AS fem_11_14_total,
SUM(CASE WHEN sexo = 'M' AND age >= 11 AND age <= 14 THEN 1 ELSE 0 END) AS mas_11_14_total,
SUM(CASE WHEN sexo = 'F' AND age >= 15 AND age <= 19 THEN 1 ELSE 0 END) AS fem_15_19_total,
SUM(CASE WHEN sexo = 'M' AND age >= 15 AND age <= 19 THEN 1 ELSE 0 END) AS mas_15_19_total,
SUM(CASE WHEN sexo = 'F' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS fem_20_24_total,
SUM(CASE WHEN sexo = 'M' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS mas_20_24_total,
SUM(case when DOB = null then 1 else 0 end) as 'UnknownAge',
count(uuid) as 'Total', 
entity_id as 'provider_id',
field_agency_name_value as provider_name
from (
select distinct 
    reg.uuid, 
    sexo, 
    dob, 
    DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 AS age,
    entity_id, 
    field_agency_name_value
FROM bitnami_drupal7.aj_registration reg
JOIN bitnami_drupal7.aj_survey sur ON sur.uuid = reg.uuid
JOIN bitnami_drupal7.field_data_field_agency_name provider on provider.entity_id=sur.provider_id
where 1 = 1 
--IF=:provider_id
	and provider.entity_id in (:provider_id)
--END
--SWITCH=:collateral
-- estecolateralparticipante can have 1 of 4 values: No, Si, No Sabe (which means Don't know), blank (which means no value, not set)
--CASE=collateral
and Estecolateralparticipante = 'Sí'
--CASE=nonCollateral
and Estecolateralparticipante != 'Sí'
--END

--IF=:from_date
and SUBSTRING(sur.createdAt, 1, 10) >= :from_date
--END
--IF=:to_date
and SUBSTRING(sur.createdAt, 1, 10) <= :to_date
--END
and 89Algunave = 'Sí'
) distinctTRSX
group by provider_id

UNION

SELECT 
'PWID',
SUM(case when Sexo = 'M' then 1 else 0 end) as 'Male', 
SUM(case when Sexo = 'F' then 1 else 0 end) as 'Female',
SUM(case when Sexo = 'M' then 0 when Sexo = 'F' then 0 else 1 end) as 'Unknown',
SUM(CASE WHEN sexo = 'F' AND age >= 11 AND age <= 14 THEN 1 ELSE 0 END) AS fem_11_14_total,
SUM(CASE WHEN sexo = 'M' AND age >= 11 AND age <= 14 THEN 1 ELSE 0 END) AS mas_11_14_total,
SUM(CASE WHEN sexo = 'F' AND age >= 15 AND age <= 19 THEN 1 ELSE 0 END) AS fem_15_19_total,
SUM(CASE WHEN sexo = 'M' AND age >= 15 AND age <= 19 THEN 1 ELSE 0 END) AS mas_15_19_total,
SUM(CASE WHEN sexo = 'F' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS fem_20_24_total,
SUM(CASE WHEN sexo = 'M' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS mas_20_24_total,
SUM(case when DOB = null then 1 else 0 end) as 'UnknownAge',
count(uuid) as 'Total', 
entity_id as 'provider_id',
field_agency_name_value as provider_name
from (
select distinct 
    reg.uuid, 
    sexo, 
    dob, 
    DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 AS age,
    entity_id, 
    field_agency_name_value
FROM bitnami_drupal7.aj_registration reg
JOIN bitnami_drupal7.aj_survey sur ON sur.uuid = reg.uuid
JOIN bitnami_drupal7.field_data_field_agency_name provider on provider.entity_id=sur.provider_id
where 1 = 1 
--IF=:provider_id
	and provider.entity_id in (:provider_id)
--END
--SWITCH=:collateral
-- estecolateralparticipante can have 1 of 4 values: No, Si, No Sabe (which means Don't know), blank (which means no value, not set)
--CASE=collateral
and Estecolateralparticipante = 'Sí'
--CASE=nonCollateral
and Estecolateralparticipante != 'Sí'
--END

--IF=:from_date
and SUBSTRING(sur.createdAt, 1, 10) >= :from_date
--END
--IF=:to_date
and SUBSTRING(sur.createdAt, 1, 10) <= :to_date
--END
and 78Hasusado = 'Sí'
) distinctPWID
group by provider_id