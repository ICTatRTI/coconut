-- ACCESS=access content
SELECT distinct
	provider_id,
    provider_name,
    uuid,
    nombre,
    apellido,
    apodo,
    sexo,
    dob,
    Estecolateralparticipante
FROM
    bitnami_drupal7.aj_registration 
WHERE
1 = 1
and 
	aj_registration.provider_id in (:provider_id)
and
    Estecolateralparticipante != 'Sí' 
AND 
    uuid NOT IN (SELECT DISTINCT uuid FROM bitnami_drupal7.aj_survey);