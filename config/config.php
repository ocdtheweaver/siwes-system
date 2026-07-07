<?php
/**
 * If you place this project in a subfolder of your web server
 * (e.g. htdocs/siwes-system, so you visit http://localhost/siwes-system/),
 * set BASE_URL to that subfolder path: '/siwes-system'
 *
 * If this project itself IS your web root (the document root points
 * straight at this folder - this is the case for the Docker setup in
 * docker-compose.yml), leave BASE_URL as an empty string.
 *
 * APP_BASE_URL, if set as an environment variable, overrides the default
 * below without needing to edit this file.
 */
$envBaseUrl = getenv('APP_BASE_URL');
define('BASE_URL', $envBaseUrl !== false ? $envBaseUrl : '/siwes-system');
