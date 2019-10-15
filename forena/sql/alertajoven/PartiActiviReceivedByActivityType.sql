-- ACCESS=access content
SELECT
        reg.uuid,
        reg.Nombre, 
        reg.Apellido,
        reg.Sexo,
        reg.Provincia,
        DATE_FORMAT(FROM_DAYS(DATEDIFF(DATE_FORMAT(fecha, '%Y-%m-%d'), reg.DOB)), '%Y')+0 AS age,
        COUNT(atten.uuid) as "Services Received"
FROM
    bitnami_drupal7.aj_attendance atten 
join bitnami_drupal7. aj_registration reg ON reg.uuid = atten.uuid
join bitnami_drupal7.field_data_field_agency_name provider on provider.entity_id=atten.provider_id
join bitnami_drupal7.field_data_field_activity_name aname on aname.entity_id=atten.activity_id
join bitnami_drupal7.field_data_field_activity_type atype on atten.activity_id=atype.entity_id
join bitnami_drupal7.field_data_field_activitytype_name atypename on atype.field_activity_type_target_id=atypename.entity_id
join bitnami_drupal7.field_data_field_activity_date adate on adate.entity_id=atten.activity_id
join bitnami_drupal7.field_data_field_activity_program aprog on aprog.entity_id=atten.activity_id
join bitnami_drupal7.field_data_field_program_provider pp on pp.entity_id=aprog.field_activity_program_target_id
join bitnami_drupal7.field_data_field_program_name pname on pname.entity_id=pp.entity_id
join bitnami_drupal7.field_data_field_programname_name pnamename on pnamename.entity_id=pname.field_program_name_target_id

where 1 = 1 
 
and atten.provider_id = :provider_id

--SWITCH=:collateral
-- estecolateralparticipante can have 1 of 4 values: No, Si, No Sabe (which means Don't know), blank (which means no value, not set)
--CASE=collateral
and reg.Estecolateralparticipante = 'Sí'
--CASE=nonCollateral
and reg.Estecolateralparticipante != 'Sí'
--END

--IF=:program_id
and aprog.field_activity_program_target_id = :program_id  
--END

--IF=:activity_type_id
and atypename.entity_id in (:activity_type_id)
--END

--IF=:from_date
and adate.field_activity_date_value >= :from_date
--END

--IF=:to_date
and adate.field_activity_date_value <= :to_date
--END

--IF=:from_date_reg
and reg.Fecha >= :from_date_reg
--END

--IF=:to_date_reg
and reg.Fecha <= :to_date_reg
--END

GROUP BY atten.uuid
order by reg.Apellido