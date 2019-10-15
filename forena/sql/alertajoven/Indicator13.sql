--ACCESS=access content
SELECT 
    *
FROM
    (SELECT 
        provider_id,
        Fem_Total,
        Mas_Total,
        Sex_Unk_Total,
        Les_15_Total,
        Bet_15_24_Total,
        Gre_24_Total,
        Unk_Total,
        fem_15_19_total,
        mas_15_19_total,
        fem_20_24_total,
        mas_20_24_total,
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
            SUM(CASE WHEN Sexo != 'M' AND Sexo != 'F' THEN 1 ELSE 0 END) AS Sex_Unk_Total,
            SUM(CASE WHEN age < 15 THEN 1 ELSE 0 END) AS Les_15_Total,
            SUM(CASE WHEN age >= 15 AND age <= 24 THEN 1 ELSE 0 END) AS Bet_15_24_Total,
            SUM(CASE WHEN age > 24 THEN 1 ELSE 0 END) AS Gre_24_Total,
			SUM(CASE WHEN age is null THEN 1 ELSE 0 END) AS Unk_Total,
			SUM(CASE WHEN sexo = 'F' AND age >= 15 AND age <= 19 THEN 1 ELSE 0 END) AS fem_15_19_total,
            SUM(CASE WHEN sexo = 'M' AND age >= 15 AND age <= 19 THEN 1 ELSE 0 END) AS mas_15_19_total,
            SUM(CASE WHEN sexo = 'F' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS fem_20_24_total,
            SUM(CASE WHEN sexo = 'M' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS mas_20_24_total,
            SUM(CASE WHEN 9Dóndenaciste = 'República Dominicana' THEN 1 ELSE 0 END) AS rep_dom_total,
            SUM(CASE WHEN 9Dóndenaciste = 'Haití' THEN 1 ELSE 0 END) AS haiti_total,
            SUM(CASE WHEN 9Dóndenaciste = 'Otro' THEN 1 ELSE 0 END) AS otro_total,   
            COUNT(uuid) AS Grand_Total
    FROM
        (SELECT DISTINCT
        reg.provider_id,
            field_agency_name_value as provider_name,
            reg.uuid,
            reg.sexo,
            DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 AS age,
            9Dóndenaciste
    FROM
        bitnami_drupal7.aj_survey sur
    JOIN bitnami_drupal7.aj_registration reg ON sur.uuid = reg.uuid join bitnami_drupal7.field_data_field_agency_name  on (sur.provider_id = field_data_field_agency_name.entity_id) join bitnami_drupal7.field_data_field_agency_active on (sur.provider_id = field_data_field_agency_active.entity_id)
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
and SUBSTRING(sur.createdAt, 1, 10) >= :from_date
--END
--IF=:to_date
and SUBSTRING(sur.createdAt, 1, 10) <= :to_date
--END
and reg.provider_id != ''
and reg.provider_id in (:provider_id) 
and field_agency_active_value = 1  
and field_data_field_agency_name.entity_id != 12              
            AND 16Actualmenteestasasistiendoa = 'Ninguno'
            AND (26Durantel = 'No' || 26Durantel = '')) uniqueRecords
    GROUP BY provider_id WITH ROLLUP) rollUP) as tb1
        right JOIN
   (SELECT 
            provider_id,
            CASE 
                WHEN provider_id = 'ALL_PROVIDERS' THEN 'ALL_PROVIDERS'
                ELSE provider_name
            END AS provider_name,
                Bet_15_24_Total_UNIVERSE,
                Total_UNIVERSE
        FROM
            (SELECT 
            IFNULL(provider_id, 'ALL_PROVIDERS') AS provider_id,
                provider_name,
                SUM(CASE
                    WHEN age >= 15 AND age <= 24 THEN 1
                    ELSE 0
                END) AS Bet_15_24_Total_UNIVERSE,
                COUNT( uuid) AS Total_UNIVERSE
        FROM
            (SELECT DISTINCT
            reg.provider_id,
                field_agency_name_value as provider_name,
                reg.uuid,
                DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 AS age
        FROM
            bitnami_drupal7.aj_survey sur
        JOIN bitnami_drupal7.aj_registration reg ON sur.uuid = reg.uuid join bitnami_drupal7.field_data_field_agency_name on (sur.provider_id = field_data_field_agency_name.entity_id) join bitnami_drupal7.field_data_field_agency_active on (sur.provider_id = field_data_field_agency_active.entity_id)
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
and SUBSTRING(sur.createdAt, 1, 10) >= :from_date
--END
--IF=:to_date
and SUBSTRING(sur.createdAt, 1, 10) <= :to_date
--END
and reg.provider_id != ''
and reg.provider_id in (:provider_id) 
and field_agency_active_value = 1
and field_data_field_agency_name.entity_id != 12
                ) uniqueRecords
        GROUP BY provider_id WITH ROLLUP) rollup2) as tb2 
        using (provider_id)
