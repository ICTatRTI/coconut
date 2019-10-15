--ACCESS=access content
SELECT  DISTINCT
            reg.provider_id,
            field_agency_name_value as provider_name,
            reg.uuid,
            nombre,
            apellido,
            sexo,
            reg.dob,
            DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 AS age,
            9Dóndenaciste
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