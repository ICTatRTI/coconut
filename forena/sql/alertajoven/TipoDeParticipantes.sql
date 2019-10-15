--ACCESS=access content
SELECT
     entity_id AS 'Organizacion Id',
    IFNULL(field_agency_name_value, '_TOTAL_') AS Organizacion,
    count(distinct uuid, 'Total') as 'Cantidad de Participantes'
FROM
    bitnami_drupal7.aj_registration
        JOIN
    bitnami_drupal7.field_data_field_agency_name ON bitnami_drupal7.field_data_field_agency_name.entity_id = bitnami_drupal7.aj_registration.provider_id
where     
1 =1
 
--SWITCH=:collateral
--CASE=collateral
and bitnami_drupal7.aj_registration.Estecolateralparticipante = 'Sí' 
--CASE=nonCollateral
and bitnami_drupal7.aj_registration.Estecolateralparticipante != 'Sí'
--END

--IF=:provider_id
and provider_id in (:provider_id)
AND provider_id != ''
AND uuid != ''

--IF=:from_date
and createdAt >= :from_date
--END
--IF=:to_date
and createdAt <= :to_date
--END
group by field_agency_name_value ASC