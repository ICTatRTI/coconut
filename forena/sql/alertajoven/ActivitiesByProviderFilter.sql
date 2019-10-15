-- ACCESS=access content
SELECT DISTINCT
        activity_id,
        aname.field_activity_name_value
FROM bitnami_drupal7.aj_attendance atten  
JOIN bitnami_drupal7.field_data_field_activity_name aname ON aname.entity_id=atten.activity_id
join bitnami_drupal7.field_data_field_activity_date adate on adate.entity_id=aname.entity_id
WHERE  1 = 1

--IF=:provider_id
AND atten.provider_id = :provider_id
-- ELSE
AND atten.provider_id is null
--END

--IF=:from_date
AND adate.field_activity_date_value >= :from_date
--END

--IF=:to_date
AND adate.field_activity_date_value <= :to_date
--END

order by field_activity_name_value
