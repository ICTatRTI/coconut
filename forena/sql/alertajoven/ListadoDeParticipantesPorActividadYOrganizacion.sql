-- ACCESS=access content
select distinct
        activity_id,
        aname.field_activity_name_value,
        adate.field_activity_date_value,
        reg.uuid,
        reg.nombre,
        reg.apellido,
        reg.apodo,
        reg.Estecolateralparticipante,
        reg.Sexo,
        reg.DOB,
        reg.BarrioComunidad,
        reg.Fecha
FROM bitnami_drupal7.aj_registration reg
join bitnami_drupal7.aj_attendance atten on atten.uuid=reg.uuid
LEFT JOIN bitnami_drupal7.aj_survey sur ON sur.uuid = reg.uuid
join bitnami_drupal7.field_data_field_agency_name provider on provider.entity_id=atten.provider_id
join bitnami_drupal7.field_data_field_activity_name aname on aname.entity_id=atten.activity_id
join bitnami_drupal7.field_data_field_activity_date adate on adate.entity_id=aname.entity_id
join bitnami_drupal7.field_data_field_activity_program aprog on aname.entity_id=aprog.entity_id
join bitnami_drupal7.field_data_field_program_provider pp on pp.entity_id=aprog.field_activity_program_target_id
join bitnami_drupal7.field_data_field_program_name pname on pname.entity_id=pp.entity_id
join bitnami_drupal7.field_data_field_programname_name pnamename on pname.field_program_name_target_id=pnamename.entity_id

--IF=:provider_id
and pp.field_program_provider_target_id in (:provider_id)  
--END

--SWITCH=:collateral
--CASE=collateral
and reg.Estecolateralparticipante = 'Sí'
--CASE=nonCollateral
and reg.Estecolateralparticipante != 'Sí'
--END

--IF=:from_date
and adate.field_activity_date_value >= :from_date
--END
--IF=:to_date
and adate.field_activity_date_value <= :to_date
--END

--IF=:activity_id
AND activity_id IN (:activity_id)