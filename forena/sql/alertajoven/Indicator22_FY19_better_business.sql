--ACCESS=access content
select * from (
    SELECT 
    provider_id,
    CASE
        WHEN provider_id = 'ALL_PROVIDERS' THEN 'ALL_PROVIDERS'
        ELSE provider_name
    END AS provider_name,
    Fem_Total,
    Mas_Total,
    Unk_Total,
    11_17_total,
    18_24_total,
    fem_15_19_total,
    mas_15_19_total,
    fem_20_24_total,
    mas_20_24_total,
    fem_25_29_total,
    mas_25_29_total,
    Grand_Total
FROM
    (select
    IFNULL(provider_id, 'ALL_PROVIDERS') AS provider_id,
    provider_name,
    sum(case when sexo = 'F' then 1 else 0 end) as Fem_Total,
    sum(case when sexo = 'M' then 1 else 0 end) as Mas_Total,
    SUM(case when Sexo != 'M' and Sexo != 'F' then 1 else 0 end) as Unk_Total,
    SUM(CASE WHEN age >= 11 AND age <= 17 THEN 1 ELSE 0 END) AS 11_17_total,
    SUM(CASE WHEN age >= 18 AND age <= 24 THEN 1 ELSE 0 END) AS 18_24_total,
    SUM(CASE WHEN sexo = 'F' AND age >= 15 AND age <= 19 THEN 1 ELSE 0 END) AS fem_15_19_total,
    SUM(CASE WHEN sexo = 'M' AND age >= 15 AND age <= 19 THEN 1 ELSE 0 END) AS mas_15_19_total,
    SUM(CASE WHEN sexo = 'F' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS fem_20_24_total,
    SUM(CASE WHEN sexo = 'M' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS mas_20_24_total,
    SUM(CASE WHEN sexo = 'F' AND age >= 25 AND age <= 29 THEN 1 ELSE 0 END) AS fem_25_29_total,
    SUM(CASE WHEN sexo = 'M' AND age >= 25 AND age <= 29 THEN 1 ELSE 0 END) AS mas_25_29_total,    
    count(distinct uuid) as Grand_Total
    from
(
SELECT distinct
    reg.uuid,
    reg.sexo,
    reg.dob,
    DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 AS age,
    reg.provider_id,
    agency.field_agency_name_value as provider_name
FROM
    bitnami_drupal7.aj_labor labor
        JOIN
    bitnami_drupal7.aj_registration reg ON labor.uuid = reg.uuid
    JOIN 
    bitnami_drupal7.field_data_field_agency_name agency on reg.provider_id = agency.entity_id
WHERE
  1 = 1 
--SWITCH=:collateral
-- estecolateralparticipante can have 1 of 4 values: No, Si, No Sabe (which means Don't know), blank (which means no value, not set)
--CASE=collateral
and reg.Estecolateralparticipante = 'Sí'
--CASE=nonCollateral
and reg.Estecolateralparticipante != 'Sí'
--END

--IF=:from_date
and SUBSTRING(labor.created, 1, 10) >= :from_date
--END
--IF=:to_date
and SUBSTRING(labor.created, 1, 10) <= :to_date
--END

--IF=:provider_id
and reg.provider_id in (:provider_id) 
--END

AND (
   (16_Siyateniasunnegocioconsiderasquedespuesdel = 'Sí' AND 17_Comohamejorado in ('Mejores ingresos',  'Mayores ingresos'))
)
GROUP BY UUID) uniqueRecords
group by provider_id WITH ROLLUP) rollUP)  as tb1

RIGHT JOIN

(
    SELECT 
    provider_id,
    CASE
        WHEN provider_id = 'ALL_PROVIDERS' THEN 'ALL_PROVIDERS'
        ELSE provider_name
    END AS provider_name,
    Universe_Fem_Total,
    Universe_Mas_Total,
    Universe_Unk_Total,
    Universe_Total
FROM
    (select
    IFNULL(provider_id, 'ALL_PROVIDERS') AS provider_id,
    provider_name,
    sum(case when sexo = 'F' then 1 else 0 end) as Universe_Fem_Total,
    sum(case when sexo = 'M' then 1 else 0 end) as Universe_Mas_Total,
    SUM(case when Sexo != 'M' and Sexo != 'F' then 1 else 0 end) as Universe_Unk_Total,
    count(distinct uuid) as Universe_Total
    from
(
SELECT distinct
    reg.uuid,
    reg.sexo,
    reg.dob,
    reg.provider_id,
    agency.field_agency_name_value as provider_name
FROM
    bitnami_drupal7.aj_labor labor
        JOIN
    bitnami_drupal7.aj_registration reg ON labor.uuid = reg.uuid
    JOIN 
    bitnami_drupal7.field_data_field_agency_name agency on reg.provider_id = agency.entity_id
WHERE
  1 = 1 
--SWITCH=:collateral
-- estecolateralparticipante can have 1 of 4 values: No, Si, No Sabe (which means Don't know), blank (which means no value, not set)
--CASE=collateral
and reg.Estecolateralparticipante = 'Sí'
--CASE=nonCollateral
and reg.Estecolateralparticipante != 'Sí'
--END

--IF=:from_date
and SUBSTRING(labor.created, 1, 10) >= :from_date
--END
--IF=:to_date
and SUBSTRING(labor.created, 1, 10) <= :to_date
--END

--IF=:provider_id
and reg.provider_id in (:provider_id) 
--END

GROUP BY UUID) uniqueRecords
group by provider_id WITH ROLLUP) rollUP)  as tb2 USING (provider_id)
order by provider_id asc
