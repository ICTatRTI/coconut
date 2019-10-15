-- ACCESS=access content
select atype.entity_id as 'entity_id', atype.field_activitytype_name_value as 'entity_value' from bitnami_drupal7.field_data_field_activitytype_name atype
join bitnami_drupal7.field_data_field_activitytype_active active on active.entity_id=atype.entity_id

where active.field_activitytype_active_value=1

order by atype.field_activitytype_name_value