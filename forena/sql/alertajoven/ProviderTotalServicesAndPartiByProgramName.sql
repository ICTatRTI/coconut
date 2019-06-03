SELECT 
    subTotalPartici.providerName, 
    totalServices,
    totalParticipants 
FROM
    (
                SELECT
                    count(aprog.entity_id) as 'totalServices',
                    provider.entity_id as 'providerId1',
                    IFNULL(provider.field_agency_name_value, '_ALL_PROVIDERS') AS 'providerName'
                FROM 
                    bitnami_drupal7.field_data_field_activity_program aprog
                    join bitnami_drupal7.field_data_field_program_provider pp on pp.entity_id=aprog.field_activity_program_target_id
                    join bitnami_drupal7.field_data_field_program_name pname on pname.entity_id=pp.entity_id
                    join bitnami_drupal7.field_data_field_programname_name pnamename on pnamename.entity_id=pname.field_program_name_target_id
                    join bitnami_drupal7.field_data_field_agency_name provider on provider.entity_id=pp.field_program_provider_target_id
                    join bitnami_drupal7.field_data_field_activity_date adate on adate.entity_id=aprog.entity_id
                where 1 = 1 

--IF=:provider_id
                    and provider.entity_id in (:provider_id)
--END

--IF=:program_name_id
                    and pnamename.entity_id in (:program_name_id)
--END

--IF=:from_date
                    and adate.field_activity_date_value >= :from_date
--END
                    
--IF=:to_date
                    and adate.field_activity_date_value <= :to_date
--END

                   GROUP BY provider.field_agency_name_value WITH ROLLUP
            
     )  subTotalActivities
JOIN 
     (
        SELECT
            count(sub.uuid) as 'totalParticipants',
            sub.provider_id as 'providerId2',
            IFNULL(sub.providerName, '_ALL_PROVIDERS') AS 'providerName'
        FROM (
                SELECT 
                    reg.uuid as 'uuid',
                    pp.field_program_provider_target_id as 'provider_id',
                    provider.field_agency_name_value AS 'providerName'    
                FROM bitnami_drupal7.aj_registration reg
                    join bitnami_drupal7.aj_attendance atten on atten.uuid=reg.uuid
                    join bitnami_drupal7.field_data_field_agency_name provider on provider.entity_id=atten.provider_id
                    join bitnami_drupal7.field_data_field_activity_date adate on adate.entity_id=atten.activity_id
                    join bitnami_drupal7.field_data_field_activity_program aprog on aprog.entity_id=atten.activity_id
                    join bitnami_drupal7.field_data_field_program_provider pp on pp.entity_id=aprog.field_activity_program_target_id
                    join bitnami_drupal7.field_data_field_program_name pname on pname.entity_id=pp.entity_id
                    join bitnami_drupal7.field_data_field_programname_name pnamename on pnamename.entity_id=pname.field_program_name_target_id
                where 1 = 1 
                and reg.uuid in (select distinct(atten.uuid) from bitnami_drupal7.aj_attendance atten)
        
--IF=:provider_id
        and provider.entity_id in (:provider_id)
--END
        
        
--IF=:program_name_id
        and pnamename.entity_id in (:program_name_id)
--END
        
--SWITCH=:collateral
-- estecolateralparticipante can have 1 of 4 values: No, Si, No Sabe (which means Don't know), blank (which means no value, not set)
--CASE=collateral
        and reg.Estecolateralparticipante = 'Sí'
--CASE=nonCollateral
        and reg.Estecolateralparticipante != 'Sí'
--END
        
--IF=:from_date
        and adate.field_activity_date_value >= :from_date
--END
--IF=:to_date
--END
        
        
--IF=:from_date_reg
        and reg.Fecha >= :from_date_reg
--END
--IF=:to_date_reg
        and reg.Fecha<= :to_date_reg
--END
        
        group by reg.uuid, provider.entity_id) sub
        group by sub.providerName WITH ROLLUP 
         
    ) subTotalPartici
ON subTotalActivities.providerName = subTotalPartici.providerName
