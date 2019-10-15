-- ACCESS=access content
SELECT 	reg.Nombre, 
		reg.Apellido, 
		reg.dob, ex.*  
FROM

bitnami_drupal7.aj_exit ex  
join bitnami_drupal7. aj_registration reg ON reg.uuid = ex.uuid
join bitnami_drupal7.field_data_field_agency_name provider on provider.entity_id=ex.provider_id

--IF=:provider_id
and ex.provider_id = :provider_id
--END

GROUP BY ex.uuid
order by reg.Apellido