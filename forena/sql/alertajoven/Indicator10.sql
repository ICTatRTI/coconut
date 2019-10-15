--ACCESS=access content
	SELECT 
    provider_id,
    CASE
        WHEN provider_id = 'ALL_PROVIDERS' THEN 'ALL_PROVIDERS'
        ELSE provider_name
    END AS provider_name,
    Fem_Total,
    Mas_Total,
    Unk_Total,
    less_11_total,
    fem_11_14_total,
    mas_11_14_total,
    11_17_total,
    18_24_total,
    fem_15_19_total,
    mas_15_19_total,
    fem_20_24_total,
    mas_20_24_total,
    fem_25_29_total,
    mas_25_29_total,
    rep_dom_Total,
    haiti_total,
    otro_total,
    Grand_Total
FROM
    (SELECT
    IFNULL(provider_id, 'ALL_PROVIDERS') AS provider_id,
    provider_name,
    sum(CASE WHEN sexo = 'F' THEN 1 ELSE 0 END) AS Fem_Total,
    sum(CASE WHEN sexo = 'M' THEN 1 ELSE 0 END) AS Mas_Total,
    SUM(CASE WHEN Sexo != 'M' AND Sexo != 'F' THEN 1 ELSE 0 END) AS Unk_Total,
    SUM(CASE WHEN age < 11 THEN 1 ELSE 0 END) AS less_11_total,
    SUM(CASE WHEN sexo = 'F' AND age >= 0 AND age <= 14 THEN 1 ELSE 0 END) AS fem_11_14_total, -- Includes less than 11 years
    SUM(CASE WHEN sexo = 'M' AND age >= 11 AND age <= 14 THEN 1 ELSE 0 END) AS mas_11_14_total,
    SUM(CASE WHEN age >= 11 AND age <= 17 THEN 1 ELSE 0 END) AS 11_17_total,
    SUM(CASE WHEN age >= 18 AND age <= 24 THEN 1 ELSE 0 END) AS 18_24_total,
    SUM(CASE WHEN sexo = 'F' AND age >= 15 AND age <= 19 THEN 1 ELSE 0 END) AS fem_15_19_total,
    SUM(CASE WHEN sexo = 'M' AND age >= 15 AND age <= 19 THEN 1 ELSE 0 END) AS mas_15_19_total,
    SUM(CASE WHEN sexo = 'F' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS fem_20_24_total,
    SUM(CASE WHEN sexo = 'M' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS mas_20_24_total,
    SUM(CASE WHEN sexo = 'F' AND age >= 25 AND age <= 29 THEN 1 ELSE 0 END) AS fem_25_29_total,
    SUM(CASE WHEN sexo = 'M' AND age >= 25 AND age <= 29 THEN 1 ELSE 0 END) AS mas_25_29_total,
    SUM(CASE WHEN 9Dóndenaciste = 'República Dominicana' THEN 1 ELSE 0 END) AS rep_dom_total,
    SUM(CASE WHEN 9Dóndenaciste = 'Haití' THEN 1 ELSE 0 END) AS haiti_total,
    SUM(CASE WHEN 9Dóndenaciste = 'Otro' THEN 1 ELSE 0 END) AS otro_total,   
    count(distinct uuid) as Grand_Total
    from
(
SELECT distinct
    reg.uuid,
    reg.sexo,
    reg.dob,
    DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 AS age,
    9Dóndenaciste,
    reg.provider_id,
    field_agency_name_value as provider_name
FROM
    bitnami_drupal7.aj_survey sur JOIN bitnami_drupal7.aj_registration reg ON sur.uuid = reg.uuid
    join bitnami_drupal7.field_data_field_agency_name agencyName on reg.provider_id = agencyName.entity_id
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
and reg.Fecha >= :from_date
--END
--IF=:to_date
and reg.Fecha <= :to_date
--END

and reg.provider_id in (:provider_id) 

AND 10Tienesunactadenacimientodominicana = 'No'
	) uniqueRecords
group by provider_id WITH ROLLUP) rollUP