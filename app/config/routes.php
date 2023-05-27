<?php

$routes->get('/', 'Chat@index');

$routes->post('/ask', 'API@ask');
$routes->get('/ping', 'API@ping');


$routes->get('/image', 'Image@index');
$routes->post('/image', 'Image@index');

$routes->get('/import/ticker/{id}', 'Import@ticker');

$routes->get('/settings', 'Settings@index');
$routes->get('/settings/new', 'Settings@new');
$routes->post('/settings/new', 'Settings@create');
$routes->get('/settings/{internalName}', 'Settings@edit');
$routes->post('/settings/{internalName}', 'Settings@save');
$routes->get('/settings/{internalName}/delete', 'Settings@delete');

$routes->get('/import/article/{id:\d+}', 'Import@index');


// You can delete these if you don´t need Users in your App

// Authentication Routes
$routes->get('/login', 'Authentication@login');
$routes->post('/login', 'Authentication@login');
$routes->get('/logout', 'Authentication@logout');
$routes->get('/profile', 'Authentication@profile');
$routes->get('/password-reset', 'Authentication@password_reset_form');
$routes->post('/password-reset', 'Authentication@password_reset_send_mail');
$routes->get('/password-change[/{resetToken}]', 'Authentication@password_change_form');
$routes->post('/password-change[/{resetToken}]', 'Authentication@password_change_process');
$routes->get('/profile/edit', 'Authentication@edit_profile');
$routes->post('/profile/edit', 'Authentication@edit_profile');

// Usermanagement / Admin Routes
$routes->get('/admin', 'Usermanagement@index');
$routes->get('/admin/new', 'Usermanagement@new');
$routes->post('/admin', 'Usermanagement@create');
$routes->get('/admin/{id:\d+}', 'Usermanagement@show');
$routes->get('/admin/{id:\d+}/delete/{token}', 'Usermanagement@delete');
$routes->post('/admin/{id:\d+}', 'Usermanagement@update');

$routes->get('/{category}', 'Chat@index');