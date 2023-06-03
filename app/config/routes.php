<?php

// Pages
$routes->get('/', 'Chat@index');
$routes->get('/faq', 'Chat@faq');

// API
$routes->post('/ask', 'Chat@ask');
$routes->get('/ping', 'API@ping');
$routes->get('/stream/{id}', 'API@stream');

// Conversations
$routes->get('/conversation/{id}', 'Chat@show_conversation');
$routes->get('/conversation/{id}/json', 'Chat@get_conversation_json');
$routes->get('/conversation/{id}/pop', 'Chat@remove_last_conversation_entry');
$routes->get('/conversations', 'Chat@conversation_list');

// Image Generator
$routes->get('/image', 'Image@index');
$routes->post('/image', 'Image@index');

// Imports
$routes->get('/import/ticker/{id}', 'Import@ticker');
$routes->get('/import/article/{portal}/{id:\d+}', 'Import@article');

// Settings / Prompts
$routes->get('/settings', 'Settings@index');
$routes->get('/settings/new', 'Settings@new');
$routes->post('/settings/new', 'Settings@create');
$routes->get('/settings/{internalName}', 'Settings@edit');
$routes->post('/settings/{internalName}', 'Settings@save');
$routes->get('/settings/{internalName}/delete', 'Settings@delete');

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