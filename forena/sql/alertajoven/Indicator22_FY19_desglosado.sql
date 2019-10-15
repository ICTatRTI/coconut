--ACCESS=access content
SELECT distinct
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

AND 
case 
when :job_type = 'all' then (
-- Nuevo Empleo
   (4_2Esestetuprimerempleo = 'Sí' OR 14_Tienesunnegociopropio = 'Sí')
-- Ingress incrementados
OR (4_2Esestetuprimerempleo = 'Sí'
    OR 14_Tienesunnegociopropio = 'Sí' 
    OR (8_Cuandoiniciasteelcursotecnicoyaestabas = 'Sí' AND 8_2Porqueconsideraqueesunmejorempleo in ('Mejor salario', 'Seguro médico', 'Mejor cobertura médica', 'Bonificaciones adicionales al salario', 'Pago de capacitaciones', 'Pago de horas extras y pago de licencias médicas'))
    OR (16_Siyateniasunnegocioconsiderasquedespuesdel = 'Sí' AND 17_Comohamejorado in ('Mejores ingresos',  'Mayores ingresos', 'Mejor planificación', 'Mejor atención al cliente', 'Mayor variedad de productos o servicios ofrecidos', 'Mayor conocimiento de tu negocio')))
-- Nuevo Emprendimiento
OR 14_Tienesunnegociopropio = 'Sí'
-- Emprendimiento Mejorado
OR (16_Siyateniasunnegocioconsiderasquedespuesdel = 'Sí' AND 17_Comohamejorado IN ('Mejores ingresos',  'Mayores ingresos', 'Mejor planificación', 'Mejor atención al cliente', 'Mayor variedad de productos o servicios ofrecidos', 'Mayor conocimiento de tu negocio'))
-- Emprendimiento EG 5.3
OR (13_Hasrecibidounprestamoatravesdelproyecto = 'Sí' AND 14_Tienesunnegociopropio IN ('Sí', 'Ya tenía un negocio')) 

)
when :job_type = 'NuevoEmpleo' then    (4_2Esestetuprimerempleo = 'Sí' OR 14_Tienesunnegociopropio = 'Sí')
when :job_type = 'MejorEmpleo' then (4_2Esestetuprimerempleo = 'Sí' OR 14_Tienesunnegociopropio = 'Sí'  OR (8_Cuandoiniciasteelcursotecnicoyaestabas = 'Sí' AND 8_2Porqueconsideraqueesunmejorempleo in ('Mejor salario', 'Seguro médico', 'Mejor cobertura médica', 'Bonificaciones adicionales al salario', 'Pago de capacitaciones', 'Pago de horas extras y pago de licencias médicas')) OR (16_Siyateniasunnegocioconsiderasquedespuesdel = 'Sí' AND 17_Comohamejorado in ('Mejores ingresos',  'Mayores ingresos')))
when :job_type = 'NuevoEmprendimiento' then 14_Tienesunnegociopropio = 'Sí'
when :job_type = 'MejorEMprendimiento' then (16_Siyateniasunnegocioconsiderasquedespuesdel = 'Sí' AND 17_Comohamejorado in ('Mejores ingresos',  'Mayores ingresos') AND 14_Tienesunnegociopropio = 'Ya tenía un negocio')
when :job_type = 'EmprendimientoEG53' then (13_Hasrecibidounprestamoatravesdelproyecto = 'Sí' AND 14_Tienesunnegociopropio IN ('Sí', 'Ya tenía un negocio')) 

end


GROUP BY UUID