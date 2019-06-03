SELECT 
IFNULL(provider, '_ALL_PROVIDERS') AS 'provider',
    IFNULL(program_name, '_ALL_PROGRAMS') AS 'program_name',
        sum(case when sexo = 'F' then 1 else 0 end) as Fem_Total,
        sum(case when sexo = 'M' then 1 else 0 end) as Mas_Total,
        SUM(case when Sexo != 'M' and Sexo != 'F' then 1 else 0 end) as Unk_Sex_Total,
        sum(case when age < 11 then 1 else 0 end) as Less_11_Total, 
        sum(case when age >= 0 and age <=17 then 1 else 0 end) as Bet_11_17_Total, -- incluye menores de 11
        sum(case when age >= 18 and age <= 24 then 1 else 0 end) as Bet_18_24_Total,
        sum(case when age > 24 then 1 else 0 end) as greater_24_Total,
        sum(case when age is null then 1 else 0 end) as unk_age_Total,
        sum(case when sexo = 'F' and age <11 then 1 else 0 end) as Fem_less_11_Total,
        sum(case when sexo = 'F' and age >=0 and age <=14 then 1 else 0 end) as Fem_11_14_Total, -- INCLUYE MENORES DE 11!!!
        sum(case when age >=0 and age <=17 then 1 else 0 end) as 11_17_Total,  -- INCLUYE MENORES DE 11!!!
        sum(case when age >=18 and age <=24 then 1 else 0 end) as 18_24_Total,
        sum(case when sexo = 'F' and age >=0 and age <=17 then 1 else 0 end) as Fem_11_17_Total, -- incluye menores de 11
        sum(case when sexo = 'M' and age >=0 and age <=17 then 1 else 0 end) as Mas_11_17_Total, -- incluye menores de 11
        sum(case when sexo = 'F' and age >=15 and age <=19 then 1 else 0 end) as Fem_15_19_Total,
        sum(case when sexo = 'M' and age >=15 and age <=19 then 1 else 0 end) as Mas_15_19_Total,
        sum(case when sexo = 'F' and age >=18 and age <=24 then 1 else 0 end) as Fem_18_24_Total,
        sum(case when sexo = 'M' and age >=18 and age <=24 then 1 else 0 end) as Mas_18_24_Total,
        SUM(CASE WHEN sexo = 'F' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS fem_20_24_total,
        SUM(CASE WHEN sexo = 'M' AND age >= 20 AND age <= 24 THEN 1 ELSE 0 END) AS mas_20_24_total,
        SUM(CASE WHEN sexo = 'F' AND age >= 25 AND age <= 29 THEN 1 ELSE 0 END) AS fem_25_29_total,
        SUM(CASE WHEN sexo = 'M' AND age >= 25 AND age <= 29 THEN 1 ELSE 0 END) AS mas_25_29_total,
        sum(case when sexo = 'F' and age > 14 then 1 else 0 end) as Fem_great_14_Total,
        sum(case when sexo = 'M' and age < 11 then 1 else 0 end) as Mas_less_11_Total,
        sum(case when sexo = 'M' and age >= 0 and age <= 14 then 1 else 0 end) as Mas_11_14_Total,  -- INCLUYE MENORES DE 11!!!
        sum(case when sexo = 'M' and age > 14 then 1 else 0 end) as Mas_great_14_Total,
        SUM(CASE WHEN 9Dóndenaciste = 'República Dominicana' THEN 1 ELSE 0 END) AS rep_dom_total,
        SUM(CASE WHEN 9Dóndenaciste = 'Haití' THEN 1 ELSE 0 END) AS haiti_total,
        SUM(CASE WHEN 9Dóndenaciste = 'Otro' THEN 1 ELSE 0 END) AS otro_total,
        SUM(case when provincia in (select name from bitnami_drupal7.provincia where classification = 'rural') then 1 else 0 end) as rural,
        SUM(case when provincia in (select name from bitnami_drupal7.provincia where classification = 'urbano') then 1 else 0 end) as urban,
        SUM(case when provincia not in (select name from bitnami_drupal7.provincia) then 1 else 0 end) as other_province, 
        count(uuid) as Total
FROM
    (SELECT DISTINCT
        sexo,
            DATE_FORMAT(FROM_DAYS(DATEDIFF(regs.Fecha, regs.dob)), '%Y') + 0 AS age,
            regs.uuid,
            survey.9Dóndenaciste,
            program_name,
            provider,
            provincia
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
        1 = 1
            AND field_activity_name_value REGEXP 
case 
when :exit_activity_name = 'all' then  '.*((Terminan capacitación técnica -)|(Obtienen documentación -)|(Graduados de EPC -)|(Reinsertados en la escuela -)|(Terminan Estrella Jóvenes - )|(Terminan QLS - )).*'
when :exit_activity_name = 'Terminan capacitación técnica - ' then '.*Terminan capacitación técnica -.*' 
when :exit_activity_name = 'Obtienen documentación - ' then '.*Obtienen documentación -.*' 
when :exit_activity_name = 'Graduados de EPC - ' then '.*Graduados de EPC -.*'
when :exit_activity_name = 'Reinsertados en la escuela - ' then '.*Reinsertados en la escuela -.*'
when :exit_activity_name = 'Terminan Estrella Jóvenes - ' then '.*Terminan Estrella Jóvenes - .*'
when :exit_activity_name = 'Terminan QLS - ' then '.*Terminan QLS - .*'
end
          
--IF=:from_date
and field_data_field_activity_date.field_activity_date_value >= :from_date
--END

--IF=:to_date
and field_data_field_activity_date.field_activity_date_value <= :to_date
--END

--IF=:program_id 
and field_data_field_programname_name.entity_id in (:program_id)
--END

) AS allActivities
    JOIN bitnami_drupal7.aj_attendance atten ON atten.activity_id = allActivities.activity_id
    JOIN bitnami_drupal7.aj_registration regs ON regs.uuid = atten.uuid
    JOIN bitnami_drupal7.aj_survey survey ON survey.uuid = regs.uuid
    where
    1 = 1
    
--IF=:provider_id
and atten.provider_id in (:provider_id)
--END

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
and regs.Fecha<= :to_date_reg
--END

) allParticipants
GROUP BY provider, program_name WITH ROLLUP