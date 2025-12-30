<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category('local_soap_sepe_cat', get_string('pluginname', 'local_soap_sepe')));

    $ADMIN->add('local_soap_sepe_cat', new admin_externalpage(
        'local_soap_sepe_centro',
        get_string('managecenters', 'local_soap_sepe'),
        new moodle_url('/local/soap_sepe/centro/index.php')
    ));

    $ADMIN->add('local_soap_sepe_cat', new admin_externalpage(
        'local_soap_sepe_accion',
        get_string('manageactions', 'local_soap_sepe'),
        new moodle_url('/local/soap_sepe/accion/index.php')
    ));
}

