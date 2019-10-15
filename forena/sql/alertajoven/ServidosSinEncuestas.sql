SELECT 
    bitnami_drupal7.aj_registration.*,
    field_agency_name_value AS Provider,
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 1 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Alfabetización Adultos',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 1 THEN 1
        ELSE 0
    END) AS 'Cantidad Alfabetización Adultos',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 2 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Auto empleo/Empredurismo',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 2 THEN 1
        ELSE 0
    END) AS 'Cantidad Auto empleo/Empredurismo',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 3 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Capacitación Técnico Vocacional',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 3 THEN 1
        ELSE 0
    END) AS 'Cantidad CTV',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 4 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Documentación',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 4 THEN 1
        ELSE 0
    END) AS 'Cantidad Documentación',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 5 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Educación Básica Adultos (EBA)',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 5 THEN 1
        ELSE 0
    END) AS 'Cantidad EBA',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 6 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Educación Secundaría Adultos (PREPARA)',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 6 THEN 1
        ELSE 0
    END) AS 'Cantidad PREPARA',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 7 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Espacio para Crecer',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 7 THEN 1
        ELSE 0
    END) AS 'Cantidad EPC',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 8 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Habilidades para la Vida',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 8 THEN 1
        ELSE 0
    END) AS 'Cantidad HabilidadesxVida',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 9 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Jóvenes Mediadores',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 9 THEN 1
        ELSE 0
    END) AS 'Cantidad Jóvenes Mediadores',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 10 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Colocación Laboral',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 10 THEN 1
        ELSE 0
    END) AS 'Cantidad Colocación Laboral',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 11 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Retención escolar',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 11 THEN 1
        ELSE 0
    END) AS 'Cantidad Retención escolar',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 12 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Prevención SSR, ITS, VIH/SIDA',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 12 THEN 1
        ELSE 0
    END) AS 'Cantidad Prevencion',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 13 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Pruebas VIH y consejería',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 13 THEN 1
        ELSE 0
    END) AS 'Cantidad Pruebas VIH',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 14 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Servicios de Salud',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 14 THEN 1
        ELSE 0
    END) AS 'Cantidad ServiciosxSalud',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 15 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Servicios terapéuticos',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 15 THEN 1
        ELSE 0
    END) AS 'Cantidad Serviciosxterapéuticos',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 16 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS ' N/A - No es parte de ningún programa',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 16 THEN 1
        ELSE 0
    END) AS 'Cantidad N/A',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 17 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Inserción escolar',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 17 THEN 1
        ELSE 0
    END) AS 'Cantidad Inserción escolar',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 18 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Microcreditos',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 18 THEN 1
        ELSE 0
    END) AS 'CantidadMicrocreditos',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 19 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Reducción de Crimen y Violencia',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 19 THEN 1
        ELSE 0
    END) AS 'CantidadReduccióndeCrimen',
    GROUP_CONCAT(CASE
            WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 20 THEN bitnami_drupal7.field_data_field_programname_name.field_programname_name_value
        END) AS 'Incidencia en Políticas Públicas',
    SUM(CASE
        WHEN bitnami_drupal7.field_data_field_programname_name.entity_id = 20 THEN 1
        ELSE 0
    END) AS 'CantidadIncidenciaPolíticas'
FROM
    bitnami_drupal7.aj_registration
        JOIN
    bitnami_drupal7.field_data_field_agency_name ON bitnami_drupal7.aj_registration.provider_id = bitnami_drupal7.field_data_field_agency_name.entity_id
        JOIN
    bitnami_drupal7.aj_attendance ON bitnami_drupal7.aj_attendance.uuid = bitnami_drupal7.aj_registration.uuid
        JOIN
    bitnami_drupal7.field_data_field_activity_program ON bitnami_drupal7.field_data_field_activity_program.entity_id = bitnami_drupal7.aj_attendance.activity_id
        JOIN
    bitnami_drupal7.field_data_field_program_name ON bitnami_drupal7.field_data_field_program_name.entity_id = bitnami_drupal7.field_data_field_activity_program.field_activity_program_target_id
        JOIN
    bitnami_drupal7.field_data_field_programname_name ON bitnami_drupal7.field_data_field_programname_name.entity_id = bitnami_drupal7.field_data_field_program_name.field_program_name_target_id
WHERE
    bitnami_drupal7.aj_registration.uuid Not IN (SELECT 
            bitnami_drupal7.aj_survey.uuid
        FROM
            bitnami_drupal7.aj_survey
        WHERE
            bitnami_drupal7.aj_survey.provider_id = :provider_id)
        AND bitnami_drupal7.aj_registration.uuid IN (SELECT 
            bitnami_drupal7.aj_attendance.uuid
        FROM
            bitnami_drupal7.aj_attendance)
        AND bitnami_drupal7.aj_attendance.provider_id = :provider_id
                   
--IF=:provider_id
and bitnami_drupal7.aj_registration.provider_id =:provider_id

--SWITCH=:collateral
--CASE=collateral
and bitnami_drupal7.aj_registration.Estecolateralparticipante = 'Sí' 
--CASE=nonCollateral
and bitnami_drupal7.aj_registration.Estecolateralparticipante != 'Sí'
--END

--IF=:from_date
and aj_registration.Fecha >= :from_date
--END
--IF=:to_date
and aj_registration.Fecha <= :to_date
--END

GROUP BY bitnami_drupal7.aj_registration.uuid