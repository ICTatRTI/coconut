<?php

/**
 * This is the hook_views_data implementation for the aj_registration table.
 */
function beneficiariesView_views_data()
{
 $data = array();

    $data['aj_registration']['table']['group'] = t('Registration');

    $data['aj_registration']['table']['base'] = array(
        'title' => t('Registration'),
        'help' => t('This is data from the aj_registration table.'),
    );

    $data['aj_registration']['table']['join'] = array(
    'eck_provider' => array(
      'left_field' => 'id',
      'field' => 'provider_id',
    ),
  );


// The ID field
$data['aj_registration']['uuid'] = array(
	'group' => t('Registration'),
    'title' => t('UUID'),
    'help' => t('The beneficiary UUID.'),
    'field' => array(
        'handler' => 'views_handler_field',
    	'click sortable' => TRUE,
    ),
    'sort' => array(
        'handler' => 'views_handler_sort',
    ),
    'filter' => array(
        'handler' => 'views_handler_filter_string',
    ),
);

// The nombre field
$data['aj_registration']['nombre'] = array(
	'group' => t('Registration'),
    'title' => t('Nombre'),
    'help' => t('The beneficiary name.'),
    'field' => array(
        'handler' => 'views_handler_field',
    	 'click sortable' => TRUE,
    ),
    'sort' => array(
        'handler' => 'views_handler_sort',
    ),
    'filter' => array(
        'handler' => 'views_handler_filter_string',
    ),
);

// The apellido field
$data['aj_registration']['apellido'] = array(
	'group' => t('Registration'),
    'title' => t('Apellido'),
    'help' => t('The beneficiary surname.'),
    'field' => array(
        'handler' => 'views_handler_field',
       	'click sortable' => TRUE,
    ),
    'sort' => array(
        'handler' => 'views_handler_sort',
    ),
    'filter' => array(
        'handler' => 'views_handler_filter_string',
    ),
);


// The apodo field
$data['aj_registration']['apodo'] = array(
	'group' => t('Registration'),
    'title' => t('Apodo'),
    'help' => t('The beneficiary nickname.'),
    'field' => array(
        'handler' => 'views_handler_field',
    	'click sortable' => TRUE,
    ),
    'sort' => array(
        'handler' => 'views_handler_sort',
    ),
    'filter' => array(
        'handler' => 'views_handler_filter_string',
    ),
);

// The Estecolateralparticipante field
$data['aj_registration']['Estecolateralparticipante'] = array(
	'group' => t('Registration'),
    'title' => t('¿Indirecto?'),
    'help' => t('The beneficiary type.'),
    'field' => array(
        'handler' => 'views_handler_field',
    	'click sortable' => TRUE,
    ),
    'sort' => array(
        'handler' => 'views_handler_sort',
    ),
    'filter' => array(
        'handler' => 'views_handler_filter_string',
    ),
);


// The DOB field
$data['aj_registration']['DOB'] = array(
	'group' => t('Registration'),
    'title' => t('Fecha de Nacimiento'),
    'help' => t('The beneficiary date of birth.'),
    'field' => array(
        'handler' => 'views_handler_field',
    	'click sortable' => TRUE,
    ),
    'sort' => array(
        'handler' => 'views_handler_sort',
    ),
    'filter' => array(
        'handler' => 'views_handler_filter_date',
    ),
);


// The Fecha field
$data['aj_registration']['Fecha'] = array(
	'group' => t('Registration'),
    'title' => t('Fecha de Registro'),
    'help' => t('The Beneficiary date of registration.'),
    'field' => array(
        'handler' => 'views_handler_field',
    	'click sortable' => TRUE,
    ),
    'sort' => array(
        'handler' => 'views_handler_sort',
    ),
    'filter' => array(
        'handler' => 'views_handler_filter_date',
    ),
);



// The provider_name field
$data['aj_registration']['provider_name'] = array(
	'group' => t('Registration'),
    'title' => t('Organización name'),
    'help' => t('The organización name.'),
    'field' => array(
        'handler' => 'views_handler_field',
    	'click sortable' => TRUE,
    ),
    'sort' => array(
        'handler' => 'views_handler_sort',
    ),
    'filter' => array(
        'handler' => 'views_handler_filter_string',
    ),
);


// The provider_id field
$data['aj_registration']['provider_id'] = array(
	'group' => t('Registration'),
    'title' => t('Esto es provider_id'),
    'help' => t('The organización ID.'),
    'field' => array(
        'handler' => 'views_handler_field',
    	'click sortable' => TRUE,
    ),
    'sort' => array(
        'handler' => 'views_handler_sort',
    ),
    'filter' => array(
        'handler' => 'views_handler_filter_numeric',
    ),
 'relationship' => array(
     'base' => 'eck_provider',
     'base field' => 'id',
 	  'field' => 'provider_id',
     'handler' => 'views_handler_relationship',
     'label' => t('aj_registration retlation'),
 	  'help' => t('This is the relationship with aj_registration and eck_provider'),
 	  'title' => t('aj_registration relationship'),
   ),
    'argument' => array(
      'handler' => 'views_handler_argument',
      'numeric' => TRUE,
    ),
);
    return $data;
}

function beneficiariesView_views_data_alter(&$data) {

	//global $user;


	// Leaving this here in case we need to make any update in the future
	//$data['aj_registration']['nombre']['title'] = 'Otro Nombre!';

	//var_dump($user->name);
	//var_dump((in_array('Data Entry', $user->roles))? 'true':'false');
// 	$keys = array_keys($data);
// 	foreach ($keys as $item) {
// 		 watchdog('Claudia', $item);
// 	}
}

