-- ACCESS=access content
select * from (select provider_id as provider_id,
  -- field_agency_name_value as provider_name,
	SUM(CASE WHEN Hombresquetienensexoconhombres = 'true' and sexo = 'M' THEN 1 ELSE 0 END) AS MasMHMtotal,
	SUM(CASE WHEN Hombresquetienensexoconhombres = 'true' and sexo = 'F' THEN 1 ELSE 0 END) AS FemMHMtotal,
	SUM(CASE WHEN Hombresquetienensexoconhombres = 'true' and sexo != 'M' and sexo != 'F' THEN 1 ELSE 0 END) AS UnkMHMtotal,
	SUM(CASE WHEN Lostrabajadoresdelsexo = 'true' and sexo = 'M' THEN 1 ELSE 0 END) AS MasCSWtotal,
	SUM(CASE WHEN Lostrabajadoresdelsexo = 'true' and sexo = 'F' THEN 1 ELSE 0 END) AS FemCSWtotal,
	SUM(CASE WHEN Lostrabajadoresdelsexo = 'true' and sexo != 'F' and sexo != 'M' THEN 1 ELSE 0 END) AS UnkCSWtotal,
	SUM(CASE WHEN Usuariosdedrogasintravenosas = 'true' and sexo = 'M' THEN 1 ELSE 0 END) AS MasPWIDtotal,
	SUM(CASE WHEN Usuariosdedrogasintravenosas = 'true' and sexo = 'F' THEN 1 ELSE 0 END) AS FemPWIDtotal,
	SUM(CASE WHEN Usuariosdedrogasintravenosas = 'true' and sexo != 'F' and sexo != 'M' THEN 1 ELSE 0 END) AS UnkPWIDtotal,
	count(distinct uuid) as totalUUID
from (
select 
	distinct marp.provider_id, 
	field_agency_name_value,
	sexo,
	Hombresquetienensexoconhombres,
	Lostrabajadoresdelsexo,
	Usuariosdedrogasintravenosas,
	marp.uuid 
from bitnami_drupal7.aj_marp  marp 
	join bitnami_drupal7.`aj_attendance` atten on atten.uuid = marp.uuid
	join bitnami_drupal7.`aj_registration` reg on reg.uuid = marp.uuid
	join bitnami_drupal7.`field_data_field_agency_name` agencyName on marp.provider_id = agencyName.entity_id
	join bitnami_drupal7.`field_data_field_agency_active` agencyActive on marp.provider_id = agencyActive.entity_id
where
	field_agency_active_value = 1
	and marp.provider_id in (:provider_id)

--SWITCH=:collateral
-- estecolateralparticipante can have 1 of 4 values: No, Si, No Sabe (which means Don't know), blank (which means no value, not set)
--CASE=collateral
and Estecolateralparticipante = 'Sí'
--CASE=nonCollateral
and Estecolateralparticipante != 'Sí'
--END

--IF=:from_date
and SUBSTRING(marp.createdAt, 1, 10) >= :from_date
--END
--IF=:to_date
and SUBSTRING(marp.createdAt, 1, 10) <= :to_date
--END
order by field_agency_name_value
) distinctMarp
group by provider_id) as tb1

 RIGHT JOIN
 
 (select provider_id as provider_id, 
	field_agency_name_value as provider_name,  
	count(distinct uuid) as totalUniverse
from (
select 
	distinct marp.provider_id, 
	field_agency_name_value,  
	marp.uuid 
from bitnami_drupal7.aj_marp  marp 
	join bitnami_drupal7.`aj_registration` reg on reg.uuid = marp.uuid
	join bitnami_drupal7.`field_data_field_agency_name` agencyName on marp.provider_id = agencyName.entity_id
	join bitnami_drupal7.`field_data_field_agency_active` agencyActive on marp.provider_id = agencyActive.entity_id
where
	field_agency_active_value = 1
	and marp.provider_id in (:provider_id)

--SWITCH=:collateral
-- estecolateralparticipante can have 1 of 4 values: No, Si, No Sabe (which means Don't know), blank (which means no value, not set)
--CASE=collateral
and Estecolateralparticipante = 'Sí'
--CASE=nonCollateral
and Estecolateralparticipante != 'Sí'
--END

--IF=:from_date
and SUBSTRING(marp.createdAt, 1, 10) >= :from_date
--END
--IF=:to_date
and SUBSTRING(marp.createdAt, 1, 10) <= :to_date
--END

order by field_agency_name_value
) distinctMarp
group by provider_id)  AS tb2 USING (provider_id)
order by provider_name asc