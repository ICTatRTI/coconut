-- ACCESS=access content
SELECT provider.field_agency_name_value,
		provider.entity_id,reg.Nombre, 
		reg.uuid,
		reg.Apellido, 
		reg.Sexo, 
		reg.Provincia, 
		reg.DOB, 
		DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 AS age,
  9Dóndenaciste,
  9Dóndenacisteotro,
  13Tieneshijos,

SUM(case when pnamename.entity_id = 16 then 1 else 0 end) as 'N/A - No es parte de ningun programa',
SUM(case when pnamename.entity_id = 1 then 1 else 0 end) as 'Alfabetizacion Adultos',
SUM(case when pnamename.entity_id = 2 then 1 else 0 end) as 'Auto empleo/Empredurismo',
SUM(case when pnamename.entity_id = 3 then 1 else 0 end) as 'Capacitacion Tecnico Vocacional',
SUM(case when pnamename.entity_id = 10 then 1 else 0 end) as 'Colocacion Laboral',
SUM(case when pnamename.entity_id = 4 then 1 else 0 end) as 'Documentacion',
SUM(case when pnamename.entity_id = 5 then 1 else 0 end) as 'Educacion Basica Adultos (EBA)',
SUM(case when pnamename.entity_id = 6 then 1 else 0 end) as 'Educacion Secundaria Adultos (PREPARA)',
SUM(case when pnamename.entity_id = 7 then 1 else 0 end) as 'Espacio para Crecer',
SUM(case when pnamename.entity_id = 8 then 1 else 0 end) as 'Habilidades para la Vida',
SUM(case when pnamename.entity_id = 20 then 1 else 0 end) as 'incidencia en Politicas Publicas',
SUM(case when pnamename.entity_id = 17 then 1 else 0 end) as 'insercion escolar',
SUM(case when pnamename.entity_id = 9 then 1 else 0 end) as 'Jovenes Mediadores',
SUM(case when pnamename.entity_id = 18 then 1 else 0 end) as 'Microcreditos',
SUM(case when pnamename.entity_id = 12 then 1 else 0 end) as 'Prevencion SSR, iTS, ViH/SiDA',
SUM(case when pnamename.entity_id = 13 then 1 else 0 end) as 'Pruebas ViH y consejeria',
SUM(case when pnamename.entity_id = 19 then 1 else 0 end) as 'Reduccion de Crimen y Violencia',
SUM(case when pnamename.entity_id = 11 then 1 else 0 end) as 'Retencion escolar',
SUM(case when pnamename.entity_id = 14 then 1 else 0 end) as 'Servicios de Salud',
SUM(case when pnamename.entity_id = 15 then 1 else 0 end) as 'Servicios terapeuticos',
case when reg.uuid in (select aj_survey.uuid from bitnami_drupal7.aj_survey) then 'Si' else 'No' end as 'Encuesta',

COUNT(atten.uuid) as "Total"
FROM
	bitnami_drupal7.aj_attendance atten 
join bitnami_drupal7. aj_registration reg ON reg.uuid = atten.uuid
LEFT join bitnami_drupal7.aj_survey survey ON survey.uuid = atten.uuid
join bitnami_drupal7.field_data_field_agency_name provider on provider.entity_id=atten.provider_id
join bitnami_drupal7.field_data_field_activity_name aname on aname.entity_id=atten.activity_id
join bitnami_drupal7.field_data_field_activity_date adate on adate.entity_id=atten.activity_id
join bitnami_drupal7.field_data_field_activity_program aprog on aprog.entity_id=atten.activity_id
join bitnami_drupal7.field_data_field_program_provider pp on pp.entity_id=aprog.field_activity_program_target_id
join bitnami_drupal7.field_data_field_program_name pname on pname.entity_id=pp.entity_id
join bitnami_drupal7.field_data_field_programname_name pnamename on pnamename.entity_id=pname.field_program_name_target_id

where 1 = 1 
-- and pp.field_program_provider_target_id = 24
and pp.field_program_provider_target_id in (:provider_id)  


 
--SWITCH=:collateral
-- estecolateralparticipante can have 1 of 4 values: No, Si, No Sabe (which means Don't know), blank (which means no value, not set)
--CASE=collateral
and reg.Estecolateralparticipante = 'Si'
--CASE=nonCollateral
and reg.Estecolateralparticipante != 'Si'
--END



GROUP BY atten.uuid
order by reg.Apellido