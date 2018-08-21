-- ACCESS=access content
SELECT 
    provider.field_agency_name_value as provider_name,
    reg.uuid,
    reg.nombre,
    reg.apellido,
    reg.provincia,
    reg.sexo,
    DATE_FORMAT(FROM_DAYS(DATEDIFF(reg.fecha, reg.dob)),
            '%Y') + 0 AS age,
    survey.10Tienesunactadenacimientodominicana as '(E)P10-ActaDeNacimiento',
    exi.9Conseguistetuactade as '(S)P9-ActaDeNacimiento',
    exi.9AOtro as '(S)P9A-Otro',
    case
        when
            (survey.10Tienesunactadenacimientodominicana = ''
                || exi.9Conseguistetuactade = '')
        THEN
            'N/A'
        WHEN (survey.10Tienesunactadenacimientodominicana = exi.9Conseguistetuactade) THEN 0
        ELSE 1
    END AS '¿Cambió ActaDeNacimiento?',
    survey.101Tienescédula as '(E)P10-1-TieneCédula',
    exi.10Tienescédula as '(S)P10-TieneCédula',
    case
        when
            (survey.101Tienescédula = ''
                || exi.10Tienescédula = '')
        THEN
            'N/A'
        WHEN (survey.101Tienescédula = exi.10Tienescédula) THEN 0
        ELSE 1
    END AS '¿Cambió TieneCédula?',
    survey.16Actualmenteestasasistiendoa as '(E)P16-RecibeEduación',
    exi.11Estasactualmente as '(S)P11-RecibeEduación',
    case
        when
            (survey.16Actualmenteestasasistiendoa = ''
                || exi.11Estasactualmente = '')
        THEN
            'N/A'
        WHEN
            (survey.16Actualmenteestasasistiendoa = 'Ninguno'
                and exi.11Estasactualmente = 'No')
        THEN
            0
        WHEN
            ((survey.16Actualmenteestasasistiendoa = 'Escuela'
                || survey.16Actualmenteestasasistiendoa = 'Universidad')
                and exi.11Estasactualmente = 'Sí')
        THEN
            0
        ELSE 1
    END AS '¿Cambió RecibeEduación?',
    survey.16BQuégradoestascursandoactualmente as '(E)P16B-CursoActual',
    survey.16CCuálnivel as '(E)P16C-NivelActual',
    exi.11AQuégradoestascursandoactualmente as '(S)P11A-CursoActual',
    exi.11B1Cuálnivel as '(S)P11B-1-NivelActual',
    survey.21Hascompletadoalgúncursotécnico as '(E)P21-CursoTécnico',
    exi.12Hascompletadoa as '(S)P12-CursoTécnico',
    case
        when
            (survey.21Hascompletadoalgúncursotécnico = ''
                || exi.12Hascompletadoa = '')
        THEN
            'N/A'
        WHEN (survey.21Hascompletadoalgúncursotécnico = exi.12Hascompletadoa) THEN 0
        ELSE 1
    END AS '¿Cambió CursoTécnico?',
    survey.21ASilarespuestaesafirmativacuálescursos as '(E)P21A-ListaCursosTéc',
    exi.12ASilarespuestaesafirmativacuálescursos as '(S)P12A-ListaCursosTéc',
    survey.25Hasreali as '(E)P25-TrabajoActual',
    survey.26Durantel as '(E)P26-TrabajoActual',
    exi.13Actualmentetienesuntrabajoenquetepaguen as '(S)P13-TrabajoActual',
    case
        WHEN
            (survey.25Hasreali = 'No'
                and exi.13Actualmentetienesuntrabajoenquetepaguen = 'No')
        THEN
            0
        WHEN
            (survey.25Hasreali = 'No'
                and exi.13Actualmentetienesuntrabajoenquetepaguen = 'Sí')
        then
            1
        WHEN
            ((survey.25Hasreali = 'Sí'
                and survey.26Durantel = '') ||
				survey.25Hasreali = '' ||
				exi.13Actualmentetienesuntrabajoenquetepaguen = '')
        THEN
            'N/A'
        WHEN (survey.26Durantel = exi.13Actualmentetienesuntrabajoenquetepaguen) THEN 0
        ELSE 1
    END AS '¿Cambió TrabajoActual?',
    survey.48Hassidot as '(E)P48-TransportadoPolicía',
    exi.15ACuántasveceshassido as '(S)P15A-TransportadoPolicía',
    case
        when
            (survey.48Hassidot = ''
                || exi.15ACuántasveceshassido = '')
        THEN
            'N/A'
        WHEN
            (survey.48Hassidot = 'No'
                && (exi.15ACuántasveceshassido = 0
                || lower(exi.15ACuántasveceshassido) regexp 'ningun[oa]'))
        THEN
            0
        WHEN
            (survey.48Hassidot = 'Sí'
                && (exi.15ACuántasveceshassido > 0))
        THEN
            0
        ELSE 1
    END AS '¿Cambió TransportadoPolicía?',
    survey.49Hassidodetenidoporlapolicíaporalgúnmotivo as '(E)P49-DetenidoPolicía',
    exi.15BCuántasveceshassido as '(S)P15B-DetenidoPolicía',
    case
        when
            (survey.49Hassidodetenidoporlapolicíaporalgúnmotivo = ''
                || exi.15BCuántasveceshassido = '')
        THEN
            'N/A'
        WHEN
            (survey.49Hassidodetenidoporlapolicíaporalgúnmotivo = 'No'
                && (exi.15BCuántasveceshassido = 0
                || lower(exi.15BCuántasveceshassido) regexp 'ningun[oa]'))
        THEN
            0
        WHEN
            (survey.49Hassidodetenidoporlapolicíaporalgúnmotivo = 'Sí'
                && (exi.15BCuántasveceshassido > 0))
        THEN
            0
        ELSE 1
    END AS '¿Cambió DetenidoPolicía?',
    survey.50Hassidod as '(E)P50-AcusadoDelito',
    exi.15CCuántasveceshassido as '(S)P15C-AcusadoDelito',
    case
        when
            (survey.50Hassidod = ''
                || exi.15CCuántasveceshassido = '')
        THEN
            'N/A'
        WHEN
            (survey.50Hassidod = 'No'
                && (exi.15CCuántasveceshassido = 0
                || lower(exi.15CCuántasveceshassido) regexp 'ningun[oa]'))
        THEN
            0
        WHEN
            (survey.50Hassidod = 'Sí'
                && (exi.15CCuántasveceshassido > 0))
        THEN
            0
        ELSE 1
    END AS '¿Cambió AcusadoDelito?',
    survey.43Enquémedidatuvidahasidoafectadaporladelincuencia as '(E)P43-ImpactoDelincuencia',
    exi.16Enquémedidatuvidaha as '(S)P16-ImpactoDelincuencia',
	case
        when
            (survey.43Enquémedidatuvidahasidoafectadaporladelincuencia = ''
                || exi.16Enquémedidatuvidaha = '')
        THEN
            'N/A'
        WHEN (survey.43Enquémedidatuvidahasidoafectadaporladelincuencia = exi.16Enquémedidatuvidaha) THEN 0
        ELSE 1
    END AS '¿Cambió ImpactoDelincuencia?',
    survey.82Algunavezhastenidorelacionessexuales as '(E)P82-RelacionesSexuales',
    exi.17Algunavezhastenidorelacionessexuales as '(S)P17-RelacionesSexuales',
    case
		when
			(survey.82Algunavezhastenidorelacionessexuales = 'Sí' && exi.17Algunavezhastenidorelacionessexuales = 'No')
		then
			'Err'
        when
            (survey.82Algunavezhastenidorelacionessexuales = ''
                || exi.17Algunavezhastenidorelacionessexuales = '')
        THEN
            'N/A'
        WHEN (survey.82Algunavezhastenidorelacionessexuales = exi.17Algunavezhastenidorelacionessexuales) THEN 0
        ELSE 1
    END AS '¿Cambió RelacionesSexuales?',
    survey.86Laúltima as '(E)P86-SexoCondón',
    exi.18Laúltimavez as '(S)P18-SexoCondón',
   case
		when
			survey.82Algunavezhastenidorelacionessexuales = 'Sí' and exi.17Algunavezhastenidorelacionessexuales = 'No'
		then
			'Err'
		WHEN
            ((survey.82Algunavezhastenidorelacionessexuales = 'Sí' and survey.86Laúltima = '') ||
			(exi.17Algunavezhastenidorelacionessexuales = 'Sí' and exi.18Laúltimavez = '') ||
			survey.82Algunavezhastenidorelacionessexuales = '' || exi.17Algunavezhastenidorelacionessexuales = '')
        then
            'N/A'
        WHEN
            (survey.82Algunavezhastenidorelacionessexuales = 'No'
                and exi.17Algunavezhastenidorelacionessexuales = 'No')
        THEN
            0
        WHEN
            (survey.82Algunavezhastenidorelacionessexuales = 'No' && (survey.82Algunavezhastenidorelacionessexuales = exi.18Laúltimavez))
        THEN
            0
        WHEN (survey.86Laúltima = exi.18Laúltimavez) 
		THEN 0
        ELSE 1
    END AS '¿Cambió SexoCondón?',
    CONCAT_WS(',',
            case
                when survey.87ANoutilicéningún = 'true' then 'Ninguno'
            end,
            case
                when survey.87BCondón = 'true' then ' Condón'
            end,
            case
                when survey.87CCondónfemenino = 'true' then 'CondónFemenino'
            end,
            case
                when survey.87DPíldoraanticonceptiva = 'true' then 'Píldora'
            end,
            case
                when survey.87ERitmoma = 'true' then 'Ritmo'
            end,
            case
                when survey.87FRetirod = 'true' then 'Retiro'
            end,
            case
                when survey.87GMelamujereslactando = 'true' then 'Mela'
            end,
            case
                when survey.87HDIUcomoMirenaoParagard = 'true' then 'DIU'
            end,
            case
                when survey.87IInyecci = 'true' then 'Inyección'
            end,
            case
                when survey.87JImplant = 'true' then 'Implante'
            end,
            case
                when survey.87KEsterilizaciónfemenina = 'true' then 'EsterilizaciónFemenina'
            end,
            case
                when survey.87LEsterilizaciónmasculina = 'true' then 'EsterilizaciónMasculina'
            end,
            case
                when survey.87MNoséInseguro = 'true' then 'NoSé-Inseguro'
            end,
            case
                when survey.87NOtro != '' then concat('Otro:', survey.87NOtro)
            end) as '(E)P87-MétodoPrevEmbarazo',
    exi.19Laúltimavez as '(S)P19-MétodoPrevEmbarazo',
    survey.90Siquisie as '(E)P90-ComprarCondón',
    exi.20Siquisierascompraruncondóncreesquepodríasencontrarlo as '(S)P20-ComprarCondón',
    case
        when
            (survey.90Siquisie = ''
                || exi.20Siquisierascompraruncondóncreesquepodríasencontrarlo = '')
        THEN
            'N/A'
        WHEN (survey.90Siquisie = exi.20Siquisierascompraruncondóncreesquepodríasencontrarlo) THEN 0
        ELSE 1
    END AS '¿Cambió ComprarCondón?',
    survey.91Siquisie as '(E)P91-ConvencerParejaUsoCondón',
    exi.21Siquisierastenersexo as '(S)P21-ConvencerParejaUsoCondón',
    case
        when
            (survey.91Siquisie = ''
                || exi.21Siquisierastenersexo = '')
        THEN
            'N/A'
        WHEN (survey.91Siquisie = exi.21Siquisierastenersexo) THEN 0
        ELSE 1
    END AS '¿Cambió ConvencerParejaUsoCondón?',
    exi.23EstatusdeSalida as '(S)P23-EstatusDeSalida'
FROM
    bitnami_drupal7.aj_exit exi
            JOIN
    bitnami_drupal7.aj_registration reg ON exi.uuid = reg.uuid
            JOIN
    bitnami_drupal7.field_data_field_agency_name provider ON exi.provider_id = provider.entity_id
        JOIN
    bitnami_drupal7.aj_survey survey ON exi.uuid = survey.uuid
where provider.entity_id in (:provider_id)
--IF=:from_date_survey
and survey.createdAt >= :from_date_survey
--END
--IF=:to_date_survey
and survey.createdAt <= :to_date_survey
--END
--IF=:from_date_exit
and exi.createdAt >= :from_date_exit
--END
--IF=:to_date_survey
and exi.createdAt <= :to_date_exit
--END