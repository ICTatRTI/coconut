-- ACCESS=access content
SELECT 
		r.provider_name, 
		r.BarrioComunidad, 
		r.Municipio, 
		r.Provincia,
		r.uuid,
		r.Apellido, 
		r.Nombre, 
		r.Fecha,  
		datediff( NOW(), r.Fecha) as daysactive,
		STR_TO_DATE(CONCAT(r.Día,'-', r.Mes, '-', r.Año), '%d-%m-%Y') as dob,
		cast((datediff( NOW(), STR_TO_DATE(CONCAT(r.Año,'-', r.Mes, '-', r.Día), '%Y-%m-%d')) / 365) AS SIGNED) as age,
		r.Celular,
		r.Calleynumero
FROM bitnami_drupal7.aj_registration r
where 1 = 1 
-- don't really need the 1 = 1 but if the other where's go away, it IS needed.
-- 
and  r.provider_id = :entity_id 
-- IF=:BarrioComunidad
and r.BarrioComunidad = :BarrioComunidad 
-- END
and r.Fecha is not null;
-- END


