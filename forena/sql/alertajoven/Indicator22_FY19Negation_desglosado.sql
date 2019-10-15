--ACCESS=access content
Select * from (SELECT distinct
    version,
    reg.uuid,
    reg.nombre,
    reg.apellido,
    reg.sexo,
    reg.dob,
    reg.barrioComunidad,
    reg.calleynumero,
    reg.municipio,
    reg.provincia,
    reg.casa,
    reg.celular,
    reg.télefono as telefono,
    DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 AS age,
    reg.provider_id,
    agency.field_agency_name_value as provider_name,
    4_2Esestetuprimerempleo,
    14_Tienesunnegociopropio,
    8_Cuandoiniciasteelcursotecnicoyaestabas,
    8_2Porqueconsideraqueesunmejorempleo,
    13_Hasrecibidounprestamoatravesdelproyecto,
    16_Siyateniasunnegocioconsiderasquedespuesdel,
    17_Comohamejorado
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

AND version = (SELECT max(version) FROM bitnami_drupal7.aj_labor temp WHERE temp.uuid = labor.uuid)

GROUP BY UUID) as tb1
WHERE
(4_2Esestetuprimerempleo != 'Sí' AND 14_Tienesunnegociopropio != 'Sí')

AND (4_2Esestetuprimerempleo != 'Sí'
    AND 14_Tienesunnegociopropio != 'Sí' 
    AND (8_Cuandoiniciasteelcursotecnicoyaestabas != 'Sí' AND 8_2Porqueconsideraqueesunmejorempleo NOT IN ('Mejor salario', 'Seguro médico', 'Mejor cobertura médica', 'Bonificaciones adicionales al salario', 'Pago de capacitaciones', 'Pago de horas extras y pago de licencias médicas'))
    AND (16_Siyateniasunnegocioconsiderasquedespuesdel != 'Sí' AND 17_Comohamejorado NOT IN ('Mejores ingresos',  'Mayores ingresos', 'Mejor planificación', 'Mejor atención al cliente', 'Mayor variedad de productos o servicios ofrecidos', 'Mayor conocimiento de tu negocio')))

AND (14_Tienesunnegociopropio != 'Sí')

AND (16_Siyateniasunnegocioconsiderasquedespuesdel != 'Sí' AND 17_Comohamejorado NOT IN ('Mejores ingresos',  'Mayores ingresos', 'Mejor planificación', 'Mejor atención al cliente', 'Mayor variedad de productos o servicios ofrecidos', 'Mayor conocimiento de tu negocio'))

AND (13_Hasrecibidounprestamoatravesdelproyecto != 'Sí' AND 14_Tienesunnegociopropio NOT IN ('Sí', 'Ya tenía un negocio'))


