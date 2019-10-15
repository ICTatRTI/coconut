--ACCESS=access content
select * from (
    SELECT 
    provider_id,
    CASE
        WHEN provider_id = 'ALL_PROVIDERS' THEN 'ALL_PROVIDERS'
        ELSE provider_name
    END AS provider_name,
    zero_employees,
    one_employees,
    two_employees,
    three_employees,
    four_employees,
    five_employees,
    six_employees,
    seven_employees,
    eight_employees,
    nine_employees,
    more_ten_employees,
    Grand_Total
FROM
    (select
    IFNULL(provider_id, 'ALL_PROVIDERS') AS provider_id,
    provider_name,
    sum(case when 14_2CuantosEmpleadosEnTuEmpresa = 'Sólo yo' then 1 else 0 end) as zero_employees,
    sum(case when cast(14_2CuantosEmpleadosOtro AS UNSIGNED) = 1 then 1 else 0 end) as one_employees,
    sum(case when cast(14_2CuantosEmpleadosOtro AS UNSIGNED) = 2 then 1 else 0 end) as two_employees,
    sum(case when cast(14_2CuantosEmpleadosOtro AS UNSIGNED) = 3 then 1 else 0 end) as three_employees,
    sum(case when cast(14_2CuantosEmpleadosOtro AS UNSIGNED) = 4 then 1 else 0 end) as four_employees,
    sum(case when cast(14_2CuantosEmpleadosOtro AS UNSIGNED) = 5 then 1 else 0 end) as five_employees,
    sum(case when cast(14_2CuantosEmpleadosOtro AS UNSIGNED) = 6 then 1 else 0 end) as six_employees,
    sum(case when cast(14_2CuantosEmpleadosOtro AS UNSIGNED) = 7 then 1 else 0 end) as seven_employees,
    sum(case when cast(14_2CuantosEmpleadosOtro AS UNSIGNED) = 8 then 1 else 0 end) as eight_employees,
    sum(case when cast(14_2CuantosEmpleadosOtro AS UNSIGNED) = 9 then 1 else 0 end) as nine_employees,
    sum(case when cast(14_2CuantosEmpleadosOtro AS UNSIGNED) >= 10 then 1 else 0 end) as more_ten_employees,
    count(distinct uuid) as Grand_Total
    from
(
SELECT distinct
    reg.uuid,
    reg.sexo,
    reg.dob,
    DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 AS age,
    reg.provider_id,
    14_2CuantosEmpleadosEnTuEmpresa,
    14_2CuantosEmpleadosOtro,
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
      19_1CuantosEmpleadosEnTuEmpresa = 'Sólo yo'
   OR 19_1CuantosEmpleadosOtro != ''
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
