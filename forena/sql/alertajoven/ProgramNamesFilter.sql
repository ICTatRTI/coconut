-- ACCESS=access content
SELECT 
	distinct(pnamename.entity_id), 
	pnamename.field_programname_name_value 
FROM 
	bitnami_drupal7.field_data_field_programname_name pnamename
WHERE 1=1
order by pnamename.field_programname_name_value