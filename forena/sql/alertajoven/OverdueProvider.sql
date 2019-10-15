--ACCESS=access content
SELECT  
ifnull(field_agency_name_value, "Total") as 'Provider',
SUM(case when fecha >= DATE_SUB(CURDATE(),INTERVAL 30  DAY) then 1 else 0 end) as 'days1',
SUM(case when fecha >= DATE_SUB(CURDATE(),INTERVAL 60  DAY) and fecha <= DATE_SUB(CURDATE(),INTERVAL 31  DAY) then 1 else 0 end) as 'days2',
SUM(case when fecha >= DATE_SUB(CURDATE(),INTERVAL 90  DAY) and fecha <= DATE_SUB(CURDATE(),INTERVAL 61  DAY) then 1 else 0 end) as 'days3',
SUM(case when fecha <= DATE_SUB(CURDATE(),INTERVAL 91  DAY) then 1 else 0 end) as 'days4'
FROM 
(
select distinct uuid, fecha, field_agency_name_value
from bitnami_drupal7.aj_registration reg
join bitnami_drupal7.field_data_field_agency_name entity on entity.entity_id=reg.provider_id
where 1 = 1
and reg.provider_id = :provider_id
and Estecolateralparticipante != 'Sí'
and reg.uuid not in (select uuid from bitnami_drupal7.aj_survey where provider_id = :provider_id)
) uuidDistinctDirectOnly
group by field_agency_name_value
WITH ROLLUP