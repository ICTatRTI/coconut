SELECT 
provider,
    program_name,allParticipants.activity_id,allParticipants.activity_name
    , description,FechaActividad
FROM
    (SELECT DISTINCT
        sexo,
            DATE_FORMAT(FROM_DAYS(DATEDIFF(DATE_FORMAT(NOW(), '%Y-%m-%d'), regs.dob)), '%Y') + 0 AS age,
            regs.uuid,
            program_name,
            provider,allActivities.activity_id,allActivities.activity_name
            , description     ,FechaActividad
    FROM
        (SELECT 
        field_data_field_activity_name.entity_id AS activity_id,
            field_activity_name_value AS activity_name,
            field_agency_name_value AS provider,
            field_programname_name_value AS program_name
            ,     field_activity_description_value AS description
            ,     field_activity_date_value as FechaActividad

    FROM
        bitnami_drupal7.field_data_field_activity_name
    JOIN bitnami_drupal7.field_data_field_activity_date ON field_data_field_activity_date.entity_id = field_data_field_activity_name.entity_id
    JOIN bitnami_drupal7.field_data_field_activity_program ON field_data_field_activity_name.entity_id = field_data_field_activity_program.entity_id
    JOIN bitnami_drupal7.field_data_field_program_provider ON field_data_field_activity_program.field_activity_program_target_id = field_data_field_program_provider.entity_id
    JOIN bitnami_drupal7.field_data_field_agency_name ON field_data_field_agency_name.entity_id = field_data_field_program_provider.field_program_provider_target_id
    JOIN bitnami_drupal7.field_data_field_program_name ON field_data_field_activity_program.field_activity_program_target_id = field_data_field_program_name.entity_id
    JOIN bitnami_drupal7.field_data_field_programname_name ON field_data_field_programname_name.entity_id = field_data_field_program_name.field_program_name_target_id
    JOIN bitnami_drupal7.field_data_field_activity_description ON field_data_field_activity_description.entity_id = field_data_field_activity_name.entity_id

    WHERE
        1 = 1
            AND field_activity_name_value REGEXP 
case 
when :exit_activity_name = 'all' then  '.*((Obtienen empleo o pasantía pagada - )|(Terminan capacitación técnica - )|(Obtienen documentación - )|(Graduados de EPC - )|(Obtienen empleo o pasantía pagada - )|(Reinsertados en la escuela - )|(Terminan Estrella Jóvenes - )|(Terminan QLS - )).*'
when :exit_activity_name = 'Terminan capacitación técnica - ' then '.*Terminan capacitación técnica - .*' 
when :exit_activity_name = 'Obtienen documentación - ' then '.*Obtienen documentación - .*' 
when :exit_activity_name = 'Graduados de EPC - ' then '.*Graduados de EPC - .*'
when :exit_activity_name = 'Obtienen empleo o pasantía pagada - ' then '.*Obtienen empleo o pasantía pagada - .*'
when :exit_activity_name = 'Reinsertados en la escuela - ' then '.*Reinsertados en la escuela - .*'
when :exit_activity_name = 'Terminan LVPE - ' then '.*Terminan LVPE - *'
when :exit_activity_name = 'Terminan La Compañia - ' then '.*Terminan La Compañia - .*'
when :exit_activity_name = 'Terminan Estrella Jóvenes - ' then '.*Terminan Estrella Jóvenes - .*'
when :exit_activity_name = 'Terminan QLS - ' then '.*Terminan QLS - .*'
end
          
--IF=:from_date
and field_data_field_activity_date.field_activity_date_value >= :from_date
--END
--IF=:to_date
and field_data_field_activity_date.field_activity_date_value <= :to_date
--END


) AS allActivities
    JOIN bitnami_drupal7.aj_attendance atten ON atten.activity_id = allActivities.activity_id
    JOIN bitnami_drupal7.aj_registration regs ON regs.uuid = atten.uuid
    where
    1 = 1
    
--IF=:provider_id
and atten.provider_id in (:provider_id)

--SWITCH=:collateral
--CASE=collateral
and regs.Estecolateralparticipante = 'Sí'
--CASE=nonCollateral
and regs.Estecolateralparticipante != 'Sí'
--END

--IF=:from_date_reg
and regs.Fecha >= :from_date_reg
--END
--IF=:to_date_reg
and regs.Fecha <= :to_date_reg
--END

) allParticipants
GROUP BY provider, program_name,allParticipants.activity_id