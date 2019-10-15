--ACCESS=access content
SELECT 
    *
FROM
    (SELECT 
        provider_id, Fem_11_24_Total, Mas_11_24_Total, Unk_11_24_Total, 11_24_Grand_Total
    FROM
        (SELECT 
        IFNULL(provider_id, 'ALL_PROVIDERS') AS provider_id,
           provider_name,
            SUM(CASE
                WHEN sexo = 'F' and age >= 11 AND age <= 24 THEN 1
                ELSE 0
            END) AS Fem_11_24_Total,
            SUM(CASE
                WHEN sexo = 'M' and age >= 11 AND age <= 24 THEN 1
                ELSE 0
            END) AS Mas_11_24_Total,
            SUM(CASE
                WHEN Sexo != 'M' AND Sexo != 'F' and age >= 11 AND age <= 24 THEN 1
                ELSE 0
            END) AS Unk_11_24_Total,
            SUM(CASE
                WHEN age >= 11 AND age <= 24 THEN 1
                ELSE 0
            END) AS 11_24_Grand_Total
    FROM
        (SELECT DISTINCT
        reg.provider_id, reg.provider_name, reg.uuid, sexo, DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 AS age

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

--IF=:from_date_reg
and reg.Fecha >= :from_date_reg
--END
--IF=:to_date_reg
and reg.Fecha <= :to_date_reg
--END

and field_agency_active_value = 1
and field_data_field_agency_name.entity_id != 12

            AND (87BCondón = 'true'
            || 87CCondónfemenino = 'true'
            || 87DPíldoraanticonceptiva = 'true'
            || 87HDIUcomoMirenaoParagard = 'true'
            || 87IInyecci = 'true'
            || 87JImplant = 'true'
            || 87KEsterilizaciónfemenina = 'true'
            || 87LEsterilizaciónmasculina = 'true'
            || 87NOtro IN ('Anticonceptivo' , 'Aparatico ', 'Aparatico en el brazo ', 'APARATO AUTICONCEPTIVO', 'APARATO EN EL BRAZO', 'DIU', 'DOPLA', 'Emergencia', 'Estoy Preparada', 'EVITA', 'EVITAL', 'Implanon', 'INYECCION', 'INYECCION ANTICOMCEPTIVA', 'Inyección cada 3 Meses', 'Inyeccion de planificacion', 'LIGADAL', 'los tuvitos', 'Microtul', 'Noplan', 'OLANIFICACION', 'Operada para no tener hijos', 'PASTILLA', 'PASTILLA AFTHERDAY', 'PASTILLA AFTHERDAY', 'Pastilla anticonceptiva ', 'Pastilla de emergencia', 'pastilla de planificacion', 'pastilla emergencia', 'Pastilla Perla', 'pastillas', 'PASTILLAS DE EMERGENCIA', 'PASTILLAS DE PLANIFICACION', 'PASTILLAS PARA EVITAR EMBARAZO', 'planificacion', 'PLANIFICACION (PASTILLA)', 'PLANIFICACIÓN EN EL BRAZO', 'PLANIFICACION VIA ORAL', 'Planificación.', 'Planificada', 'Planificadora', 'PREPARADA', 'Tubito', 'Tuvitos', 'una pastilla', 'VACUNA'))) uniqueRecords
    GROUP BY provider_id WITH ROLLUP) rollUP) AS tb1
        RIGHT JOIN
    (SELECT 
        provider_id,
            CASE
                WHEN provider_id = 'ALL_PROVIDERS' THEN 'ALL_PROVIDERS'
                ELSE provider_name
            END AS provider_name,
            Bet_11_24_Total_UNIVERSE,
            Fem_Bet_11_24_Total_UNIVERSE,
            Mas_Bet_11_24_Total_UNIVERSE,
            TotalUNIVERSE
    FROM
        (SELECT 
        IFNULL(provider_id, 'ALL_PROVIDERS') AS provider_id,
           provider_name,
            SUM(CASE
                WHEN age >= 11 AND age <= 24 THEN 1
                ELSE 0
            END) AS Bet_11_24_Total_UNIVERSE,
            SUM(CASE
                WHEN age >= 11 AND age <= 24 AND sexo = 'F' THEN 1
                ELSE 0
            END) AS Fem_Bet_11_24_Total_UNIVERSE,
            SUM(CASE
                WHEN age >= 11 AND age <= 24 AND sexo = 'M' THEN 1
                ELSE 0
            END) AS Mas_Bet_11_24_Total_UNIVERSE,
            COUNT(uuid) AS TotalUNIVERSE
    FROM
        (SELECT DISTINCT
        reg.provider_id, field_agency_name_value as provider_name, reg.uuid, DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 AS age, sexo
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

--IF=:from_date_reg
and reg.Fecha >= :from_date_reg
--END
--IF=:to_date_reg
and reg.Fecha <= :to_date_reg
--END


and field_agency_active_value = 1
and field_data_field_agency_name.entity_id != 12
            AND 82Algunavezhastenidorelacionessexuales = 'Sí') uniqueRecords
    GROUP BY provider_id WITH ROLLUP) rollUP2) 
    AS tb2 USING (provider_id)