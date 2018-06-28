-- ACCESS=access content
SELECT 
SUM(case when s.9Dóndenaciste = 'Haití' or s.9Dóndenaciste = 'Otro' then 1 else 0 end) as 'docrisk',
SUM(case when s.13Tieneshijos = 'Sí' then 1 else 0 end) as 'youthwithdep',
SUM(case when s.14Sabesleeryescribir = 'No' or s.15Cuálesel is null or s.20Hasrepetidoalgúncursoenlaescuela = 'Sí' then 1 else 0 end) as 'edurisk',
SUM(case when s.29Cuántashorastrabajasenundía > '8' or s.26Durantel = 'No'  or s.33Actualme = 'Sí' or s.25Hasreali = 'Sí' then 1 else 0 end) as 'labrisk',
SUM(case when s.42Conquéfr = 'Rara vez' or s.45Tepreocu = 'Rara vez'  or s.47AUnpolicíameamenazóverbalmente = 'Sí' or s.47BUnpolicíamecobródinerosinjustificación = 'Sí' or 47CUnpolicíamequitóalgoquemepertenecia = 'Sí' or s.47DUnpolicíamemaltratófísicamente = 'Sí'  or s.66Algunavezhassidoatacadoorobado = 'Sí' or s.68Algunavezhassidosecuestrado = 'Sí'  or s.88Algunave = 'Sí'  or s.89ASilares = 'Sí' then 1 else 0 end) as 'abuserisk',
SUM(case when s.48Hassidot = 'Sí' or s.49Hassidodetenidoporlapolicíaporalgúnmotivo = 'Sí'  or s.47AUnpolicíameamenazóverbalmente = 'Sí' or s.50Hassidod = 'Sí' or 51Algunode = 'Sí' or s.52Enlosúlt = 'Sí'  or s.52Enlosúlt = 'Sí' or s.54Enlosúlt = 'Sí'  or s.55Enlosúlt = 'Sí'  or s.56Enlosúlt = 'Sí'   or s.57Enlosúlt = 'Sí'  or s.58Enlosúlt = 'Sí' or s.59Enlosúlt = 'Sí' or s.60Enlosúlt = 'Sí'  or s.61Enlosúlt = 'Sí'  or s.62Enlosúlt = 'Sí'  or s.63Enlosúlt = 'Sí'  or s.64Enlosúlt = 'Sí'  or s.65Hasdañad = 'Sí'   or s.67Algunavezhasatacadoorobadoaalguien = 'Sí'  or s.69Algunavezhassecuestradoaalguien = 'Sí'  or s.70Algunave = 'Sí'  or s.71Algunave = 'Sí' or s.72Algunavezhasvendidooayudadoavenderdrogas = 'Sí' or s.73Hasestadoinvolucradoenunapandilla = 'Sí'   or s.74Comparte = 'Sí'    then 1 else 0 end) as 'crimerisk',
SUM(case when s.72Algunavezhasvendidooayudadoavenderdrogas = 'Sí' or s.75Enlosúlt = 'Sí'  or s.76Algunave = 'Sí'  or s.77Hasproba = 'Sí'  or s.78Hasusado = 'Sí' then 1 else 0 end) as 'substancerisk',
SUM(case when s.86Laúltima = 'No' or s.89Algunave = 'Sí'  or s.90Siquisie = 'No'  or s.91Siquisie = 'Sí'  or s.94Algunave = 'No' or s.95Algunave='No' then 1 else 0 end) as 'stdrisk',
SUM(case when s.95Algunave = 'No' or s.13Tieneshijos = 'Sí'  or s.90Siquisie = 'No'  or s.91Siquisie = 'No'  then 1 else 0 end) as 'pregrisk',
SUM(case when s.73Hasestadoinvolucradoenunapandilla = 'Sí' or s.74Comparte = 'Sí'  or s.45Tepreocu = 'Rara vez'   then 1 else 0 end) as 'gangrisk',
SUM(CASE WHEN DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 >= 11 AND DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 <= 17 THEN 1 ELSE 0 END) AS 11_17_total,
SUM(CASE WHEN DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 >= 18 AND DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.Fecha, reg.dob)), '%Y') + 0 <= 24 THEN 1 ELSE 0 END) AS 18_24_total


FROM bitnami_drupal7.aj_survey s 
JOIN bitnami_drupal7.aj_registration reg ON s.uuid = reg.uuid
join bitnami_drupal7.field_data_field_agency_name entity on entity.entity_id=s.provider_id

where 1 = 1
-- don't really need the 1 = 1 but if the other where's go away, it IS needed.
--
and entity.entity_type = 'provider'
and entity.bundle = 'provider'








