-- ACCESS=access content
select distinct provider.entity_id, provider.field_agency_name_value, activeProvider.field_agency_active_value
	from bitnami_drupal7.field_data_field_agency_name provider
	join bitnami_drupal7.field_data_field_agency_active activeProvider on provider.entity_id = activeProvider.entity_id
	join bitnami_drupal7.field_data_field_user_provider userProvider 
	join bitnami_drupal7.users_roles userRoles 
	where 
	userProvider.entity_id =  :current_user
	and activeProvider.field_agency_active_value = 1
	and userRoles.uid =  :current_user
	and IF(userRoles.rid = 4, -- provider admin
				if(provider.entity_id = userProvider.field_user_provider_target_id, 1, 0), 
				    if(userRoles.rid = 8, -- case manager
					   if(provider.entity_id = userProvider.field_user_provider_target_id, 1, 0),
					       if(userRoles.rid = 9, -- data entry
						      if(provider.entity_id = userProvider.field_user_provider_target_id, 1, 0),
						          if(userRoles.rid = 10, -- Provider Consulta
                                    if(provider.entity_id = userProvider.field_user_provider_target_id, 1, 0),
						          1    
						) 
					)
				)
			) 
	order by field_agency_name_value;


--select * from bitnami_drupal7.users_roles where uid = :current_user;
--select rid into @roleId from bitnami_drupal7.users_roles where uid = :current_user;
--select field_user_provider_target_id into @userProviderId from bitnami_drupal7.field_data_field_user_provider where entity_id = :current_user;
--select * from bitnami_drupal7.field_data_field_agency_name where entity_id = @userProviderId;
--select entity_id, field_agency_name_value 
--	from bitnami_drupal7.field_data_field_agency_name 
--	where IF(@roleId = 4, -- provider admin
--				if(entity_id = @userProviderId, 1, 0), 
--				if(@roleId = 8, -- case manager
--					if(entity_id = @userProviderId, 1, 0),
--					if(@roleId = 9, -- data entry
--						if(entity_id = @userProviderId, 1, 0),
--						1
--					)
--				)
--			) 
--	order by field_agency_name_value;