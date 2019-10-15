--ACCESS=access content
SELECT  distinct
 agencyName.field_agency_name_value as Organizacion,
 	progNameName.field_programname_name_value as Programa,
 	actDate.entity_id as 'ActividadID',
 	field_activity_name_value as ActividadNombre,
	field_activity_description_value as ActividadDescription,
    field_activitytype_name_value as ActividadTipo,
    field_activity_date_value as 'ActividadFecha YYYY-MM-DD',
  FROM_UNIXTIME(actProp.created,'%Y-%m-%d %H:%i:%s') as 'ActividadCreacion YYYY-MM-DD',
  actProp.uid as CreadorUserID,
  users.name as CreadorNombre,
  activityList.createdAt as 'ListadoDeParticipanteFechaCreacion',
	activityList.lastModifiedAt as 'ListadoDeParticipanteFechaModificación',
  activityList.user_name as 'ListadoDeParticipanteUsuarioCreador',
    count(distinct activityList.uuid) as TOTAL_uuid
 --  beneficiaries.nombre,
 --  beneficiaries.apellido
--   beneficiaries.sexo,
--   beneficiaries.DOB
    FROM
    bitnami_drupal7.field_data_field_activity_date actDate
        JOIN
    bitnami_drupal7.field_data_field_activity_name actName ON actDate.entity_id = actName.entity_id
        JOIN
  bitnami_drupal7.field_data_field_activity_description actDesc ON actDate.entity_id = actDesc.entity_id
       JOIN
     bitnami_drupal7.field_data_field_activity_program actProg ON actProg.entity_id = actDate.entity_id
     join 
		bitnami_drupal7.field_data_field_activity_type actType on actDate.entity_id = actType.entity_id
	join
        bitnami_drupal7.field_data_field_activitytype_name actTypeName on actType.field_activity_type_target_id = actTypeName.entity_id
    join
     bitnami_drupal7.aj_attendance activityList on  actDate.entity_id = activityList.activity_id
     join
     bitnami_drupal7.aj_registration beneficiaries on activityList.uuid = beneficiaries.uuid
	join
     bitnami_drupal7.field_revision_field_program_name ProgType on actProg.field_activity_program_target_id = ProgType.entity_id
	join
    bitnami_drupal7.field_revision_field_programname_name progNameName on ProgType.field_program_name_target_id = progNameName.entity_id
     join 
     bitnami_drupal7.eck_activity actProp on actProp.id = actDate.entity_id
     left join
     bitnami_drupal7.users users on users.uid = actProp.uid
     join
     bitnami_drupal7.field_data_field_program_provider progProv on progProv.entity_id = ProgType.entity_id
     join
     bitnami_drupal7.field_data_field_agency_name agencyName on agencyName.entity_id = progProv.field_program_provider_target_id
     join
     bitnami_drupal7.field_data_field_agency_active agencyActive on agencyActive.entity_id = progProv.field_program_provider_target_id
 WHERE
 1 = 1
-- 	and  FROM_UNIXTIME(actProp.created,'%Y-%m-%d %H:%i:%s') >= '2015-09-05 00:00:00'
-- 	and  FROM_UNIXTIME(actProp.created,'%Y-%m-%d %H:%i:%s') <= '2015-10-26 00:00:00'

--IF=:from_date
and  FROM_UNIXTIME(actProp.created,'%Y-%m-%d') >= :from_date
--END
--IF=:to_date
and  FROM_UNIXTIME(actProp.created,'%Y-%m-%d') <= :to_date
--END

 -- and  field_activity_date_value <= '2015-03-31 00:00:00'
 --  and field_activity_date_value >= '2015-06-31 00:00:00'
-- 	and progNameName.entity_id in (4)
    
--IF=:program_id
and progNameName.entity_id in (:program_id)  
--END
    
--SWITCH=:collateral
--CASE=collateral
and Estecolateralparticipante = 'Sí'
--CASE=nonCollateral
and Estecolateralparticipante != 'Sí'
--END

and agencyName.entity_id in (:provider_id)

group by actName.entity_id