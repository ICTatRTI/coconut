SELECT DISTINCT
    regs.uuid,
    nombre,
    apellido,
    sexo,
    BarrioComunidad,
    Calleynumero,
    Casa,
    Celular,
    Teléfono,
    DATE_FORMAT(FROM_DAYS(DATEDIFF(regs.fecha, regs.dob)), '%Y') + 0 AS age,
    9Dóndenaciste,
    provider,
    program_name,
    allActivities.activity_id,
    activity_name
FROM
    (SELECT 
        field_data_field_activity_name.entity_id AS activity_id,
        field_activity_name_value AS activity_name,
        field_agency_name_value AS provider,
        field_programname_name_value AS program_name
    FROM
        bitnami_drupal7.field_data_field_activity_name
    JOIN bitnami_drupal7.field_data_field_activity_date ON field_data_field_activity_date.entity_id = field_data_field_activity_name.entity_id
    JOIN bitnami_drupal7.field_data_field_activity_program ON field_data_field_activity_name.entity_id = field_data_field_activity_program.entity_id
    JOIN bitnami_drupal7.field_data_field_program_provider ON field_data_field_activity_program.field_activity_program_target_id = field_data_field_program_provider.entity_id
    JOIN bitnami_drupal7.field_data_field_agency_name ON field_data_field_agency_name.entity_id = field_data_field_program_provider.field_program_provider_target_id
    JOIN bitnami_drupal7.field_data_field_program_name ON field_data_field_activity_program.field_activity_program_target_id = field_data_field_program_name.entity_id
    JOIN bitnami_drupal7.field_data_field_programname_name ON field_data_field_programname_name.entity_id = field_data_field_program_name.field_program_name_target_id
    WHERE
      field_activity_name_value REGEXP 
CASE 
WHEN :exit_activity_name = 'all' THEN  '.*((Terminan capacitación técnica -)|(Obtienen documentación -)|(Graduados de EPC -)|(Reinsertados en la escuela -)|(Terminan Estrella Jóvenes - )|(Terminan QLS - )|(Terminan Red Juvenil -)).*'
WHEN :exit_activity_name = 'Terminan capacitación técnica - ' THEN '.*Terminan capacitación técnica -.*' 
WHEN :exit_activity_name = 'Obtienen documentación - ' THEN '.*Obtienen documentación -.*' 
WHEN :exit_activity_name = 'Graduados de EPC - ' THEN '.*Graduados de EPC -.*'
WHEN :exit_activity_name = 'Reinsertados en la escuela - ' THEN '.*Reinsertados en la escuela -.*'
when :exit_activity_name = 'Terminan Estrella Jóvenes - ' then '.*Terminan Estrella Jóvenes - .*'
when :exit_activity_name = 'Terminan QLS - ' then '.*Terminan QLS - .*'
when :exit_activity_name = 'Terminan Red Juvenil - ' then '.*Terminan Red Juvenil - .*'

END
          
--IF=:from_date
AND field_data_field_activity_date.field_activity_date_value >= :from_date
--END
--IF=:to_date
AND field_data_field_activity_date.field_activity_date_value <= :to_date
--END

) AS allActivities
    JOIN bitnami_drupal7.aj_attendance atten ON atten.activity_id = allActivities.activity_id
    JOIN bitnami_drupal7.aj_registration regs ON regs.uuid = atten.uuid
    LEFT JOIN bitnami_drupal7.aj_survey survey ON survey.uuid = regs.uuid
    WHERE
    1 = 1
    
--IF=:provider_id
AND atten.provider_id IN (:provider_id)

--SWITCH=:collateral
--CASE=collateral
AND regs.Estecolateralparticipante = 'Sí'
--CASE=nonCollateral
AND regs.Estecolateralparticipante != 'Sí'
--END
