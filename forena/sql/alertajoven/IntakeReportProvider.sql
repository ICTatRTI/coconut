--ACCESS=access content
SELECT ifnull(entity.field_agency_name_value, "Total") as 'Provider',
SUM(case when reg.Sexo = 'M' then 1 else 0 end) as 'Male', 
SUM(case when reg.Sexo = 'F' then 1 else 0 end) as 'Female',
SUM(case when reg.Sexo = 'M' then 0 when reg.Sexo = 'F' then 0 else 1 end) as 'Unknown',
count(reg.Sexo) as 'Gender'
FROM bitnami_drupal7.aj_registration reg
join bitnami_drupal7.aj_survey survey on survey.uuid=reg.uuid
join bitnami_drupal7.field_data_field_agency_name entity on entity.entity_id=reg.provider_id

where 1 = 1 
-- don't really need the 1 = 1 but if the other where's go away, it IS needed.
-- 
and reg.provider_id = :provider_id
and survey.createdAt is not null
and entity.entity_type = 'provider' 
and entity.bundle = 'provider'
--IF=:startdate
and survey.createdAt >= :startdate
--END
--IF=:to_date
and survey.createdat <= :to_date
--END
group by entity.field_agency_name_value
WITH ROLLUP
