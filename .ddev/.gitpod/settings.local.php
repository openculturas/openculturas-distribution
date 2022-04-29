<?php

// somehow ddev can not resolve the db service with service name.
$databases['default']['default']['host'] = 'ddev-openculturas-db';