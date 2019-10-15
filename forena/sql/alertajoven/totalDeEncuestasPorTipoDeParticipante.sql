--ACCESS=access content
SELECT 
    IFNULL(field_agency_name_value, '_TOTAL_') AS Organizacion,
    count(distinct aj_survey.uuid, 'Total') as 'Cantidad de Participantes'
FROM
    bitnami_drupal7.aj_survey
    JOIN bitnami_drupal7.field_data_field_agency_name ON field_data_field_agency_name.entity_id = aj_survey.provider_id
    JOIN bitnami_drupal7.aj_registration ON aj_survey.uuid = aj_registration.uuid
WHERE     
1 =1
 
--SWITCH=:collateral
--CASE=collateral
and Estecolateralparticipante = 'Sí' 
--CASE=nonCollateral
and Estecolateralparticipante != 'Sí'
--END

--IF=:provider_id
and aj_survey.provider_id in (:provider_id)
AND aj_survey.provider_id != ''
AND aj_survey.uuid != ''

--IF=:from_date
and aj_survey.createdAt >= :from_date
--END
--IF=:to_date
and aj_survey.createdAt <= :to_date
--END
group by field_agency_name_value WITH ROLLUP